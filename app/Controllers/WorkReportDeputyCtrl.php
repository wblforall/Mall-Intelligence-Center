<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\WorkInitiativeModel;
use App\Models\WorkInitiativeUpdateModel;
use App\Models\WorkInitiativeFlagModel;
use App\Models\WorkInitiativeCommentModel;

class WorkReportDeputyCtrl extends BaseController
{
    private WorkInitiativeModel        $m;
    private WorkInitiativeUpdateModel  $mu;
    private WorkInitiativeFlagModel    $mf;
    private WorkInitiativeCommentModel $mc;

    public function __construct()
    {
        $this->m  = new WorkInitiativeModel();
        $this->mu = new WorkInitiativeUpdateModel();
        $this->mf = new WorkInitiativeFlagModel();
        $this->mc = new WorkInitiativeCommentModel();
    }

    // ── Halaman utama Deputy ─────────────────────────────────────────────
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp = $this->currentEmployee();
        if (! $emp || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report')->with('error', 'Halaman ini hanya untuk Deputy.');
        }

        $db      = \Config\Database::connect();
        $divisi  = $db->table('divisions')->where('id', $emp['divisi_id'])->get()->getRowArray();
        $depts   = $db->table('departments')
            ->where('division_id', $emp['divisi_id'])
            ->where('is_outsource', 0)
            ->orderBy('name')
            ->get()->getResultArray();

        $items = $this->m->forDivision((int) $emp['divisi_id']);

        // Kelompokkan per dept
        $byDept = [];
        foreach ($items as $item) {
            $byDept[$item['dept_id']][] = $item;
        }

        // Inisiatif milik Deputy sendiri (tidak punya dept_id dari org — assigned_to_dept_id = null)
        $ownItems = array_filter($items, fn($i) => (int)$i['created_by'] === (int)$emp['id'] && ! $i['assigned_to_dept_id']);

