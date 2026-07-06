<?php

namespace App\Controllers;

use App\Models\TalentPeriodModel;
use App\Models\TalentPlacementModel;
use App\Models\TalentViewerModel;
use App\Models\UserModel;
use App\Libraries\ActivityLog;

/**
 * Talent Portfolio (9-Box) — Performance × Potential.
 * Alur berjenjang (rantai atasan): atasan langsung isi → naik rantai →
 * tingkatan tertinggi verifikasi (final) → HR lock periode.
 * - Rantai putus (atasan tanpa akun) → ditangani HR (fallback), HANYA kasus itu.
 * - Setelah verified: tidak bisa diubah siapa pun (HR hanya lihat + lock).
 * - Peta penuh hanya untuk admin + daftar viewer (dikelola admin).
 * Rantai dihitung in-memory dari satu query employees (hindari N+1).
 */
class TalentPortfolio extends BaseController
{
    protected $periods;
    protected $placements;
    protected $viewers;

    public function __construct()
    {
        $this->periods    = new TalentPeriodModel();
        $this->placements = new TalentPlacementModel();
        $this->viewers    = new TalentViewerModel();
    }

    // ── Akses & util ─────────────────────────────────────────────────────────
    private function uid(): int { return (int) session()->get('user_id'); }
    private function isHr(): bool { return $this->isAdmin() || $this->canEditMenu('hr_main'); }
    private function canViewMap(): bool { return $this->isAdmin() || $this->viewers->isViewer($this->uid()); }
    /** Batas tanggal masuk (probation < 6 bln dikecualikan) — dipakai saat aktivasi. */
    private function cutoff(): string { return date('Y-m-d', strtotime('-6 months')); }

