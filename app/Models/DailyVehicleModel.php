<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyVehicleModel extends Model
{
    protected $table         = 'daily_vehicles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tanggal', 'mall', 'total_mobil', 'total_motor', 'created_by'];
    protected $useTimestamps = true;

    public function getByDateMall(string $tanggal, string $mall): ?array
    {
        return $this->where('tanggal', $tanggal)->where('mall', $mall)->first();
    }

    public function getDailyTotals(string $startDate, string $endDate, string $mall = null): array
    {
        $builder = $this->db->table('daily_vehicles')
            ->select('tanggal, SUM(total_mobil) AS total_mobil, SUM(total_motor) AS total_motor')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->groupBy('tanggal')
            ->orderBy('tanggal');

        if ($mall) $builder->where('mall', $mall);

        return $builder->get()->getResultArray();
    }

    public function getPeriodTotals(string $startDate, string $endDate): array
    {
        $row = $this->db->table('daily_vehicles')
            ->selectSum('total_mobil', 'mobil')
            ->selectSum('total_motor', 'motor')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->get()->getRow();

        return ['mobil' => (int)($row->mobil ?? 0), 'motor' => (int)($row->motor ?? 0)];
    }
}
