<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\WorkInitiativeModel;
use App\Models\WorkInitiativeUpdateModel;
use App\Models\WorkInitiativeCommentModel;

class WorkReportCtrl extends BaseController
{
    private WorkInitiativeModel       $m;
    private WorkInitiativeUpdateModel $mu;
    private WorkInitiativeCommentModel $mc;

    public function __construct()
    {
        $this->m  = new WorkInitiativeModel();
        $this->mu = new WorkInitiativeUpdateModel();
        $this->mc = new WorkInitiativeCommentModel();
    }

    // ── Routing entry point ──────────────────────────────────────────────
    // Redirect ke view yang sesuai jabatan
    public function index(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        // Admin → admin overview
        if ($this->isAdmin()) {
            return redirect()->to('/work-report/admin');
        }

        $emp = $this->currentEmployee();

        // GM → GM view
        if ($emp && $this->isGm($emp)) {
            return redirect()->to('/work-report/gm');
        }

        // Deputy → Deputy view
        if ($emp && $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division');
        }

        // Dept Head / User biasa → Dept Head view
        return $this->deptView();
    }

    // ── Dept Head View ───────────────────────────────────────────────────
    private function deptView(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $emp = $this->currentEmployee();
        if (! $emp || ! $emp['dept_id']) {
            return redirect()->to('/')->with('error', 'Akun belum terhubung ke karyawan atau departemen.');
        }

        $deptId = (int) $emp['dept_id'];
        $items  = $this->m->forDeptHead($deptId);
        $db     = \Config\Database::connect();

        $deptInfo  = $db->table('departments')->where('id', $deptId)->get()->getRowArray();
        $employees = $db->table('employees')
            ->where('dept_id', $deptId)
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()->getResultArray();

        // Load history semua update untuk setiap inisiatif
        $histories = [];
        foreach ($items as $it) {
            $histories[$it['id']] = $this->mu->historyFor((int) $it['id']);
        }

        // Badge unread: komentar dari Deputy (dept_deputy) yang belum Dept Head baca.
        // Kecualikan komentar Dept Head sendiri (thread dua-arah) agar tak jadi badge sendiri.
        $initiativeIds = array_column($items, 'id');
        $myEmpId       = (int) $emp['id'];
        $commentUnread = [];
        if ($initiativeIds) {
            $uid = (int) session()->get('user_id');
            $reads = $db->table('work_initiative_reads')
                ->whereIn('initiative_id', $initiativeIds)
                ->where('user_id', $uid)
                ->get()->getResultArray();
            $readMap = array_column($reads, 'last_read_comment_at', 'initiative_id');

            $rows = $db->table('work_initiative_comments')
                ->select('initiative_id, MAX(created_at) AS latest_at, COUNT(*) AS total')
                ->where('visibility', 'dept_deputy')
                ->where('author_id !=', $myEmpId)
                ->whereIn('initiative_id', $initiativeIds)
                ->groupBy('initiative_id')
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $lastRead = $readMap[$r['initiative_id']] ?? null;
                if (! $lastRead || $r['latest_at'] > $lastRead) {
                    $commentUnread[$r['initiative_id']] = $lastRead
                        ? $db->table('work_initiative_comments')
                            ->where('initiative_id', $r['initiative_id'])
                            ->where('visibility', 'dept_deputy')
                            ->where('author_id !=', $myEmpId)
                            ->where('created_at >', $lastRead)
                            ->countAllResults()
                        : (int) $r['total'];
                }
            }
        }

