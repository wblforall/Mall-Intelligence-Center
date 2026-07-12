<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeModel extends Model
{
    protected $table         = 'work_initiatives';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'dept_id', 'divisi_id', 'judul', 'deskripsi', 'pic_employee_id',
        'target_mulai', 'target_selesai', 'assigned_to_dept_id', 'created_by', 'is_active',
        'archived_at', 'archived_by', 'auto_archive_exempt', 'deleted_at', 'deleted_by',
    ];

    // Program dianggap arsip bila diarsip manual ATAU auto: status terakhir
    // done/cancelled dan update terakhirnya lebih tua dari 30 hari.
    public const AUTO_ARCHIVE_DAYS = 30;

    // Label grup untuk program buatan Deputy tanpa dept (dept_name NULL).
    public const DIVISION_LEVEL_LABEL = 'Program Level Divisi';

    // COALESCE wajib: u_latest NULL (belum pernah update) harus dievaluasi FALSE,
    // bukan NULL — tanpa ini baris tanpa update hilang dari scope aktif (NOT NULL = NULL).
    // auto_archive_exempt = penanda Batal Arsip/Pulihkan agar rule auto tidak
    // langsung menangkap item yang sama lagi (di-reset saat ada update baru).
    private const AUTO_ARCHIVED_SQL = "(work_initiatives.archived_at IS NOT NULL
        OR (work_initiatives.auto_archive_exempt = 0
            AND COALESCE(u_latest.status, '') IN ('done','cancelled')
            AND u_latest.created_at < DATE_SUB(NOW(), INTERVAL " . self::AUTO_ARCHIVE_DAYS . " DAY)))";

    /**
     * Terapkan scope daftar: 'active' (default), 'archived', 'deleted'.
     * Semua query daftar sudah punya join u_latest yang dibutuhkan scope arsip.
     */
    private function applyScope($builder, string $scope)
    {
        if ($scope === 'deleted') {
            return $builder->where('work_initiatives.is_active', 0);
        }
        $builder->where('work_initiatives.is_active', 1);
        if ($scope === 'archived') {
            return $builder->where(self::AUTO_ARCHIVED_SQL, null, false);
        }
        return $builder->where('NOT ' . self::AUTO_ARCHIVED_SQL, null, false);
    }

    // Semua inisiatif yang bisa dilihat oleh Dept Head:
    // 1. Inisiatif milik dept sendiri (dept_id = X, assigned_to_dept_id IS NULL)
    // 2. Inisiatif yang di-assign Deputy ke dept ini (assigned_to_dept_id = X)
    public function forDeptHead(int $deptId, string $scope = 'active'): array
    {
        $b = $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                u_arc.name AS archived_by_name,
                u_del.name AS deleted_by_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('users u_arc', 'u_arc.id = work_initiatives.archived_by', 'left')
            ->join('users u_del', 'u_del.id = work_initiatives.deleted_by', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->groupStart()
                ->where('work_initiatives.dept_id', $deptId)
                ->orWhere('work_initiatives.assigned_to_dept_id', $deptId)
            ->groupEnd()
            ->orderBy('work_initiatives.created_at', 'DESC');

        return $this->applyScope($b, $scope)->findAll();
    }

    // Semua inisiatif dalam satu divisi (untuk Deputy)
    public function forDivision(int $divisiId, string $scope = 'active'): array
    {
        $b = $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                ad.name AS assigned_dept_name,
                u_arc.name AS archived_by_name,
                u_del.name AS deleted_by_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at,
                (SELECT COUNT(*) FROM work_initiative_flags f WHERE f.initiative_id = work_initiatives.id AND f.is_active = 1) AS is_flagged')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('departments ad', 'ad.id = work_initiatives.assigned_to_dept_id', 'left')
            ->join('users u_arc', 'u_arc.id = work_initiatives.archived_by', 'left')
            ->join('users u_del', 'u_del.id = work_initiatives.deleted_by', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->where('work_initiatives.divisi_id', $divisiId)
            ->orderBy('d.name')
            ->orderBy('work_initiatives.created_at', 'DESC');

        return $this->applyScope($b, $scope)->findAll();
    }

    // Untuk GM: program kerja yang di-flag Deputy, ATAU (Opsi C) program kerja dari
    // divisi yang TIDAK punya Deputy GM → otomatis tampil tanpa perlu di-flag.
    // $deputyDivisionIds = daftar id divisi yang punya Deputy efektif (dihitung di controller).
    public function forGm(array $deputyDivisionIds = [], string $scope = 'active'): array
    {
        $b = $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                ad.name AS assigned_dept_name,
                u_arc.name AS archived_by_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at,
                f.flagged_at, f.flagged_by,
                (f.id IS NOT NULL) AS is_flagged_gm,
                dep_emp.nama AS deputy_name')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('departments ad', 'ad.id = work_initiatives.assigned_to_dept_id', 'left')
            ->join('work_initiative_flags f', 'f.initiative_id = work_initiatives.id AND f.is_active = 1', 'left')
            ->join('users u_dep', 'u_dep.id = f.flagged_by', 'left')
            ->join('employees dep_emp', 'dep_emp.user_id = u_dep.id', 'left')
            ->join('users u_arc', 'u_arc.id = work_initiatives.archived_by', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left');
        $this->gmVisibility($b, $deputyDivisionIds);
        $this->applyScope($b, $scope);

        return $b->orderBy('dv.nama')
            ->orderBy('d.name')
            ->orderBy('work_initiatives.created_at', 'DESC')
            ->findAll();
    }

    // Filter tampilan GM: yang di-flag ATAU dari divisi tanpa Deputy. Bila TIDAK ada
    // satupun divisi ber-Deputy, seluruh program kerja tampil (semua = tanpa Deputy).
    // Builder harus sudah punya join work_initiative_flags dengan alias f.
    private function gmVisibility($builder, array $deputyDivisionIds): void
    {
        if (! empty($deputyDivisionIds)) {
            $ids = implode(',', array_map('intval', $deputyDivisionIds));
            $builder->groupStart()
                ->where('f.id IS NOT NULL')
                ->orWhere("(work_initiatives.divisi_id IS NULL OR work_initiatives.divisi_id NOT IN ($ids))")
              ->groupEnd();
        }
    }

    // Daftar id divisi yang punya Deputy GM efektif (karyawan aktif grade-3 + akun aktif).
    public function divisionsWithDeputy(): array
    {
        $rows = $this->db->table('employees e')
            ->select('COALESCE(e.division_id, d.division_id) AS divid')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = e.user_id', 'inner')
            ->where('e.status', 'aktif')
            ->where('j.grade', 3)
            ->where('u.is_active', 1)
            ->groupBy('divid')
            ->get()->getResultArray();
        return array_values(array_filter(array_map(fn($r) => (int) $r['divid'], $rows)));
    }

    // Semua inisiatif aktif (untuk Admin — lintas dept & divisi)
    public function forAdmin(?int $divisiId = null, ?int $deptId = null, string $scope = 'active'): array
    {
        $q = $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name, cb.nama AS created_by_name,
                ad.name AS assigned_dept_name,
                u_arc.name AS archived_by_name,
                u_del.name AS deleted_by_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at,
                (SELECT COUNT(*) FROM work_initiative_flags f WHERE f.initiative_id = work_initiatives.id AND f.is_active = 1) AS is_flagged')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('departments ad', 'ad.id = work_initiatives.assigned_to_dept_id', 'left')
            ->join('users u_arc', 'u_arc.id = work_initiatives.archived_by', 'left')
            ->join('users u_del', 'u_del.id = work_initiatives.deleted_by', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left');

        $this->orgFilter($q, $divisiId, $deptId);

        return $this->applyScope($q, $scope)
            ->orderBy('dv.nama')->orderBy('d.name')->orderBy('work_initiatives.created_at', 'DESC')->findAll();
    }

    // Filter divisi/dept yang konsisten dengan daftar Dept Head: program yang
    // di-assign ke sebuah dept (assigned_to_dept_id) ikut terhitung milik dept itu.
    private function orgFilter($builder, ?int $divisiId, ?int $deptId): void
    {
        if ($divisiId) $builder->where('work_initiatives.divisi_id', $divisiId);
        if ($deptId) {
            $builder->groupStart()
                ->where('work_initiatives.dept_id', $deptId)
                ->orWhere('work_initiatives.assigned_to_dept_id', $deptId)
            ->groupEnd();
        }
    }

    // ── Hitung jumlah per scope (tab Aktif|Arsip|Dihapus) ─────────────────
    // Query COUNT ringan (hanya join u_latest yang dibutuhkan rule arsip),
    // bukan findAll penuh — dipakai semua halaman daftar.
    private function countScope(\Closure $filter, string $scope): int
    {
        $b = $this->db->table('work_initiatives')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left');
        $filter($b);
        $this->applyScope($b, $scope);
        return $b->countAllResults();
    }

    private function scopeCountsWhere(\Closure $filter): array
    {
        $counts = [];
        foreach (['active', 'archived', 'deleted'] as $s) {
            $counts[$s] = $this->countScope($filter, $s);
        }
        return $counts;
    }

    public function scopeCountsForDeptHead(int $deptId): array
    {
        return $this->scopeCountsWhere(fn($b) => $b->groupStart()
            ->where('work_initiatives.dept_id', $deptId)
            ->orWhere('work_initiatives.assigned_to_dept_id', $deptId)
        ->groupEnd());
    }

    public function scopeCountsForDivision(int $divisiId): array
    {
        return $this->scopeCountsWhere(fn($b) => $b->where('work_initiatives.divisi_id', $divisiId));
    }

    public function scopeCountsForAdmin(?int $divisiId, ?int $deptId): array
    {
        return $this->scopeCountsWhere(fn($b) => $this->orgFilter($b, $divisiId, $deptId));
    }

    public function scopeCountsForGm(array $deputyDivisionIds): array
    {
        return $this->scopeCountsWhere(function ($b) use ($deputyDivisionIds) {
            $b->join('work_initiative_flags f', 'f.initiative_id = work_initiatives.id AND f.is_active = 1', 'left');
            $this->gmVisibility($b, $deputyDivisionIds);
        });
    }

    // ── Tren update mingguan untuk dashboard ─────────────────────────────
    // Scope 'active' diterapkan pada PROGRAM-nya (exclude arsip manual & auto)
    // agar chart konsisten dengan KPI tiles yang juga ber-scope aktif.
    public function weeklyUpdateTrend(?int $divisiId, ?int $deptId, string $since): array
    {
        $b = $this->db->table('work_initiative_updates u')
            ->select('YEARWEEK(u.created_at, 1) AS yw, u.status, COUNT(*) AS c')
            ->join('work_initiatives', 'work_initiatives.id = u.initiative_id')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->where('u.created_at >=', $since);
        $this->orgFilter($b, $divisiId, $deptId);
        $this->applyScope($b, 'active');

        return $b->groupBy('yw, u.status')->orderBy('yw')->get()->getResultArray();
    }

    // ── Grouping per dept utk tampilan daftar ─────────────────────────────
    // Program tanpa dept = program level divisi (buatan Deputy tanpa assign) —
    // selalu dinaikkan ke urutan paling atas.
    public static function groupByDept(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $grouped[$item['dept_name'] ?? self::DIVISION_LEVEL_LABEL][] = $item;
        }
        ksort($grouped);
        if (isset($grouped[self::DIVISION_LEVEL_LABEL])) {
            $grouped = [self::DIVISION_LEVEL_LABEL => $grouped[self::DIVISION_LEVEL_LABEL]] + $grouped;
        }
        return $grouped;
    }
}
