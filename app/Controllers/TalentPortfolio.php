<?php

namespace App\Controllers;

use App\Models\TalentPeriodModel;
use App\Models\TalentPlacementModel;
use App\Models\TalentViewerModel;
use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Libraries\AppraisalChain;
use App\Libraries\ActivityLog;

/**
 * Talent Portfolio (9-Box) — Performance × Potential.
 * Input berjenjang (rantai atasan, reuse AppraisalChain): atasan langsung isi →
 * naik rantai → tingkatan tertinggi verifikasi (final) → HR lock periode.
 * Peta penuh hanya untuk admin + daftar viewer (dikelola admin).
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

    // ── Akses ────────────────────────────────────────────────────────────────
    private function uid(): int { return (int) session()->get('user_id'); }
    private function isHr(): bool { return $this->isAdmin() || $this->canEditMenu('hr_main'); }
    private function canViewMap(): bool { return $this->isAdmin() || $this->viewers->isViewer($this->uid()); }
    /** Batas tanggal masuk untuk kecualikan probation < 6 bulan. */
    private function cutoff(): string { return date('Y-m-d', strtotime('-6 months')); }

    // ── 1. PETA 9-BOX (viewer/admin) ──────────────────────────────────────────
    public function index()
    {
        if (! $this->canViewMap()) {
            // Bukan viewer peta: arahkan ke fungsi yang sesuai perannya.
            if ($this->isHr()) return redirect()->to('people/talent/periods');
            if (! empty($this->placements->inboxFor($this->uid()))) return redirect()->to('people/talent/input');
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $periods   = $this->periods->allPeriods();
        $periodId  = (int) ($this->request->getGet('period') ?: ($periods[0]['id'] ?? 0));
        $ftype     = $this->request->getGet('ftype') === 'jabatan' ? 'jabatan' : 'dept';
        $fid       = (int) $this->request->getGet('fid');

        $db        = db_connect();
        $depts     = $db->table('departments')->where('(is_outsource IS NULL OR is_outsource=0)')->orderBy('name')->get()->getResultArray();
        $jabatans  = $db->table('jabatans')->orderBy('grade')->orderBy('nama')->get()->getResultArray();

        $grid = $dist = [];
        $chosen = $periodId && $fid;
        if ($chosen) {
            $grid = $this->placements->gridData($periodId, $ftype, $fid, $this->cutoff());
            $dist = $this->placements->distribution($periodId, $ftype, $fid, $this->cutoff());
        }

        return view('people/talent/index', [
            'user'      => $this->currentUser(),
            'periods'   => $periods,
            'periodId'  => $periodId,
            'ftype'     => $ftype,
            'fid'       => $fid,
            'depts'     => $depts,
            'jabatans'  => $jabatans,
            'grid'      => $grid,
            'dist'      => $dist,
            'chosen'    => $chosen,
            'isHr'      => $this->isHr(),
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // ── 2. INPUT / INBOX PENILAI (atasan) ─────────────────────────────────────
    public function input()
    {
        $inbox = $this->placements->inboxFor($this->uid());
        // Tandai apakah pelaku = puncak rantai (tak ada atasan berikut) → tombol "Verifikasi", bukan "Teruskan".
        $chain = new AppraisalChain();
        foreach ($inbox as &$it) {
            $next = $chain->nextActorAfter($this->uid(), (int) $it['employee_id']);
            $it['is_top'] = empty($next['user_id'] ?? null);
        }
        unset($it);
        // HR/admin juga bisa menangani placement chain-broken (current_actor NULL) di periode aktif.
        $hrPending = [];
        if ($this->isHr()) {
            $active = $this->periods->activePeriod();
            if ($active) {
                $hrPending = db_connect()->table('talent_placements tp')
                    ->select('tp.*, e.nama AS employee_nama, j.nama AS jabatan')
                    ->join('employees e', 'e.id = tp.employee_id', 'left')
                    ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
                    ->where('tp.period_id', $active['id'])
                    ->where('tp.current_actor_id IS NULL')
                    ->whereIn('tp.status', ['input', 'in_review'])
                    ->orderBy('e.nama')->get()->getResultArray();
            }
        }
        if (empty($inbox) && empty($hrPending) && ! $this->isHr() && ! $this->canViewMap()) {
            return redirect()->to('/')->with('error', 'Tidak ada penilaian talent untuk Anda.');
        }
        return view('people/talent/input', [
            'user'      => $this->currentUser(),
            'inbox'     => $inbox,
            'hrPending' => $hrPending,
            'isHr'      => $this->isHr(),
            'canViewMap'=> $this->canViewMap(),
        ]);
    }

    /** Simpan skor + teruskan/verifikasi satu placement. */
    public function save(int $placementId)
    {
        $p = $this->placements->find($placementId);
        if (! $p) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        // Periode terkunci → tolak.
        $period = $this->periods->find($p['period_id']);
        if (! $period || $period['status'] === 'locked') return redirect()->back()->with('error', 'Periode sudah dikunci.');

        // Otorisasi: giliran user ini (current_actor), atau HR/admin (untuk chain-broken / fallback).
        $isActor = ((int) $p['current_actor_id'] === $this->uid()) && $p['status'] !== 'verified';
        if (! $isActor && ! $this->isHr()) return redirect()->back()->with('error', 'Bukan giliran Anda.');

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
        if (empty($p['placed_by'])) $data['placed_by'] = $this->uid();

        $action = $post['action'] ?? 'save'; // save | forward
        if ($action === 'forward') {
            $chain = new AppraisalChain();
            // Atasan berikutnya di atas pelaku saat ini (null bila pelaku = puncak / HR fallback).
            $next  = $chain->nextActorAfter($this->uid(), (int) $p['employee_id']);
            if ($next && ! empty($next['user_id'])) {
                $data['status']           = 'in_review';
                $data['current_actor_id'] = (int) $next['user_id'];
            } else {
                // Tidak ada atasan berikutnya → user ini tingkatan tertinggi → VERIFIKASI (final).
                $data['status']           = 'verified';
                $data['current_actor_id'] = null;
                $data['verified_by']      = $this->uid();
                $data['verified_at']      = date('Y-m-d H:i:s');
            }
        }
        $this->placements->update($placementId, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'talent_placement', (string) $placementId, 'talent: ' . ($action === 'forward' ? ($data['status'] ?? '') : 'simpan'));

        return redirect()->to('people/talent/input')->with('success', $action === 'forward' ? 'Penilaian diteruskan.' : 'Penilaian disimpan.');
    }

    // ── 3. KELOLA PERIODE (HR/admin) ──────────────────────────────────────────
    public function periods()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $rows = $this->periods->allPeriods();
        // hitung ringkas jumlah placement per periode
        $db = db_connect();
        foreach ($rows as &$r) {
            $r['n_placed']   = $db->table('talent_placements')->where('period_id', $r['id'])->where('performance IS NOT NULL')->countAllResults();
            $r['n_total']    = $db->table('talent_placements')->where('period_id', $r['id'])->countAllResults();
        }
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

    /** Aktifkan periode → generate placement untuk semua karyawan dalam cakupan + tetapkan penilai awal. */
    public function activatePeriod(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $period = $this->periods->find($id);
        if (! $period || $period['status'] === 'locked') return redirect()->back()->with('error', 'Periode tidak valid.');

        $chain = new AppraisalChain();
        $ids   = $this->placements->eligibleIds($this->cutoff());
        $created = 0;
        foreach ($ids as $eid) {
            if ($this->placements->getOne($id, $eid)) continue; // sudah ada
            $first = $chain->firstActor($eid);
            $this->placements->insert([
                'period_id'        => $id,
                'employee_id'      => $eid,
                'status'           => 'input',
                'current_actor_id' => $first['user_id'] ?? null, // null = chain putus → ditangani HR
            ]);
            $created++;
        }
        $this->periods->update($id, ['status' => 'active']);
        ActivityLog::write('update', 'talent_period', (string) $id, $period['nama'] . ' — aktif, generate ' . $created . ' placement');
        return redirect()->to('people/talent/periods')->with('success', "Periode aktif. {$created} karyawan siap dinilai (penilai awal = atasan langsung).");
    }

    public function lockPeriod(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $period = $this->periods->find($id);
        if (! $period) return redirect()->back()->with('error', 'Periode tidak ditemukan.');
        db_connect()->table('talent_placements')->where('period_id', $id)->update(['status' => 'locked']);
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
