<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltyRealisasiModel extends Model
{
    protected $table         = 'loyalty_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'tanggal', 'jumlah', 'member_aktif', 'tersebar', 'terpakai', 'catatan', 'created_by',
    ];

    public function getByProgram(int $programId): array
    {
        return $this->where('program_id', $programId)->orderBy('tanggal', 'DESC')->findAll();
    }

    public function getGroupedByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];

        $rows = $this->whereIn('program_id', $programIds)->orderBy('tanggal', 'DESC')->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $pid = $row['program_id'];
            if (! isset($grouped[$pid])) {
                $grouped[$pid] = ['total' => 0, 'total_aktif' => 0, 'tersebar' => 0, 'terpakai' => 0, 'entries' => []];
            }
            $grouped[$pid]['total']       += (int)$row['jumlah'];
            $grouped[$pid]['total_aktif'] += (int)($row['member_aktif'] ?? 0);
            $grouped[$pid]['tersebar']    += (int)($row['tersebar'] ?? 0);
            $grouped[$pid]['terpakai']    += (int)($row['terpakai'] ?? 0);
            $grouped[$pid]['entries'][]    = $row;
        }
        return $grouped;
    }

    // Returns daily totals for a program (for trend chart)
    public function getDailyTotals(int $programId): array
    {
        return $this->db->table('loyalty_realisasi')
            ->select('tanggal, SUM(jumlah) as total_jumlah, SUM(tersebar) as total_tersebar, SUM(terpakai) as total_terpakai')
            ->where('program_id', $programId)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }

    public function getAvailableMonths(): array
    {
        return $this->db->table('loyalty_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan")
            ->distinct()
            ->orderBy('bulan', 'DESC')
            ->get()->getResultArray();
    }

    // Per-program totals for a given month (keyed by program_id)
    public function getMonthlyByPrograms(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('loyalty_realisasi')
            ->select("program_id, SUM(jumlah) as total_jumlah, SUM(member_aktif) as total_member_aktif, SUM(tersebar) as total_tersebar, SUM(terpakai) as total_terpakai, COUNT(*) as entri_count")
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('program_id')
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) { $result[$row['program_id']] = $row; }
        return $result;
    }

    // Monthly aggregated totals across all programs (for trend table)
    public function getAllMonthlyTotals(array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('loyalty_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as total_jumlah, SUM(member_aktif) as total_member_aktif, SUM(tersebar) as total_tersebar, SUM(terpakai) as total_terpakai")
            ->whereIn('program_id', $programIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }

    // Daily rows for a given month (for chart)
    public function getDailyForMonth(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('loyalty_realisasi')
            ->select("tanggal, program_id, jumlah, tersebar, terpakai")
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }
}
