<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipRealisasiModel extends Model
{
    protected $table         = 'sponsorship_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'sponsor_id', 'tanggal', 'nilai', 'catatan', 'file_bukti', 'created_by',
    ];

    // Keyed by sponsor_id → { total_nilai, entries[] }
    public function getGroupedBySponsors(array $sponsorIds): array
    {
        if (empty($sponsorIds)) return [];
        $rows = $this->whereIn('sponsor_id', $sponsorIds)->orderBy('tanggal', 'DESC')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $sid = (int)$row['sponsor_id'];
            if (! isset($grouped[$sid])) {
                $grouped[$sid] = ['total_nilai' => 0, 'entries' => []];
            }
            $grouped[$sid]['total_nilai'] += (int)$row['nilai'];
            $grouped[$sid]['entries'][]    = $row;
        }
        return $grouped;
    }

    // Total realisasi per program (for KPI)
    public function getTotalByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('sponsorship_realisasi')
            ->select('program_id, SUM(nilai) as total_nilai')
            ->whereIn('program_id', $programIds)
            ->groupBy('program_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['program_id']] = (int)$r['total_nilai'];
        }
        return $map;
    }

    // Monthly totals across given program IDs (for trend chart)
    public function getAllMonthlyTotals(array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('sponsorship_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(nilai) as total_nilai")
            ->whereIn('program_id', $programIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }

    // Per-program totals for a given month
    public function getMonthlyByPrograms(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('sponsorship_realisasi')
            ->select('program_id, SUM(nilai) as total_nilai')
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('program_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['program_id']] = (int)$r['total_nilai'];
        }
        return $map;
    }

    // Daily rows for chart
    public function getDailyForMonth(string $bulan, array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('sponsorship_realisasi')
            ->select("tanggal, SUM(nilai) as nilai")
            ->whereIn('program_id', $programIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }

    public function getAvailableMonths(array $programIds): array
    {
        if (empty($programIds)) return [];
        return $this->db->table('sponsorship_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan")
            ->whereIn('program_id', $programIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'DESC')
            ->get()->getResultArray();
    }
}