        return view('work_report/division', [
            'items'    => $items,
            'byDept'   => $byDept,
            'ownItems' => array_values($ownItems),
            'depts'    => $depts,
            'divisi'   => $divisi,
            'emp'      => $emp,
        ]);
    }

    // ── Store inisiatif oleh Deputy ───────────────────────────────────────
    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp = $this->currentEmployee();
        if (! $emp || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        $db          = \Config\Database::connect();
        $assignDeptId = $this->request->getPost('assigned_to_dept_id') ?: null;

        // Tentukan dept_id: jika di-assign ke dept → pakai dept itu; jika tidak → pakai dept Deputy sendiri
        $deptId = $assignDeptId
            ? (int) $assignDeptId
            : (int) ($emp['dept_id'] ?? 0);

        $judul = trim($this->request->getPost('judul'));
        if ($judul === '') {
            return redirect()->to('/work-report/division')->with('error', 'Judul wajib diisi.');
        }

        $data = [
            'dept_id'             => $deptId,
            'divisi_id'           => (int) $emp['divisi_id'],
            'judul'               => $judul,
            'deskripsi'           => trim($this->request->getPost('deskripsi') ?? ''),
            'pic_employee_id'     => $this->request->getPost('pic_employee_id') ?: null,
            'target_mulai'        => $this->request->getPost('target_mulai') ?: null,
            'target_selesai'      => $this->request->getPost('target_selesai') ?: null,
            'assigned_to_dept_id' => $assignDeptId ? (int) $assignDeptId : null,
            'created_by'          => (int) $emp['id'],
            'is_active'           => 1,
        ];

        $id = $this->m->insert($data);
        ActivityLog::write('create', 'work_initiative', (string) $id, $judul . ($assignDeptId ? ' [assign ke dept #' . $assignDeptId . ']' : ''));

        return redirect()->to('/work-report/division')->with('success', 'Inisiatif ditambahkan.');
    }

    // ── Edit inisiatif (Deputy bisa edit inisiatifnya sendiri) ────────────
    public function edit(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item) return redirect()->to('/work-report/division')->with('error', 'Tidak ditemukan.');

        // Deputy hanya bisa edit inisiatif buatannya sendiri atau dalam divisinya
        if (! $this->isAdmin() && (int) $item['divisi_id'] !== (int) $emp['divisi_id']) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        $judul = trim($this->request->getPost('judul'));
        if ($judul === '') return redirect()->to('/work-report/division')->with('error', 'Judul wajib diisi.');

        ActivityLog::captureBefore($item);
        $this->m->update($id, [
            'judul'               => $judul,
            'deskripsi'           => trim($this->request->getPost('deskripsi') ?? ''),
            'pic_employee_id'     => $this->request->getPost('pic_employee_id') ?: null,
            'target_mulai'        => $this->request->getPost('target_mulai') ?: null,
            'target_selesai'      => $this->request->getPost('target_selesai') ?: null,
            'assigned_to_dept_id' => $this->request->getPost('assigned_to_dept_id') ?: null,
        ]);
        ActivityLog::captureAfter($this->m->find($id));
        ActivityLog::write('update', 'work_initiative', (string) $id, $judul);

        return redirect()->to('/work-report/division')->with('success', 'Inisiatif diperbarui.');
    }

    // ── Flag / Unflag untuk GM ────────────────────────────────────────────
    public function flag(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        if ((int) $item['divisi_id'] !== (int) $emp['divisi_id'] && ! $this->isAdmin()) {
            return redirect()->to('/work-report/division')->with('error', 'Inisiatif bukan dari divisi Anda.');
        }

        $uid     = session()->get('user_id');
        $flagged = $this->mf->toggle($id, $uid);
        ActivityLog::write('update', 'work_initiative', (string) $id,
            ($flagged ? 'Flag ke GM: ' : 'Unflag dari GM: ') . $item['judul']);

        return redirect()->to('/work-report/division')->with('success',
            $flagged ? 'Inisiatif ditampilkan di halaman GM.' : 'Inisiatif disembunyikan dari GM.');
    }

    // ── Komentar Deputy → Dept ────────────────────────────────────────────
    public function addComment(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        $body = trim($this->request->getPost('body') ?? '');
        if ($body === '') return redirect()->to('/work-report/division')->with('error', 'Komentar tidak boleh kosong.');

        $this->mc->insert([
            'initiative_id' => $id,
            'parent_id'     => null,
            'body'          => $body,
            'author_id'     => (int) $emp['id'],
            'visibility'    => 'dept_deputy',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/work-report/division#initiative-' . $id)->with('success', 'Komentar dikirim.');
    }

    // ── Balas catatan GM ──────────────────────────────────────────────────
    public function replyGm(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        $body     = trim($this->request->getPost('body') ?? '');
        $parentId = $this->request->getPost('parent_id') ?: null;
        if ($body === '') return redirect()->to('/work-report/division')->with('error', 'Balasan tidak boleh kosong.');

        $this->mc->insert([
            'initiative_id' => $id,
            'parent_id'     => $parentId,
            'body'          => $body,
            'author_id'     => (int) $emp['id'],
            'visibility'    => 'gm_deputy',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/work-report/division#initiative-' . $id)->with('success', 'Balasan dikirim ke GM.');
    }

    // ── Detail inisiatif (Deputy) ─────────────────────────────────────────
    public function detail(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);
        if (! $item || ! $this->isDeputy($emp)) {
            return redirect()->to('/work-report/division')->with('error', 'Akses ditolak.');
        }

        $db       = \Config\Database::connect();
        $history  = $this->mu->historyFor($id);
        $deptComments = $this->mc->deptDeputyComments($id);
        $gmThread     = $this->mc->gmDeputyThread($id);
        $isFlagged    = $this->mf->isFlagged($id);
        $depts        = $db->table('departments')
            ->where('division_id', $emp['divisi_id'])
            ->where('is_outsource', 0)
            ->get()->getResultArray();

        return view('work_report/deputy_detail', [
            'item'         => $item,
            'history'      => $history,
            'deptComments' => $deptComments,
            'gmThread'     => $gmThread,
            'isFlagged'    => $isFlagged,
            'emp'          => $emp,
            'depts'        => $depts,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function currentEmployee(): ?array
    {
        $uid = session()->get('user_id');
        if (! $uid) return null;
        return \Config\Database::connect()
            ->table('employees')
            ->select('employees.*, d.name AS dept_name, d.division_id AS divisi_id, dv.nama AS divisi_name, j.grade, j.nama AS jabatan_nama')
            ->join('departments d', 'd.id = employees.dept_id', 'left')
            ->join('divisions dv', 'dv.id = d.division_id', 'left')
            ->join('jabatans j', 'j.id = employees.jabatan_id', 'left')
            ->where('employees.user_id', $uid)
            ->get()->getRowArray();
    }

    private function isDeputy(array $emp): bool
    {
        return (int) ($emp['grade'] ?? 99) === 3;
    }
}