        return view('work_report/index', [
            'items'          => $items,
            'histories'      => $histories,
            'commentUnread'  => $commentUnread,
            'deptInfo'       => $deptInfo,
            'employees'      => $employees,
            'empId'          => (int) $emp['id'],
        ]);
    }

    // ── Store inisiatif baru (Dept Head) ─────────────────────────────────
    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp = $this->currentEmployee();
        if (! $emp) return redirect()->to('/work-report')->with('error', 'Akun belum terhubung ke karyawan.');

        $data = [
            'dept_id'         => (int) $emp['dept_id'],
            'divisi_id'       => $emp['divisi_id'] ?? null,
            'judul'           => trim($this->request->getPost('judul')),
            'deskripsi'       => trim($this->request->getPost('deskripsi') ?? ''),
            'pic_employee_id' => $this->request->getPost('pic_employee_id') ?: null,
            'target_mulai'    => $this->request->getPost('target_mulai') ?: null,
            'target_selesai'  => $this->request->getPost('target_selesai') ?: null,
            'created_by'      => (int) $emp['id'],
            'is_active'       => 1,
        ];

        if ($data['judul'] === '') {
            return redirect()->to('/work-report')->with('error', 'Judul wajib diisi.');
        }

        $id = $this->m->insert($data);
        ActivityLog::write('create', 'work_initiative', (string) $id, $data['judul']);

        return redirect()->to('/work-report')->with('success', 'Inisiatif berhasil ditambahkan.');
    }

    // ── Edit inisiatif ───────────────────────────────────────────────────
    public function edit(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item) return redirect()->to('/work-report')->with('error', 'Inisiatif tidak ditemukan.');

        // Hanya boleh edit jika: inisiatif milik dept sendiri ATAU di-assign ke deptnya
        if (! $this->canAccessItem($item, $emp)) {
            return redirect()->to('/work-report')->with('error', 'Akses ditolak.');
        }

        $judul = trim($this->request->getPost('judul'));
        if ($judul === '') return redirect()->to('/work-report')->with('error', 'Judul wajib diisi.');

        ActivityLog::captureBefore($item);
        $this->m->update($id, [
            'judul'           => $judul,
            'deskripsi'       => trim($this->request->getPost('deskripsi') ?? ''),
            'pic_employee_id' => $this->request->getPost('pic_employee_id') ?: null,
            'target_mulai'    => $this->request->getPost('target_mulai') ?: null,
            'target_selesai'  => $this->request->getPost('target_selesai') ?: null,
        ]);
        ActivityLog::captureAfter($this->m->find($id));
        ActivityLog::write('update', 'work_initiative', (string) $id, $judul);

        return redirect()->to('/work-report')->with('success', 'Inisiatif diperbarui.');
    }

    // ── Delete inisiatif ─────────────────────────────────────────────────
    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item) return redirect()->to('/work-report')->with('error', 'Inisiatif tidak ditemukan.');

        // Hanya boleh hapus jika created_by user ini
        if ((int) $item['created_by'] !== (int) $emp['id'] && ! $this->isAdmin()) {
            return redirect()->to('/work-report')->with('error', 'Hanya pembuat inisiatif yang bisa menghapus.');
        }

        $this->m->update($id, ['is_active' => 0]);
        ActivityLog::write('delete', 'work_initiative', (string) $id, $item['judul']);

        return redirect()->to('/work-report')->with('success', 'Inisiatif dihapus.');
    }

    // ── Tambah update status (Senin report) ──────────────────────────────
    public function addUpdate(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->canAccessItem($item, $emp)) {
            return redirect()->to('/work-report')->with('error', 'Akses ditolak.');
        }

        $status = $this->request->getPost('status');
        $valid  = ['on_track', 'at_risk', 'delayed', 'done', 'cancelled'];
        if (! in_array($status, $valid)) {
            return redirect()->to('/work-report')->with('error', 'Status tidak valid.');
        }

        $pct = $this->request->getPost('progress_pct');
        $this->mu->insert([
            'initiative_id' => $id,
            'status'        => $status,
            'progress_pct'  => ($pct !== '' && $pct !== null) ? max(0, min(100, (int) $pct)) : null,
            'catatan'       => trim($this->request->getPost('catatan') ?? ''),
            'hambatan'      => trim($this->request->getPost('hambatan') ?? ''),
            'updated_by'    => (int) $emp['id'],
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('update', 'work_initiative', (string) $id, 'Update status: ' . $status);

        return redirect()->to('/work-report')->with('success', 'Update progress disimpan.');
    }

    // ── Komentar Dept Head → Deputy (thread dua-arah dept_deputy) ────────
    public function addComment(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->canAccessItem($item, $emp)) {
            return redirect()->to('/work-report')->with('error', 'Akses ditolak.');
        }

        $body = trim($this->request->getPost('body') ?? '');
        if ($body === '') {
            return redirect()->to('/work-report/' . $id . '/detail')->with('error', 'Komentar tidak boleh kosong.');
        }

        $this->mc->insert([
            'initiative_id' => $id,
            'parent_id'     => null,
            'body'          => $body,
            'author_id'     => (int) $emp['id'],
            'visibility'    => 'dept_deputy',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::write('create', 'work_initiative', (string) $id, 'Komentar ke Deputy: ' . $item['judul']);

        return redirect()->to('/work-report/' . $id . '/detail#komentar')->with('success', 'Komentar terkirim ke Deputy.');
    }

    // ── Detail: history update + komentar Deputy ─────────────────────────
    public function detail(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->canAccessItem($item, $emp)) {
            return redirect()->to('/work-report')->with('error', 'Akses ditolak.');
        }

        $db       = \Config\Database::connect();
        $deptInfo = $db->table('departments')->where('id', $item['dept_id'])->get()->getRowArray();
        $history  = $this->mu->historyFor($id);
        $comments = $this->mc->deptDeputyComments($id);

        // Mark komentar Deputy sebagai terbaca
        $uid = (int) session()->get('user_id');
        $now = date('Y-m-d H:i:s');
        $existing = $db->table('work_initiative_reads')
            ->where('initiative_id', $id)->where('user_id', $uid)->get()->getRowArray();
        if ($existing) {
            $db->table('work_initiative_reads')
                ->where('initiative_id', $id)->where('user_id', $uid)
                ->update(['last_read_comment_at' => $now]);
        } else {
            $db->table('work_initiative_reads')
                ->insert(['initiative_id' => $id, 'user_id' => $uid, 'last_read_comment_at' => $now]);
        }

        return view('work_report/detail', [
            'item'     => $item,
            'deptInfo' => $deptInfo,
            'history'  => $history,
            'comments' => $comments,
            'empId'    => (int) $emp['id'],
        ]);
    }

    // ── Admin Overview ───────────────────────────────────────────────────
    public function admin(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/work-report')->with('error', 'Akses ditolak.');
        }

        $db      = \Config\Database::connect();
        $divisiId = (int) ($this->request->getGet('divisi_id') ?? 0) ?: null;
        $deptId   = (int) ($this->request->getGet('dept_id') ?? 0) ?: null;

        $items    = $this->m->forAdmin($divisiId, $deptId);
        $divisis  = $db->table('divisions')->orderBy('nama')->get()->getResultArray();
        $depts    = $db->table('departments')->where('is_outsource', 0)->orderBy('name')->get()->getResultArray();

        // Kelompokkan per divisi → dept
        $grouped = [];
        foreach ($items as $item) {
            $div  = $item['divisi_name'] ?? 'Tanpa Divisi';
            $dept = $item['dept_name']   ?? 'Tanpa Dept';
            $grouped[$div][$dept][] = $item;
        }
        ksort($grouped);

        return view('work_report/admin', [
            'grouped'    => $grouped,
            'divisis'    => $divisis,
            'depts'      => $depts,
            'filterDiv'  => $divisiId,
            'filterDept' => $deptId,
            'total'      => count($items),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function currentEmployee(): ?array
    {
        $uid = session()->get('user_id');
        if (! $uid) return null;
        return \Config\Database::connect()
            ->table('employees')
            ->select('employees.*,
                d.name AS dept_name,
                COALESCE(dv_direct.id, dv_dept.id) AS divisi_id,
                COALESCE(dv_direct.nama, dv_dept.nama) AS divisi_name,
                j.grade, j.nama AS jabatan_nama')
            ->join('departments d', 'd.id = employees.dept_id', 'left')
            ->join('divisions dv_dept', 'dv_dept.id = d.division_id', 'left')
            ->join('divisions dv_direct', 'dv_direct.id = employees.division_id', 'left')
            ->join('jabatans j', 'j.id = employees.jabatan_id', 'left')
            ->where('employees.user_id', $uid)
            ->get()->getRowArray();
    }

    private function isGm(array $emp): bool
    {
        return str_contains(strtolower($emp['jabatan_nama'] ?? ''), 'general manager');
    }

    private function isDeputy(array $emp): bool
    {
        // Deputy GM (grade 3) ATAU manajer tingkat divisi: punya divisi tapi tanpa
        // departemen (mis. Senior Manager pembina divisi) → dapat view se-divisi.
        if ((int) ($emp['grade'] ?? 99) === 3) return true;
        return empty($emp['dept_id']) && ! empty($emp['divisi_id']);
    }

    private function canAccessItem(array $item, ?array $emp): bool
    {
        if ($this->isAdmin() || ! $emp) return $this->isAdmin();
        $deptId = (int) $emp['dept_id'];
        return (int) $item['dept_id'] === $deptId || (int) ($item['assigned_to_dept_id'] ?? 0) === $deptId;
    }
}
