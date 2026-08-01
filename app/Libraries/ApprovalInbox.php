<?php

namespace App\Libraries;

/**
 * Kotak Persetujuan terpadu — mengumpulkan seluruh item berstatus "menunggu
 * keputusan" dari semua modul menjadi satu daftar ternormalisasi.
 *
 * Otorisasi TIDAK ditebak di sini: pemanggil menyerahkan konteks kapabilitas
 * (hasil canEditMenu()/can()) lewat $ctx, sehingga aturan akses tetap satu
 * sumber di BaseController. Struktur hasil sengaja datar agar bisa dipakai
 * ulang oleh endpoint API mobile (MIC_MOBILE_DESIGN.md §6).
 *
 * Konteks yang diharapkan:
 *   user_id, employee_id (nullable), is_admin,
 *   can_approve_promo_media, can_approve_events, can_approve_pip,
 *   is_hr (kelola HR/People Dev), can_legal
 */
class ApprovalInbox
{
    /** Metadata tampilan per modul: label, ikon, warna aksen. */
    public const MODULES = [
        'promo_media'    => ['Media Promo',     'bi-easel',            'pink'],
        'event'          => ['Event',           'bi-calendar-event',   'crimson'],
        'appraisal'      => ['Appraisal',       'bi-clipboard-check',  'blue'],
        'hr_data'        => ['Data Karyawan',   'bi-person-lines-fill','orange'],
        'hr_document'    => ['Dokumen Karyawan','bi-file-earmark-text','orange'],
        'pip'            => ['PIP',             'bi-graph-up-arrow',   'gold'],
        'idp'            => ['IDP',             'bi-signpost-split',   'green'],
        'legal'          => ['Legal',           'bi-shield-check',     'cyan'],
    ];

