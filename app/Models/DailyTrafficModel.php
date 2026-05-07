<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyTrafficModel extends Model
{
    protected $table         = 'daily_traffic';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tanggal', 'mall', 'jam', 'pintu', 'jumlah_pengunjung', 'created_by'];
    protected $useTimestamps = false;

    public function getByDateMall(string $tanggal, string $mall): array
    {
        return $this->where('tanggal', $tanggal)->where('mall', $mall)
            ->orderBy('jam')->orderBy('pintu')
            ->findAll();
    }

    public function deleteByDateMall(string $tanggal, string $mall): void
    {
        $this->where('tanggal', $tanggal)->where('mall', $mall)->delete();
    }

    public function deleteByDateMallDoors(string $tanggal, string $mall, array $doors): void
    {
        $this->where('tanggal', $tanggal)->where('mall', $mall)->whereIn('pintu', $doors)->delete();
    }

    public function getDailyTotals(string $startDate, string $endDate, string $mall = null): array
    {
        $builder = $this->db->table('daily_traffic')
            ->select('tanggal, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->groupBy('tanggal')
            ->orderBy('tanggal');

        if ($mall) $builder->where('mall', $mall);

        return $builder->get()->getResultArray();
    }

    public function getLatestDate(string $mall): ?string
    {
        $row = $this->db->table('daily_traffic')
            ->select('tanggal')
            ->where('mall', $mall)
            ->orderBy('tanggal', 'DESC')
            ->limit(1)
            ->get()->getRowArray();
        return $row['tanggal'] ?? null;
    }

    public function getInputtedDates(string $mall, string $month = null): array
    {
        $builder = $this->db->table('daily_traffic')
            ->select('tanggal, SUM(jumlah_pengunjung) AS total')
            ->where('mall', $mall);

        if ($month) {
            $builder->where('DATE_FORMAT(tanggal, "%Y-%m")', $month);
        }

        return $builder
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'DESC')
            ->get()->getResultArray();
    }

    public function getPeriodTotal(string $startDate, string $endDate, string $mall = null): int
    {
        $builder = $this->db->table('daily_traffic')
            ->selectSum('jumlah_pengunjung', 'total')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate);

        if ($mall) $builder->where('mall', $mall);

        return (int)($builder->get()->getRow()->total ?? 0);
    }

    public function getByHour(string $startDate, string $endDate, string $mall = null): array
    {
        $builder = $this->db->table('daily_traffic')
            ->select('jam, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->groupBy('jam')
            ->orderBy('jam');

        if ($mall) $builder->where('mall', $mall);

        return $builder->get()->getResultArray();
    }

    public function getByDoor(string $startDate, string $endDate, string $mall = null): array
    {
        $builder = $this->db->table('daily_traffic')
            ->select('pintu, SUM(jumlah_pengunjung) AS total, COUNT(DISTINCT tanggal) AS hari')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->groupBy('pintu')
            ->orderBy('total', 'DESC');

        if ($mall) $builder->where('mall', $mall);

        return $builder->get()->getResultArray();
    }

    public function getDailyByBothMalls(string $startDate, string $endDate): array
    {
        $rows = $this->db->table('daily_traffic')
            ->select('tanggal, mall, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->groupBy(['tanggal', 'mall'])
            ->orderBy('tanggal')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['tanggal']][$r['mall']] = (int)$r['total'];
        }
        return $map;
    }
}
