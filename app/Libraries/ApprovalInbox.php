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
    /** Batas item per sumber — cegah halaman menarik ratusan tunggakan sekaligus. */
    public const BATAS_PER_SUMBER = 50;

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
        'creative'       => ['Creative',        'bi-palette',          'pink'],
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
                ->orderBy('u.submitted_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
                ->orderBy('e.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
                ->orderBy('t.submitted_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('appraisal', 'Template KPI: ' . ($r['jabatan_nama'] ?? $r['nama']),
                    'Menunggu persetujuan HR', 'appraisal/templates/' . $r['id'], $r['submitted_at']);
            }
            // Form di tahap pengecekan akhir HR
            foreach ($db->table('appraisal_forms f')
                ->select('f.id, f.updated_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = f.employee_id', 'left')
                ->where('f.status', 'hr_review')
                ->orderBy('f.updated_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
                ->orderBy('f.updated_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
                ->orderBy('r.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('hr_data', ($r['employee_nama'] ?? '-') . ' — ' . $r['label'],
                    'Usulan baru: ' . mb_substr((string) $r['value_new'], 0, 60),
                    'people/change-requests', $r['created_at']);
            }
            foreach ($db->table('employee_documents d')
                ->select('d.id, d.jenis, d.created_at, e.nama AS employee_nama')
                ->join('employees e', 'e.id = d.employee_id', 'left')
                ->where('d.status', 'pending')
                ->orderBy('d.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
            foreach ($q->orderBy('p.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
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
            foreach ($q->orderBy('p.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('idp', 'IDP: ' . ($r['employee_nama'] ?? '-'),
                    (string) $r['periode_label'], 'people/idp/' . $r['id'], $r['created_at']);
            }
        }

        // ── Creative standalone: materi diajukan untuk ditinjau ──
        // Gate = canApproveCreative(): admin/manager atau pemegang hak
        // "Setujui" pada creative_main.
        if ($admin || ! empty($ctx['can_creative'])) {
            foreach ($db->table('creative_items c')
                ->select('c.id, c.nama, c.pic, c.versi, c.review_submitted_at, c.updated_at, c.created_at')
                ->selectCount('f.id', 'jml_opsi')
                ->join('creative_files f', "f.creative_item_id = c.id AND f.is_opsi = 1", 'left')
                ->where('c.status', 'review')
                ->groupBy('c.id')
                ->orderBy('c.review_submitted_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('creative', $r['nama'],
                    self::ketCreative($r) . ($r['pic'] ? ' · PIC ' . $r['pic'] : ''),
                    'creative#item-' . $r['id'] . '-s',
                    $r['review_submitted_at'] ?: ($r['updated_at'] ?: $r['created_at']));
            }
        }

        // ── Creative per-event: HANYA admin/manager atau pemegang hak
        // Setujui yang boleh memutuskan, jadi jangan tampilkan ke staf
        // creative yang tak bisa menindaklanjutinya.
        if ($admin || ! empty($ctx['can_creative_event'])) {
            foreach ($db->table('event_creative_items c')
                ->select('c.id, c.nama, c.event_id, c.versi, c.review_submitted_at, c.updated_at, c.created_at, e.name AS event_nama')
                ->selectCount('f.id', 'jml_opsi')
                ->join('events e', 'e.id = c.event_id', 'left')
                ->join('event_creative_files f', "f.creative_item_id = c.id AND f.is_opsi = 1", 'left')
                ->where('c.status', 'review')
                ->groupBy('c.id')
                ->orderBy('c.review_submitted_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('creative', $r['nama'],
                    'Event: ' . ($r['event_nama'] ?? '-') . ' · ' . self::ketCreative($r),
                    'events/' . $r['event_id'] . '/creative#item-' . $r['id'],
                    $r['review_submitted_at'] ?: ($r['updated_at'] ?: $r['created_at']));
            }
        }

        // ── Legal: dokumen dalam review ──
        if ($admin || ! empty($ctx['can_legal'])) {
            foreach ($db->table('legal_reviews r')
                ->select('r.id, r.judul, r.created_at')
                ->where('r.status', 'in_review')
                ->orderBy('r.created_at', 'ASC')->get(self::BATAS_PER_SUMBER)->getResultArray() as $r) {
                $items[] = self::row('legal', $r['judul'], 'Review dokumen berjalan',
                    'legal/reviews/' . $r['id'], $r['created_at']);
            }
        }

        // Terlama dulu — yang paling lama menunggu paling perlu tindakan
        usort($items, static fn ($a, $b) => strcmp((string) $a['since'], (string) $b['since']));
        return $items;
    }


    /**
     * Daftar user yang MUNGKIN punya item menunggu keputusan — dipakai cron
     * pengingat agar tak perlu memindai seluruh user. Sengaja longgar
     * (superset); penyaringan sebenarnya tetap dilakukan collect() per user.
     *
     * @return int[] user_id
     */
    public static function candidateApprovers(): array
    {
        $db  = db_connect();
        $ids = array_merge(
            OrgRecipients::admins(),
            OrgRecipients::withRolePerm('can_approve_promo_media'),
            OrgRecipients::withRolePerm('can_approve_events'),
            OrgRecipients::withRolePerm('can_approve_pip'),
            OrgRecipients::menuEditors('hr_main'),
            OrgRecipients::menuEditors('people_dev'),
            OrgRecipients::menuEditors('legal'),
            OrgRecipients::menuEditors('creative_main'),
            OrgRecipients::menuEditors('creative'),
            // pemegang grant "Setujui" per-orang/dept — tak terjaring resolver di atas
            OrgRecipients::menuApprovers('events'),
            OrgRecipients::menuApprovers('creative_main'),
            OrgRecipients::menuApprovers('creative'),
            OrgRecipients::menuApprovers('people_dev'),
            OrgRecipients::menuApprovers('legal'),
        );

        // Penilai yang sedang dituju form appraisal
        foreach ($db->table('appraisal_forms')->select('current_user_id')
            ->where('status', 'in_review')->where('current_user_id IS NOT NULL', null, false)
            ->groupBy('current_user_id')->get()->getResultArray() as $r) {
            $ids[] = (int) $r['current_user_id'];
        }

        // Atasan langsung dari karyawan yang PIP/IDP-nya menunggu persetujuan
        $sql = 'SELECT DISTINCT a.user_id
                FROM employees e
                JOIN employees a ON a.id = e.atasan_id
                WHERE a.user_id IS NOT NULL AND (
                      EXISTS (SELECT 1 FROM pip_plans p WHERE p.employee_id = e.id
                              AND p.persetujuan_atasan = "pending" AND p.status = "menunggu_persetujuan")
                   OR EXISTS (SELECT 1 FROM idp_plans i WHERE i.employee_id = e.id
                              AND i.persetujuan_atasan = "pending"))';
        foreach ($db->query($sql)->getResultArray() as $r) $ids[] = (int) $r['user_id'];

        return array_values(array_unique(array_filter($ids, static fn ($i) => (int) $i > 0)));
    }

    /**
     * Bangun konteks kapabilitas satu user LANGSUNG DARI DATABASE (tanpa session)
     * — dipakai cron pengingat & kelak endpoint API mobile. Aturannya menyalin
     * BaseController: admin bypass → user_menu_access → department_menu_access.
     * Mengembalikan [] bila user tidak ada / tidak aktif.
     */
    public static function contextForUser(int $userId): array
    {
        $db = db_connect();
        $u  = $db->table('users')->select('id, role, role_id, department_id, is_active')
            ->where('id', $userId)->get()->getRowArray();
        if (! $u || ! $u['is_active']) return [];

        $perms = ['is_admin' => false];
        if (! empty($u['role_id'])) {
            $role = $db->table('roles')->where('id', (int) $u['role_id'])->get()->getRowArray();
            if ($role) $perms = \App\Models\RoleModel::buildPerms($role);
        } elseif (($u['role'] ?? '') === 'admin') {
            $perms = ['is_admin' => true];
        }
        $isAdmin = ! empty($perms['is_admin']) || ($u['role'] ?? '') === 'admin';

        // Aturan akses memakai sumber tunggal MenuAccess (sama dengan web & API).
        // Peta dimuat SEKALI di sini — versi lama menembak 2 query per menu per
        // pemeriksaan, padahal konteks ini dipanggil untuk tiap kandidat di cron.
        $peta = ['is_admin' => $isAdmin] + MenuAccess::mapsForUser($userId);
        $canEdit    = static fn (string $k): bool => MenuAccess::allowed($isAdmin, $peta['user'], $peta['dept'], $k, 'can_edit');
        $canApprove = static fn (string $k): bool => MenuAccess::allowed($isAdmin, $peta['user'], $peta['dept'], $k, 'can_approve');

        $emp = $db->table('employees')->select('id')->where('user_id', $userId)->get()->getRowArray();

        return self::contextFor([
            'user_id'                 => $userId,
            'employee_id'             => (int) ($emp['id'] ?? 0),
            'is_admin'                => $isAdmin,
            // Hak setuju = izin role (lama) ATAU grant "Setujui" pada menu (baru)
            'can_approve_promo_media' => $isAdmin || ! empty($perms['can_approve_promo_media']) || $canApprove('creative_main'),
            'can_approve_events'      => $isAdmin || ! empty($perms['can_approve_events'])      || $canApprove('events'),
            'can_approve_pip'         => $isAdmin || ! empty($perms['can_approve_pip'])         || $canApprove('people_dev'),
            'is_hr'                   => $canEdit('hr_main') || $canEdit('people_dev'),
            'can_legal'               => $canEdit('legal') || $canApprove('legal'),
            // Gate creative mengikuti canApproveCreative() di BaseController —
            // persis syarat yang dipakai endpoint setujui/revisi. Hak can_edit
            // saja TIDAK cukup: desainer yang hanya boleh mengunggah materi
            // akan melihat item yang tak bisa ia putuskan, dan item itu tak
            // akan pernah hilang dari kotaknya.
            'can_creative'            => $isAdmin || in_array($u['role'] ?? '', ['admin', 'manager'], true)
                                         || $canApprove('creative_main'),
            'can_creative_event'      => $isAdmin || in_array($u['role'] ?? '', ['admin', 'manager'], true)
                                         || $canApprove('creative'),
        ]);
    }

    /** Bangun konteks kapabilitas dari controller (dipanggil BaseController). */
    public static function contextFor(array $flags): array
    {
        return $flags + [
            'user_id' => 0, 'employee_id' => 0, 'is_admin' => false,
            'can_approve_promo_media' => false, 'can_approve_events' => false,
            'can_approve_pip' => false, 'is_hr' => false, 'can_legal' => false,
            'can_creative' => false, 'can_creative_event' => false,
        ];
    }

    /** Umur item dalam hari (untuk penanda mendesak). */
    public static function ageDays(?string $since): int
    {
        if (! $since) return 0;
        return (int) floor((time() - strtotime($since)) / 86400);
    }

    /**
     * Keterangan materi creative yang menunggu keputusan: berapa opsi yang
     * harus dipilih dan putaran review ke berapa — dua hal yang menentukan
     * apakah peninjau perlu membandingkan alternatif atau sekadar meng-iya-kan.
     */
    private static function ketCreative(array $r): string
    {
        $jml   = (int) ($r['jml_opsi'] ?? 0);
        $versi = (int) ($r['versi'] ?? 1) ?: 1;

        $ket = $jml > 1
            ? 'Pilih 1 dari ' . $jml . ' opsi'
            : 'Menunggu peninjauan';

        // Sengaja "putaran ke-N", bukan "revisi ke-N": versi juga naik saat
        // materi dibuka kembali setelah disetujui, jadi menyebutnya revisi
        // akan mengarang permintaan revisi yang tidak pernah ada.
        return $versi > 1 ? $ket . ' · putaran ke-' . $versi : $ket;
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