    /**
     * Kumpulkan seluruh item menunggu keputusan untuk satu user.
     * @return array<int, array{module:string,title:string,subtitle:?string,url:string,since:?string,urgent:bool}>
     */
    public static function collect(array $ctx): array
    {
        $db    = db_connect();
        $items = [];
        $uid   = (int) ($ctx['user_id'] ?? 0);
        $eid   = (int) ($ctx['employee_id'] ?? 0);
        $admin = ! empty($ctx['is_admin']);

        // ── Media Promo: request menunggu approval ──
        if ($admin || ! empty($ctx['can_approve_promo_media'])) {
            foreach ($db->table('promo_media_usage u')
                ->select('u.id, u.nama_materi, u.tanggal_mulai, u.tanggal_selesai, u.submitted_at, s.nama AS spot_nama, us.name AS pengaju')
                ->join('promo_media_spots s', 's.id = u.spot_id', 'left')
                ->join('users us', 'us.id = u.created_by', 'left')
                ->where('u.status', 'pending')
                ->orderBy('u.submitted_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('promo_media', $r['nama_materi'],
                    trim(($r['pengaju'] ?? '') . ' · ' . ($r['spot_nama'] ?? '') . ' · ' . $r['tanggal_mulai'] . ' s/d ' . $r['tanggal_selesai'], ' ·'),
                    'creative/media-promo/pending', $r['submitted_at']);
            }
        }

        // ── Event: menunggu persetujuan ──
        if ($admin || ! empty($ctx['can_approve_events'])) {
            foreach ($db->table('events e')
                ->select('e.id, e.name, e.mall, e.start_date, e.event_days, e.created_at')
                ->where('e.approval_status', 'pending')
                ->orderBy('e.created_at', 'ASC')->get()->getResultArray() as $r) {
                // MIC menyimpan durasi sebagai event_days, bukan end_date
                $periode = $r['start_date']
                    ? $r['start_date'] . ' · ' . (int) $r['event_days'] . ' hari'
                    : 'tanggal belum diisi';
                $items[] = self::row('event', $r['name'],
                    ucfirst((string) $r['mall']) . ' · ' . $periode, 'events', $r['created_at']);
            }
        }

        // ── Appraisal: template menunggu HR ──
        if ($admin || ! empty($ctx['is_hr'])) {
            foreach ($db->table('appraisal_templates t')
                ->select('t.id, t.nama, t.submitted_at, j.nama AS jabatan_nama')
                ->join('jabatans j', 'j.id = t.jabatan_id', 'left')
                ->where('t.status', 'submitted')
                ->orderBy('t.submitted_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('appraisal', 'Template KPI: ' . ($r['jabatan_nama'] ?? $r['nama']),
                    'Menunggu persetujuan HR', 'appraisal/templates/' . $r['id'], $r['submitted_at']);
            }
            // Form di tahap pengecekan akhir HR
            foreach ($db->table('appraisal_forms f')
                ->select('f.id, f.updated_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = f.employee_id', 'left')
                ->where('f.status', 'hr_review')
                ->orderBy('f.updated_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('appraisal', 'Penilaian: ' . ($r['employee_nama'] ?? '-'),
                    'Menunggu finalisasi HR', 'appraisal/forms/' . $r['id'], $r['updated_at']);
            }
        }

        // ── Appraisal: form diteruskan KE SAYA (penilaian berjenjang) ──
        if ($uid) {
            foreach ($db->table('appraisal_forms f')
                ->select('f.id, f.updated_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = f.employee_id', 'left')
                ->where('f.status', 'in_review')->where('f.current_user_id', $uid)
                ->orderBy('f.updated_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('appraisal', 'Penilaian: ' . ($r['employee_nama'] ?? '-'),
                    'Menunggu penilaian Anda', 'appraisal/forms/' . $r['id'], $r['updated_at'], true);
            }
        }

        // ── HR: pengajuan perubahan data & dokumen karyawan ──
        if ($admin || ! empty($ctx['is_hr'])) {
            foreach ($db->table('employee_change_requests r')
                ->select('r.id, r.label, r.value_new, r.created_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = r.employee_id', 'left')
                ->where('r.status', 'pending')
                ->orderBy('r.created_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('hr_data', ($r['employee_nama'] ?? '-') . ' — ' . $r['label'],
                    'Usulan baru: ' . mb_substr((string) $r['value_new'], 0, 60),
                    'people/change-requests', $r['created_at']);
            }
            foreach ($db->table('employee_documents d')
                ->select('d.id, d.jenis, d.created_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = d.employee_id', 'left')
                ->where('d.status', 'pending')
                ->orderBy('d.created_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('hr_document', ($r['employee_nama'] ?? '-') . ' — ' . $r['jenis'],
                    'Dokumen menunggu verifikasi', 'people/change-requests', $r['created_at']);
            }
        }

        // ── PIP: menunggu persetujuan atasan langsung ──
        if ($eid || $admin || ! empty($ctx['can_approve_pip'])) {
            $q = $db->table('pip_plans p')
                ->select('p.id, p.created_at, e.nama AS employee_nama, e.atasan_id')
                ->join('employees e', 'e.id = p.employee_id', 'left')
                ->where('p.persetujuan_atasan', 'pending')
                ->where('p.status', 'menunggu_persetujuan');
            if (! $admin && empty($ctx['can_approve_pip'])) $q->where('e.atasan_id', $eid);
            foreach ($q->orderBy('p.created_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('pip', 'PIP: ' . ($r['employee_nama'] ?? '-'),
                    'Menunggu persetujuan atasan', 'people/pip/' . $r['id'], $r['created_at']);
            }
        }

        // ── IDP: menunggu persetujuan atasan langsung ──
        if ($eid || $admin || ! empty($ctx['is_hr'])) {
            $q = $db->table('idp_plans p')
                ->select('p.id, p.periode_label, p.created_at, e.nama AS employee_nama, e.atasan_id')
                ->join('employees e', 'e.id = p.employee_id', 'left')
                ->where('p.persetujuan_atasan', 'pending');
            if (! $admin && empty($ctx['is_hr'])) $q->where('e.atasan_id', $eid);
            foreach ($q->orderBy('p.created_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('idp', 'IDP: ' . ($r['employee_nama'] ?? '-'),
                    (string) $r['periode_label'], 'people/idp/' . $r['id'], $r['created_at']);
            }
        }

        // ── Legal: dokumen dalam review ──
        if ($admin || ! empty($ctx['can_legal'])) {
            foreach ($db->table('legal_reviews r')
                ->select('r.id, r.judul, r.created_at')
                ->where('r.status', 'in_review')
                ->orderBy('r.created_at', 'ASC')->get()->getResultArray() as $r) {
                $items[] = self::row('legal', $r['judul'], 'Review dokumen berjalan',
                    'legal/reviews/' . $r['id'], $r['created_at']);
            }
        }

        // Terlama dulu — yang paling lama menunggu paling perlu tindakan
        usort($items, static fn ($a, $b) => strcmp((string) $a['since'], (string) $b['since']));
        return $items;
    }

    /** Jumlah item menunggu keputusan (untuk badge). */
    public static function count(array $ctx): int
    {
        return count(self::collect($ctx));
    }

    /** Bangun konteks kapabilitas dari controller (dipanggil BaseController). */
    public static function contextFor(array $flags): array
    {
        return $flags + [
            'user_id' => 0, 'employee_id' => 0, 'is_admin' => false,
            'can_approve_promo_media' => false, 'can_approve_events' => false,
            'can_approve_pip' => false, 'is_hr' => false, 'can_legal' => false,
        ];
    }

    /** Umur item dalam hari (untuk penanda mendesak). */
    public static function ageDays(?string $since): int
    {
        if (! $since) return 0;
        return (int) floor((time() - strtotime($since)) / 86400);
    }

    private static function row(string $module, string $title, ?string $subtitle, string $url, ?string $since, bool $urgent = false): array
    {
        return [
            'module'   => $module,
            'title'    => $title,
            'subtitle' => $subtitle ?: null,
            'url'      => $url,
            'since'    => $since,
            'urgent'   => $urgent || self::ageDays($since) >= 7,
        ];
    }
}