    // ── Rantai atasan in-memory (satu query untuk semua karyawan) ────────────
    /** Peta employee_id => [atasan_id, user_id]. */
    private function empMap(): array
    {
        $rows = db_connect()->table('employees')->select('id, atasan_id, user_id')->get()->getResultArray();
        $map  = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = [
                'atasan_id' => $r['atasan_id'] ? (int) $r['atasan_id'] : null,
                'user_id'   => $r['user_id'] ? (int) $r['user_id'] : null,
            ];
        }
        return $map;
    }

    /** Rantai atasan (employee ids, bawah→atas), anti-loop, maks 15 level. */
    private function chainIds(array $map, int $employeeId): array
    {
        $out = []; $seen = [$employeeId => true];
        $cur = $map[$employeeId]['atasan_id'] ?? null;
        while ($cur && ! isset($seen[$cur]) && isset($map[$cur]) && count($out) < 15) {
            $seen[$cur] = true;
            $out[] = $cur;
            $cur = $map[$cur]['atasan_id'];
        }
        return $out;
    }

    /** User id atasan berakun terdekat (penilai awal). Null bila rantai putus. */
    private function firstActorUid(array $map, int $employeeId): ?int
    {
        foreach ($this->chainIds($map, $employeeId) as $aid) {
            if (! empty($map[$aid]['user_id'])) return (int) $map[$aid]['user_id'];
        }
        return null;
    }

    /**
     * Posisi $uid dalam rantai atasan karyawan.
     * in_chain=false ⇒ user BUKAN bagian rantai (mis. org berubah) — jangan verifikasi.
     * next_uid=null (dengan in_chain=true) ⇒ user adalah puncak rantai.
     */
    private function chainPosition(array $map, int $uid, int $employeeId): array
    {
        $ids = $this->chainIds($map, $employeeId);
        $idx = null;
        foreach ($ids as $i => $aid) {
            if ((int) ($map[$aid]['user_id'] ?? 0) === $uid) { $idx = $i; break; }
        }
        if ($idx === null) return ['in_chain' => false, 'next_uid' => null];
        for ($j = $idx + 1, $n = count($ids); $j < $n; $j++) {
            if (! empty($map[$ids[$j]]['user_id'])) return ['in_chain' => true, 'next_uid' => (int) $map[$ids[$j]]['user_id']];
        }
        return ['in_chain' => true, 'next_uid' => null];
    }

    // ── 1. PETA 9-BOX (viewer/admin) ──────────────────────────────────────────
    public function index()
    {
        if (! $this->canViewMap()) {
            // Bukan viewer peta: arahkan ke fungsi yang sesuai perannya.
            if ($this->isHr()) return redirect()->to('people/talent/periods');
            if (! empty($this->placements->inboxFor($this->uid()))) return redirect()->to('people/talent/input');
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $periods  = $this->periods->allPeriods();
        $periodId = (int) ($this->request->getGet('period') ?: ($periods[0]['id'] ?? 0));
        $ftype    = $this->request->getGet('ftype') === 'jabatan' ? 'jabatan' : 'dept';
        $fid      = (int) $this->request->getGet('fid');

        $db       = db_connect();
        $depts    = $db->table('departments')->where('(is_outsource IS NULL OR is_outsource=0)')->orderBy('name')->get()->getResultArray();
        $jabatans = $db->table('jabatans')->orderBy('grade')->orderBy('nama')->get()->getResultArray();

        // Grouping di controller (bukan view): sel 9-box, kuadran 4-box, belum ditempatkan.
        $cells = $quadCells = $unplaced = [];
        $placed = 0; $total = 0;
        $chosen = $periodId && $fid;
        if ($chosen) {
            $grid  = $this->placements->gridData($periodId, $ftype, $fid);
            $total = count($grid);
            foreach ($grid as $g) {
                if ($g['performance'] === null || $g['potential'] === null) { $unplaced[] = $g; continue; }
                $placed++;
                $cells[(int) $g['performance']][(int) $g['potential']][] = $g;
                $qk = ((int) $g['performance'] >= 2 ? 'H' : 'L') . '_' . ((int) $g['potential'] >= 2 ? 'H' : 'L');
                $quadCells[$qk][] = $g;
            }
        }

        return view('people/talent/index', [
            'user'      => $this->currentUser(),
            'periods'   => $periods,
            'periodId'  => $periodId,
            'ftype'     => $ftype,
            'fid'       => $fid,
            'depts'     => $depts,
            'jabatans'  => $jabatans,
            'cells'     => $cells,
            'quadCells' => $quadCells,
            'unplaced'  => $unplaced,
            'placed'    => $placed,
            'total'     => $total,
            'chosen'    => $chosen,
            'isHr'      => $this->isHr(),
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // ── 2. INPUT / INBOX PENILAI (atasan; HR untuk rantai putus) ─────────────
    public function input()
    {
        $inbox = $this->placements->inboxFor($this->uid());
        if (! empty($inbox)) {
            $map = $this->empMap();
            foreach ($inbox as &$it) {
                $pos = $this->chainPosition($map, $this->uid(), (int) $it['employee_id']);
                // Tombol "Verifikasi (final)" hanya bila user benar-benar puncak rantai.
                $it['is_top'] = $pos['in_chain'] && $pos['next_uid'] === null;
            }
            unset($it);
        }
        $hrPending = $this->isHr() ? $this->placements->hrPendingAll() : [];

        if (empty($inbox) && empty($hrPending) && ! $this->isHr() && ! $this->canViewMap()) {
            return redirect()->to('/')->with('error', 'Tidak ada penilaian talent untuk Anda.');
        }
        return view('people/talent/input', [
            'user'       => $this->currentUser(),
            'inbox'      => $inbox,
            'hrPending'  => $hrPending,
            'isHr'       => $this->isHr(),
            'canViewMap' => $this->canViewMap(),
        ]);
    }

    /** Simpan skor + teruskan/verifikasi satu placement. */
    public function save(int $placementId)
    {
        $p = $this->placements->find($placementId);
        if (! $p) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        $period = $this->periods->find($p['period_id']);
        if (! $period || $period['status'] === 'locked') return redirect()->back()->with('error', 'Periode sudah dikunci.');

        // Otorisasi ketat:
        // - verified = FINAL, tidak bisa diubah siapa pun (HR hanya lihat + lock).
        // - aktor = user yang sedang memegang giliran.
        // - HR fallback HANYA untuk rantai putus (current_actor_id NULL).
        $uid          = $this->uid();
        $isVerified   = $p['status'] === 'verified';
        $isActor      = ! $isVerified && (int) $p['current_actor_id'] === $uid;
        $isHrFallback = ! $isVerified && $p['current_actor_id'] === null && $this->isHr();
        if (! $isActor && ! $isHrFallback) {
            return redirect()->back()->with('error', $isVerified ? 'Penilaian sudah diverifikasi (final).' : 'Bukan giliran Anda.');
        }

        $post = $this->request->getPost();
        $perf = (int) ($post['performance'] ?? 0);
        $pot  = (int) ($post['potential'] ?? 0);
        if ($perf < 1 || $perf > 3 || $pot < 1 || $pot > 3) {
            return redirect()->back()->with('error', 'Performance & Potential wajib diisi (skala 1–3).');
        }

        ActivityLog::captureBefore($p);
        $data = [
            'performance' => $perf,
            'potential'   => $pot,
            'catatan'     => trim($post['catatan'] ?? '') ?: null,
        ];
        if (empty($p['placed_by'])) $data['placed_by'] = $uid;

        $msg    = 'Penilaian disimpan.';
        $action = $post['action'] ?? 'save'; // save | forward
        if ($action === 'forward') {
            if ($isHrFallback) {
                // HR menuntaskan kasus rantai putus → verifikasi final.
                $data['status']           = 'verified';
                $data['current_actor_id'] = null;
                $data['verified_by']      = $uid;
                $data['verified_at']      = date('Y-m-d H:i:s');
                $msg = 'Penilaian diverifikasi (final).';
            } else {
                $map = $this->empMap();
                $pos = $this->chainPosition($map, $uid, (int) $p['employee_id']);
                if (! $pos['in_chain']) {
                    // Struktur organisasi berubah — user tak lagi di rantai karyawan ini.
                    // JANGAN verifikasi; alihkan ke atasan baru (atau HR bila rantai putus).
                    $first = $this->firstActorUid($map, (int) $p['employee_id']);
                    $data['status']           = 'in_review';
                    $data['current_actor_id'] = ($first && $first !== $uid) ? $first : null;
                    $msg = $data['current_actor_id']
                        ? 'Struktur organisasi berubah — penilaian dialihkan ke atasan yang baru.'
                        : 'Struktur organisasi berubah — penilaian dialihkan ke HR.';
                } elseif ($pos['next_uid']) {
                    $data['status']           = 'in_review';
                    $data['current_actor_id'] = $pos['next_uid'];
                    $msg = 'Penilaian diteruskan ke atasan berikutnya.';
                } else {
                    // Puncak rantai → verifikasi final.
                    $data['status']           = 'verified';
                    $data['current_actor_id'] = null;
                    $data['verified_by']      = $uid;
                    $data['verified_at']      = date('Y-m-d H:i:s');
                    $msg = 'Penilaian diverifikasi (final).';
                }
            }
        }
        $this->placements->update($placementId, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'talent_placement', (string) $placementId, 'talent: ' . ($data['status'] ?? 'simpan skor'));

        return redirect()->to('people/talent/input')->with('success', $msg);
    }

    // ── 3. KELOLA PERIODE (HR/admin) ──────────────────────────────────────────
    public function periods()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $rows = $this->periods->allPeriods();

        // Ringkasan placement per periode — satu query GROUP BY.
        $stats = [];
        foreach (db_connect()->table('talent_placements')
            ->select('period_id, COUNT(*) AS n_total, SUM(performance IS NOT NULL) AS n_placed')
            ->groupBy('period_id')->get()->getResultArray() as $s) {
            $stats[(int) $s['period_id']] = $s;
        }
        foreach ($rows as &$r) {
            $r['n_total']  = (int) ($stats[$r['id']]['n_total'] ?? 0);
            $r['n_placed'] = (int) ($stats[$r['id']]['n_placed'] ?? 0);
        }
        unset($r);

        return view('people/talent/periods', ['user' => $this->currentUser(), 'periods' => $rows, 'isAdmin' => $this->isAdmin()]);
    }

    public function createPeriod()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $nama = trim($this->request->getPost('nama') ?? '');
        if ($nama === '') return redirect()->back()->with('error', 'Nama periode wajib diisi.');
        $id = $this->periods->insert(['nama' => $nama, 'status' => 'draft', 'created_by' => $this->uid()]);
        ActivityLog::write('create', 'talent_period', (string) $id, $nama);
        return redirect()->to('people/talent/periods')->with('success', 'Periode dibuat. Klik "Aktifkan" untuk mulai penilaian.');
    }

    /**
     * Aktifkan periode → generate placement untuk semua karyawan dalam cakupan
     * + tetapkan penilai awal (atasan berakun terdekat). Batch insert, tanpa N+1.
     * Hanya SATU periode aktif pada satu waktu.
     */
    public function activatePeriod(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $period = $this->periods->find($id);
        if (! $period || $period['status'] === 'locked') return redirect()->back()->with('error', 'Periode tidak valid.');

        // Cegah dua periode aktif bersamaan (inbox & antrean HR jadi ambigu).
        $otherActive = $this->periods->where('status', 'active')->where('id !=', $id)->countAllResults();
        if ($otherActive > 0) {
            return redirect()->back()->with('error', 'Masih ada periode lain yang aktif. Kunci periode tersebut terlebih dahulu.');
        }

        $map      = $this->empMap();
        $ids      = $this->placements->eligibleIds($this->cutoff());
        $existing = array_map('intval', array_column(
            db_connect()->table('talent_placements')->select('employee_id')->where('period_id', $id)->get()->getResultArray(),
            'employee_id'
        ));
        $existing = array_flip($existing);

        $now = date('Y-m-d H:i:s');
        $batch = [];
        foreach ($ids as $eid) {
            if (isset($existing[$eid])) continue;
            $batch[] = [
                'period_id'        => $id,
                'employee_id'      => $eid,
                'status'           => 'input',
                'current_actor_id' => $this->firstActorUid($map, $eid), // null = rantai putus → HR
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }
        if ($batch) db_connect()->table('talent_placements')->insertBatch($batch, null, 100);

        $this->periods->update($id, ['status' => 'active']);
        ActivityLog::write('update', 'talent_period', (string) $id, $period['nama'] . ' — aktif, generate ' . count($batch) . ' placement');
        return redirect()->to('people/talent/periods')->with('success', 'Periode aktif. ' . count($batch) . ' karyawan siap dinilai (penilai awal = atasan langsung).');
    }

    /**
     * Kunci periode — status placement TIDAK disentuh (jejak input/in_review/verified
     * terjaga untuk audit). save() menolak berdasarkan period.status, dan inbox
     * hanya menampilkan periode aktif, jadi lock satu tabel ini cukup.
     */
    public function lockPeriod(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $period = $this->periods->find($id);
        if (! $period) return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        $this->periods->update($id, ['status' => 'locked', 'locked_by' => $this->uid(), 'locked_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'talent_period', (string) $id, $period['nama'] . ' — dikunci');
        return redirect()->to('people/talent/periods')->with('success', 'Periode dikunci. Penempatan menjadi read-only.');
    }

    // ── 4. KELOLA VIEWER (admin saja) ─────────────────────────────────────────
    public function viewers()
    {
        if (! $this->isAdmin()) return redirect()->to('/')->with('error', 'Akses ditolak (khusus admin).');
        $current = $this->viewers->listWithUser();
        $usedIds = array_column($current, 'user_id');
        $avail   = (new UserModel())->select('id, name, email, role')->where('is_active', 1)
            ->orderBy('name')->findAll();
        $avail   = array_filter($avail, fn($u) => ! in_array($u['id'], $usedIds));
        return view('people/talent/viewers', ['user' => $this->currentUser(), 'viewers' => $current, 'avail' => $avail]);
    }

    public function addViewer()
    {
        if (! $this->isAdmin()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $userId = (int) $this->request->getPost('user_id');
        if ($userId && ! $this->viewers->isViewer($userId)) {
            $this->viewers->insert(['user_id' => $userId, 'added_by' => $this->uid()]);
            ActivityLog::write('create', 'talent_viewer', (string) $userId, 'tambah viewer talent');
        }
        return redirect()->to('people/talent/viewers')->with('success', 'Viewer ditambahkan.');
    }

    public function removeViewer(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $this->viewers->delete($id);
        ActivityLog::write('delete', 'talent_viewer', (string) $id, 'hapus viewer talent');
        return redirect()->to('people/talent/viewers')->with('success', 'Viewer dihapus.');
    }
}
