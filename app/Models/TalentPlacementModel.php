<?php

namespace App\Models;

use CodeIgniter\Model;

class TalentPlacementModel extends Model
{
    protected $table         = 'talent_placements';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'period_id', 'employee_id', 'performance', 'potential', 'catatan',
        'status', 'current_actor_id', 'placed_by', 'verified_by', 'verified_at',
    ];
    protected $useTimestamps = true;

    public function getOne(int $periodId, int $employeeId): ?array
    {
        return $this->where('period_id', $periodId)->where('employee_id', $employeeId)->first();
    }

    /**
     * Builder karyawan yang MASUK CAKUPAN penilaian (dipakai SAAT AKTIVASI periode):
     * aktif, bukan outsource, di bawah GM (grade > 2; Deputy grade 3 termasuk),
     * bukan probation (masuk setelah $cutoff dikecualikan).
     * Catatan: pakai jabatans.grade, BUKAN nama jabatan — kebal rename.
     */
    protected function scopeBuilder(string $cutoff)
    {
        return $this->db->table('employees e')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->where('e.status', 'aktif')
            ->where('(d.is_outsource IS NULL OR d.is_outsource = 0)')
            ->where('(j.grade IS NULL OR j.grade > 2)') // 1=Direktur, 2=GM dikecualikan
            ->where("(e.tanggal_masuk IS NULL OR e.tanggal_masuk <= '" . $cutoff . "')");
    }

    /** ID karyawan dalam cakupan penilaian (untuk generate placement saat aktivasi periode). */
    public function eligibleIds(string $cutoff): array
    {
        $rows = $this->scopeBuilder($cutoff)->select('e.id')->get()->getResultArray();
        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Data grid peta 9-box — BERBASIS PLACEMENT periode ini (komposisi BEKU
     * sejak aktivasi): karyawan resign tetap tampil, karyawan baru pasca-aktivasi
     * tidak muncul. Filter dept/jabatan mengikuti data karyawan saat ini.
     * $filterType = 'dept' | 'jabatan'.
     */
    public function gridData(int $periodId, string $filterType, int $filterId): array
    {
        $b = $this->db->table('talent_placements tp')
            ->select('tp.employee_id, e.nama, e.foto, j.nama AS jabatan, d.name AS dept_name,
                      tp.performance, tp.potential, tp.status, tp.catatan')
            ->join('employees e', 'e.id = tp.employee_id', 'inner')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->where('tp.period_id', $periodId);

        if ($filterType === 'jabatan') $b->where('e.jabatan_id', $filterId);
        else                          $b->where('e.dept_id', $filterId);

        return $b->orderBy('e.nama', 'ASC')->get()->getResultArray();
    }

    /** Placement yang menunggu giliran user ini (inbox penilai/reviewer), semua periode aktif. */
    public function inboxFor(int $userId): array
    {
        return $this->db->table('talent_placements tp')
            ->select('tp.*, e.nama AS employee_nama, j.nama AS jabatan, pr.nama AS periode, pr.status AS periode_status')
            ->join('employees e', 'e.id = tp.employee_id', 'left')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->join('talent_periods pr', 'pr.id = tp.period_id', 'left')
            ->where('tp.current_actor_id', $userId)
            ->whereIn('tp.status', ['input', 'in_review'])
            ->where('pr.status', 'active')
            ->orderBy('e.nama', 'ASC')
            ->get()->getResultArray();
    }

    /** Placement rantai-putus (tanpa aktor) di SEMUA periode aktif — ditangani HR. */
    public function hrPendingAll(): array
    {
        return $this->db->table('talent_placements tp')
            ->select('tp.*, e.nama AS employee_nama, j.nama AS jabatan, pr.nama AS periode, pr.status AS periode_status')
            ->join('employees e', 'e.id = tp.employee_id', 'left')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->join('talent_periods pr', 'pr.id = tp.period_id', 'inner')
            ->where('tp.current_actor_id IS NULL')
            ->whereIn('tp.status', ['input', 'in_review'])
            ->where('pr.status', 'active')
            ->orderBy('e.nama', 'ASC')
            ->get()->getResultArray();
    }
}
