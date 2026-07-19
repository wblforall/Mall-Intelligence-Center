<?php

namespace App\Models;

use CodeIgniter\Model;

class EventSponsorRealisasiModel extends Model
{
    protected $table         = 'event_sponsor_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'sponsor_id', 'tanggal', 'nilai', 'catatan', 'file_foto', 'file_terima', 'created_by'];
    protected $useTimestamps = true;

    public function getGroupedByEvent(int $eventId): array
    {
        $rows   = $this->select('event_sponsor_realisasi.*, u.name AS pengisi')
                       ->join('users u', 'u.id = event_sponsor_realisasi.created_by', 'left')
                       ->where('event_id', $eventId)->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['sponsor_id']][] = $r;
        }
        return $result;
    }

    public function getTotalByEvent(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('event_id', $eventId)->get()->getRow()->total ?? 0);
    }

    // ── Agregasi utk Laporan Bulanan Sponsorship ─────────────────────────

    /** Realisasi per event pada satu bulan: [event_id => nilai] */
    public function getMonthlyByEvents(string $bulan, array $eventIds): array
    {
        if (empty($eventIds)) return [];
        $rows = $this->db->table('event_sponsor_realisasi')
            ->select('event_id, SUM(nilai) as total_nilai')
            ->whereIn('event_id', $eventIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('event_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) { $map[(int)$r['event_id']] = (int)$r['total_nilai']; }
        return $map;
    }

    /** Kumulatif s/d bulan tertentu per event: [event_id => nilai] */
    public function getCumulativeByEvents(string $upToMonth, array $eventIds): array
    {
        if (empty($eventIds)) return [];
        $rows = $this->db->table('event_sponsor_realisasi')
            ->select('event_id, SUM(nilai) as total_nilai')
            ->whereIn('event_id', $eventIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m') <=", $upToMonth)
            ->groupBy('event_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) { $map[(int)$r['event_id']] = (int)$r['total_nilai']; }
        return $map;
    }

    /** Total realisasi per bulan (semua event terkait) — utk grafik tren */
    public function getAllMonthlyTotals(array $eventIds): array
    {
        if (empty($eventIds)) return [];
        return $this->db->table('event_sponsor_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(nilai) as total_nilai")
            ->whereIn('event_id', $eventIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }

    /** Baris harian satu bulan (semua event terkait) — utk grafik harian */
    public function getDailyForMonth(string $bulan, array $eventIds): array
    {
        if (empty($eventIds)) return [];
        return $this->db->table('event_sponsor_realisasi')
            ->select('tanggal, SUM(nilai) as nilai')
            ->whereIn('event_id', $eventIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }
}
