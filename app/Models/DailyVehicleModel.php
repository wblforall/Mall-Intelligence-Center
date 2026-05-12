<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyVehicleModel extends Model
{
    protected $table         = 'daily_vehicles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tanggal', 'mall', 'total_mobil', 'total_motor', 'total_mobil_box', 'total_bus', 'total_truck', 'total_taxi', 'created_by'];
    protected $useTimestamps = true;

    public function getByDateMall(string $tanggal, string $mall): ?array
    {
        return $this->where('tanggal', $tanggal)->where('mall', $mall)->first();
    }

    public function getDailyTotals(string $startDate, string $endDate, string $mall = null): array
    {
        $builder = $this->db->table('daily_vehicles')
            ->select('tanggal, SUM(total_mobil) AS total_mobil, SUM(total_motor) AS total_motor, SUM(total_mobil_box) AS total_mobil_box, SUM(total_bus) AS total_bus, SUM(total_truck) AS total_truck, SUM(total_taxi) AS total_taxi')
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
            ->selectSum('total_mobil',     'mobil')
            ->selectSum('total_motor',     'motor')
            ->selectSum('total_mobil_box', 'mobil_box')
            ->selectSum('total_bus',       'bus')
            ->selectSum('total_truck',     'truck')
            ->selectSum('total_taxi',      'taxi')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->get()->getRow();

        return [
            'mobil'     => (int)($row->mobil     ?? 0),
            'motor'     => (int)($row->motor     ?? 0),
            'mobil_box' => (int)($row->mobil_box ?? 0),
            'bus'       => (int)($row->bus       ?? 0),
            'truck'     => (int)($row->truck     ?? 0),
            'taxi'      => (int)($row->taxi      ?? 0),
        ];
    }
}
