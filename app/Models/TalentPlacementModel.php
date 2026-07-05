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

    /** ID karyawan dalam cakupan penilaian (untuk generate placement saat aktivasi periode). */
    public function eligibleIds(string $cutoff): array
    {
        $rows = $this->scopeBuilder($cutoff)->select('e.id')->get()->getResultArray();
        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Builder karyawan yang MASUK CAKUPAN penilaian:
     * aktif, bukan outsource, BUKAN GM (deputy & bawahnya termasuk), bukan probation < 6 bln.
     * $cutoff = tanggal batas masuk (Y-m-d); yang masuk setelah ini dikecualikan.
     */
    protected function scopeBuilder(string $cutoff)
    {
        return $this->db->table('employees e')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('divisions dv', 'dv.id = e.division_id', 'left')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->where('e.status', 'aktif')
            ->where('(d.is_outsource IS NULL OR d.is_outsource = 0)')
            ->where("(j.nama IS NULL OR LOWER(j.nama) NOT LIKE '%general manager%')")
            ->where("(e.tanggal_masuk IS NULL OR e.tanggal_masuk <= '" . $cutoff . "')");
    }

    /**
     * Data grid: karyawan dalam cakupan (terfilter dept/jabatan) + placement periode ini (bila ada).
     * $filterType = 'dept' | 'jabatan'.
     */
    public function gridData(int $periodId, string $filterType, int $filterId, string $cutoff): array
    {
        $b = $this->scopeBuilder($cutoff)
            ->select('e.id AS employee_id, e.nama, e.foto, j.nama AS jabatan, d.name AS dept_name,
                      tp.performance, tp.potential, tp.status, tp.catatan')
            ->join('talent_placements tp', 'tp.employee_id = e.id AND tp.period_id = ' . (int) $periodId, 'left');

        if ($filterType === 'jabatan') $b->where('e.jabatan_id', $filterId);
        else                          $b->where('e.dept_id', $filterId);

        return $b->orderBy('e.nama', 'ASC')->get()->getResultArray();
    }

    /** Distribusi (jumlah) per kombinasi performance×potential untuk periode+filter. */
    public function distribution(int $periodId, string $filterType, int $filterId, string $cutoff): array
    {
        $b = $this->scopeBuilder($cutoff)
            ->select('tp.performance, tp.potential, COUNT(*) AS n')
            ->join('talent_placements tp', 'tp.employee_id = e.id AND tp.period_id = ' . (int) $periodId, 'inner')
            ->where('tp.performance IS NOT NULL')->where('tp.potential IS NOT NULL')
            ->groupBy('tp.performance, tp.potential');

        if ($filterType === 'jabatan') $b->where('e.jabatan_id', $filterId);
        else                          $b->where('e.dept_id', $filterId);

        $out = [];
        foreach ($b->get()->getResultArray() as $r) {
            $out[(int) $r['performance']][(int) $r['potential']] = (int) $r['n'];
        }
        return $out;
    }

    /** Placement yang menunggu giliran user ini (inbox penilai/reviewer). */
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
}
