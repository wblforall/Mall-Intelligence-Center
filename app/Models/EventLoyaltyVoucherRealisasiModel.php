<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyVoucherRealisasiModel extends Model
{
    protected $table         = 'event_loyalty_voucher_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'item_id', 'tanggal', 'tersebar', 'terpakai', 'catatan', 'created_by',
    ];

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('item_id', $itemIds)->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $iid = $row['item_id'];
            if (! isset($grouped[$iid])) {
                $grouped[$iid] = ['total_tersebar' => 0, 'total_terpakai' => 0, 'entries' => []];
            }
            $grouped[$iid]['total_tersebar'] += (int)$row['tersebar'];
            $grouped[$iid]['total_terpakai'] += (int)$row['terpakai'];
            $grouped[$iid]['entries'][]       = $row;
        }
        return $grouped;
    }

    public function getMonthlyByItems(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->db->table('event_loyalty_voucher_realisasi')
            ->select('item_id, SUM(tersebar) as total_tersebar, SUM(terpakai) as total_terpakai')
            ->whereIn('item_id', $itemIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('item_id')
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['item_id']] = [
                'total_tersebar' => (int)$r['total_tersebar'],
                'total_terpakai' => (int)$r['total_terpakai'],
            ];
        }
        return $result;
    }

    public function getAllMonthlyTotals(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        return $this->db->table('event_loyalty_voucher_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(tersebar) as total_tersebar, SUM(terpakai) as total_terpakai")
            ->whereIn('item_id', $itemIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }

    public function getDailyForMonth(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        return $this->db->table('event_loyalty_voucher_realisasi')
            ->select("tanggal, SUM(tersebar) as tersebar, SUM(terpakai) as terpakai")
            ->whereIn('item_id', $itemIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'ASC')
            ->get()->getResultArray();
    }
}
