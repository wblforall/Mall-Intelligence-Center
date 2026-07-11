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
        $tab    = (string) $this->request->getGet('tab');
        $scope  = in_array($tab, ['archived', 'deleted'], true) ? $tab : 'active';
        $items  = $this->m->forDeptHead($deptId, $scope);
        $counts = [];
        foreach (['active', 'archived', 'deleted'] as $s) {
            $counts[$s] = $s === $scope ? count($items) : count($this->m->forDeptHead($deptId, $s));
        }
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
            'scope'          => $scope,
            'scopeCounts'    => $counts,
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
        if ((int) $item['created_by'] !== (int) ($emp['id'] ?? 0) && ! $this->isAdmin()) {
            return redirect()->to('/work-report')->with('error', 'Hanya pembuat inisiatif yang bisa menghapus.');
        }

        $this->m->update($id, [
            'is_active'  => 0,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => (int) session()->get('user_id'),
        ]);
        ActivityLog::write('delete', 'work_initiative', (string) $id, $item['judul'], [
            'dihapus_oleh' => session()->get('name'),
        ]);

        return redirect()->to('/work-report')->with('success', 'Program kerja dipindahkan ke tab Dihapus.');
    }

    // ── Arsip / batal arsip / pulihkan ───────────────────────────────────
    public function archive(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        return $this->setArchived($id, true);
    }

    public function unarchive(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        return $this->setArchived($id, false);
    }

    private function setArchived(int $id, bool $archive): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $item = $this->m->find($id);
        if (! $item || ! $item['is_active']) {
            return redirect()->back()->with('error', 'Program kerja tidak ditemukan.');
        }
        if (! $this->canManageItem($item)) {
            return redirect()->back()->with('error', 'Anda tidak berhak mengarsipkan program kerja ini.');
        }

        $this->m->update($id, [
            'archived_at' => $archive ? date('Y-m-d H:i:s') : null,
            'archived_by' => $archive ? (int) session()->get('user_id') : null,
        ]);
        ActivityLog::write($archive ? 'archive' : 'unarchive', 'work_initiative', (string) $id, $item['judul']);

        return redirect()->back()->with('success', $archive ? 'Program kerja diarsipkan.' : 'Program kerja dikembalikan dari arsip.');
    }

    // Pulihkan program yang dihapus (admin only) — kembali ke daftar aktif.
    public function restore(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/work-report')->with('error', 'Hanya admin yang bisa memulihkan.');
        }

        $item = $this->m->find($id);
        if (! $item || $item['is_active']) {
            return redirect()->back()->with('error', 'Program kerja tidak ditemukan di tab Dihapus.');
        }

        $this->m->update($id, [
            'is_active'  => 1,
            'deleted_at' => null,
            'deleted_by' => null,
        ]);
        ActivityLog::write('restore', 'work_initiative', (string) $id, $item['judul']);

        return redirect()->back()->with('success', 'Program kerja dipulihkan.');
    }

    // Boleh kelola arsip: admin, pembuat, dept head pemilik program, atau
    // Deputy/manajer divisi untuk program di divisinya.
    private function canManageItem(array $item): bool
    {
        if ($this->isAdmin()) return true;
        $emp = $this->currentEmployee();
        if (! $emp) return false;
        if ((int) $item['created_by'] === (int) $emp['id']) return true;
        if ($this->canAccessItem($item, $emp)) return true;
        return $this->isDeputy($emp)
            && (int) ($item['divisi_id'] ?? 0) === (int) ($emp['divisi_id'] ?? 0)
            && (int) ($item['divisi_id'] ?? 0) !== 0;
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

        // Validasi foto bukti dulu (khusus gambar) sebelum ada yang disimpan.
        $images = array_filter(
            $this->request->getFileMultiple('images') ?? [],
            fn($f) => $f && $f->getError() !== UPLOAD_ERR_NO_FILE
        );
        if (count($images) > 5) {
            return redirect()->to('/work-report')->with('error', 'Maksimal 5 foto per update.');
        }
        foreach ($images as $file) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 5)) {
                return redirect()->to('/work-report')->with('error', 'Foto bukti: ' . $err);
            }
        }

        $pct = $this->request->getPost('progress_pct');
        $updateId = $this->mu->insert([
            'initiative_id' => $id,
            'status'        => $status,
            'progress_pct'  => ($pct !== '' && $pct !== null) ? max(0, min(100, (int) $pct)) : null,
            'catatan'       => trim($this->request->getPost('catatan') ?? ''),
            'hambatan'      => trim($this->request->getPost('hambatan') ?? ''),
            'updated_by'    => (int) $emp['id'],
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if ($images) {
            $uploadPath = FCPATH . 'uploads/work_report/' . $id . '/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $db = \Config\Database::connect();
            foreach ($images as $file) {
                $name = 'upd_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
                $file->move($uploadPath, $name);
                $db->table('work_initiative_update_images')->insert([
                    'update_id'     => (int) $updateId,
                    'initiative_id' => $id,
                    'file_name'     => $name,
                    'original_name' => $file->getClientName(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        ActivityLog::write('update', 'work_initiative', (string) $id, 'Update status: ' . $status,
            $images ? ['foto' => count($images)] : []);

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
        $tab      = (string) $this->request->getGet('tab');
        $scope    = in_array($tab, ['archived', 'deleted'], true) ? $tab : 'active';

        $items    = $this->m->forAdmin($divisiId, $deptId, $scope);
        $counts   = [];
        foreach (['active', 'archived', 'deleted'] as $s) {
            $counts[$s] = $s === $scope ? count($items) : count($this->m->forAdmin($divisiId, $deptId, $s));
        }
        $divisis  = $db->table('divisions')->orderBy('nama')->get()->getResultArray();
        $depts    = $db->table('departments')->where('is_outsource', 0)->orderBy('name')->get()->getResultArray();

        // Kelompokkan per divisi → dept. Program tanpa dept = program level divisi
        // (dibuat Deputy tanpa assign ke dept) — selalu tampil paling atas di divisinya.
        $grouped = [];
        foreach ($items as $item) {
            $div  = $item['divisi_name'] ?? 'Tanpa Divisi';
            $dept = $item['dept_name']   ?? 'Program Level Divisi';
            $grouped[$div][$dept][] = $item;
        }
        ksort($grouped);
        foreach ($grouped as $div => $deptGroups) {
            if (isset($deptGroups['Program Level Divisi'])) {
                $grouped[$div] = ['Program Level Divisi' => $deptGroups['Program Level Divisi']]
                    + $deptGroups;
            }
        }

        return view('work_report/admin', [
            'items'       => $items,
            'grouped'     => $grouped,
            'divisis'     => $divisis,
            'depts'       => $depts,
            'filterDiv'   => $divisiId,
            'filterDept'  => $deptId,
            'total'       => count($items),
            'scope'       => $scope,
            'scopeCounts' => $counts,
        ]);
    }

    // ── Dashboard rekap (admin & GM semua divisi; Deputy divisinya) ──────
    public function dashboard(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp      = $this->currentEmployee();
        $isAdmin  = $this->isAdmin();
        $isGm     = $emp && $this->isGm($emp);
        $isDeputy = $emp && $this->isDeputy($emp);
        $isDeptHead = $emp && ! empty($emp['dept_id']);
        if (! $isAdmin && ! $isGm && ! $isDeputy && ! $isDeptHead) {
            return redirect()->to('/work-report')->with('error', 'Akun belum terhubung ke karyawan atau departemen.');
        }

        // Scope: admin & GM semua divisi (bisa filter); Deputy divisinya; Dept Head dept-nya.
        $divisiId = null;
        $deptId   = null;
        if ($isAdmin || $isGm) {
            $divisiId = (int) ($this->request->getGet('divisi_id') ?? 0) ?: null;
        } elseif ($isDeputy) {
            $divisiId = (int) $emp['divisi_id'];
        } else {
            $deptId = (int) $emp['dept_id'];
        }

        $items = $this->m->forAdmin($divisiId, $deptId, 'active');
        $today = date('Y-m-d');

        // ── Stat & agregat per divisi + freshness per dept — sekali loop ──
        $stat = ['total' => count($items), 'on_track' => 0, 'at_risk' => 0, 'delayed' => 0,
                 'done' => 0, 'cancelled' => 0, 'no_update' => 0, 'overdue' => 0, 'stale7' => 0];
        $byDivisi = [];
        $byDept   = [];
        foreach ($items as $it) {
            $st = $it['latest_status'] ?? null;
            if ($st === null) $stat['no_update']++;
            elseif (isset($stat[$st])) $stat[$st]++;

            $overdue = ! empty($it['target_selesai']) && $it['target_selesai'] < $today
                && $st !== 'done' && $st !== 'cancelled';
            if ($overdue) $stat['overdue']++;

            $stale7 = ! empty($it['latest_updated_at'])
                && strtotime($it['latest_updated_at']) < strtotime('-7 days')
                && $st !== 'done' && $st !== 'cancelled';
            if ($stale7) $stat['stale7']++;

            $dv = $it['divisi_name'] ?? 'Tanpa Divisi';
            if (! isset($byDivisi[$dv])) {
                $byDivisi[$dv] = ['on_track' => 0, 'at_risk' => 0, 'delayed' => 0, 'done' => 0, 'cancelled' => 0, 'no_update' => 0];
            }
            $byDivisi[$dv][$st !== null && isset($byDivisi[$dv][$st]) ? $st : 'no_update']++;

            $dpKey = ($it['divisi_name'] ?? '—') . ' · ' . ($it['dept_name'] ?? 'Program Level Divisi');
            if (! isset($byDept[$dpKey])) {
                $byDept[$dpKey] = ['divisi' => $it['divisi_name'] ?? '—',
                                   'dept' => $it['dept_name'] ?? 'Program Level Divisi',
                                   'total' => 0, 'last_update' => null, 'overdue' => 0];
            }
            $byDept[$dpKey]['total']++;
            if ($overdue) $byDept[$dpKey]['overdue']++;
            if (! empty($it['latest_updated_at'])
                && ($byDept[$dpKey]['last_update'] === null || $it['latest_updated_at'] > $byDept[$dpKey]['last_update'])) {
                $byDept[$dpKey]['last_update'] = $it['latest_updated_at'];
            }
        }
        ksort($byDivisi);
        ksort($byDept);

        // ── Tren mingguan: update yang dilaporkan per minggu (12 minggu) ──
        $db   = \Config\Database::connect();
        $trendQ = $db->table('work_initiative_updates u')
            ->select("YEARWEEK(u.created_at, 1) AS yw, u.status, COUNT(*) AS c")
            ->join('work_initiatives wi', 'wi.id = u.initiative_id')
            ->where('wi.is_active', 1)
            ->where('u.created_at >=', date('Y-m-d', strtotime('monday this week -11 weeks')));
        if ($divisiId) $trendQ->where('wi.divisi_id', $divisiId);
        if ($deptId)   $trendQ->where('wi.dept_id', $deptId);
        $trendRows = $trendQ->groupBy('yw, u.status')->orderBy('yw')->get()->getResultArray();

        $trendMap = [];
        foreach ($trendRows as $r) $trendMap[$r['yw']][$r['status']] = (int) $r['c'];

        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $mon = strtotime("monday this week -{$i} weeks");
            $yw  = date('oW', $mon); // ISO year+week, sama dengan YEARWEEK(...,1)
            $weeks[] = ['label' => date('d M', $mon), 'data' => $trendMap[$yw] ?? []];
        }

        $divisis = ($isAdmin || $isGm)
            ? $db->table('divisions')->orderBy('nama')->get()->getResultArray()
            : [];

        $scopedLabel = null;
        if (! $isAdmin && ! $isGm) {
            $scopedLabel = $isDeputy
                ? ($emp['divisi_name'] ?? '')
                : ($emp['dept_name'] ?? '');
        }

        return view('work_report/dashboard', [
            'stat'      => $stat,
            'byDivisi'  => $byDivisi,
            'byDept'    => $byDept,
            'weeks'     => $weeks,
            'divisis'   => $divisis,
            'filterDiv' => $divisiId,
            'scopedDiv' => $scopedLabel,
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
