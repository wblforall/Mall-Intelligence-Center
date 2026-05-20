<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyVehicleModel extends Model
{
    protected $table         = 'daily_vehicles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tanggal', 'total_mobil', 'total_motor', 'total_mobil_box', 'total_bus', 'total_truck', 'total_taxi', 'created_by'];
    protected $useTimestamps = true;

    public function getByDate(string $tanggal): ?array
    {
        return $this->where('tanggal', $tanggal)->first();
    }

    public function getDailyTotals(string $startDate, string $endDate): array
    {
        return $this->db->table('daily_vehicles')
            ->select('tanggal, total_mobil, total_motor, total_mobil_box, total_bus, total_truck, total_taxi')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->orderBy('tanggal')
            ->get()->getResultArray();
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
