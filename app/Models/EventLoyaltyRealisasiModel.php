<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyRealisasiModel extends Model
{
    protected $table         = 'event_loyalty_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['program_id', 'event_id', 'tanggal', 'jumlah', 'member_aktif', 'tersebar', 'terpakai', 'catatan', 'created_by'];
    protected $useTimestamps = true;

    public function getByProgram(int $programId): array
    {
        return $this->where('program_id', $programId)
            ->orderBy('tanggal', 'ASC')
            ->findAll();
    }

    public function getTotalByProgram(int $programId): int
    {
        return (int)($this->selectSum('jumlah', 'total')
            ->where('program_id', $programId)
            ->get()->getRow()->total ?? 0);
    }

    // Keyed by program_id for a list of program IDs (cross-event)
    public function getGroupedByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->whereIn('program_id', $programIds)->orderBy('tanggal', 'DESC')->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $pid = $r['program_id'];
            if (! isset($map[$pid])) {
                $map[$pid] = ['total' => 0, 'total_aktif' => 0, 'tersebar' => 0, 'terpakai' => 0, 'entries' => []];
            }
            $map[$pid]['total']      += (int)$r['jumlah'];
            $map[$pid]['total_aktif']+= (int)($r['member_aktif'] ?? 0);
            $map[$pid]['tersebar']   += (int)($r['tersebar'] ?? 0);
            $map[$pid]['terpakai']   += (int)($r['terpakai'] ?? 0);
            $map[$pid]['entries'][]   = $r;
        }
        return $map;
    }

    // Monthly per-program totals (keyed by program_id)
    public function getMonthlyByPrograms(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('event_loyalty_realisasi')
            ->select("program_id, SUM(jumlah) as total_jumlah, COALESCE(SUM(member_aktif), 0) as total_member_aktif, COALESCE(SUM(tersebar), 0) as total_tersebar, COALESCE(SUM(terpakai), 0) as total_terpakai, COUNT(*) as entri_count")
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('program_id')
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) { $result[$row['program_id']] = $row; }
        return $result;
    }

    // All monthly totals across given program IDs
    public function getAllMonthlyTotals(array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('event_loyalty_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total_jumlah, COALESCE(SUM(member_aktif), 0) as total_member_aktif, COALESCE(SUM(tersebar), 0) as total_tersebar, COALESCE(SUM(terpakai), 0) as total_terpakai")
            ->whereIn('program_id', $programIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }

    // Daily rows for month (for chart)
    public function getDailyForMonth(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('event_loyalty_realisasi')
            ->select("tanggal, program_id, jumlah, tersebar, terpakai")
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }

    // Available months from event realisasi
    public function getAvailableMonths(): array
    {
        return $this->db->table('event_loyalty_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan")
            ->distinct()
            ->orderBy('bulan', 'DESC')
            ->get()->getResultArray();
    }

    // Returns keyed by program_id: ['total' => int, 'entries' => array]
    public function getGroupedByEvent(int $eventId): array
    {
        $rows = $this->where('event_id', $eventId)
            ->orderBy('program_id')->orderBy('tanggal')
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $pid = $r['program_id'];
            if (! isset($map[$pid])) {
                $map[$pid] = ['total' => 0, 'total_aktif' => 0, 'tersebar' => 0, 'terpakai' => 0, 'entries' => []];
            }
            $map[$pid]['total']      += (int)$r['jumlah'];
            $map[$pid]['total_aktif']+= (int)($r['member_aktif'] ?? 0);
            $map[$pid]['tersebar']   += (int)($r['tersebar'] ?? 0);
            $map[$pid]['terpakai']   += (int)($r['terpakai'] ?? 0);
            $map[$pid]['entries'][]   = $r;
        }
        return $map;
    }
}
