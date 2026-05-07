<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltyHadiahRealisasiModel extends Model
{
    protected $table         = 'loyalty_hadiah_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'item_id', 'tanggal', 'jumlah_dibagikan', 'catatan', 'created_by',
    ];

    // Keyed by item_id → { total, entries[] }
    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('item_id', $itemIds)->orderBy('tanggal', 'DESC')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $iid = $row['item_id'];
            if (! isset($grouped[$iid])) $grouped[$iid] = ['total' => 0, 'entries' => []];
            $grouped[$iid]['total']    += (int)$row['jumlah_dibagikan'];
            $grouped[$iid]['entries'][] = $row;
        }
        return $grouped;
    }

    // Monthly totals keyed by item_id (for summary)
    public function getMonthlyByItems(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->db->table('loyalty_hadiah_realisasi')
            ->select('item_id, SUM(jumlah_dibagikan) as total_dibagikan')
            ->whereIn('item_id', $itemIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('item_id')
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $r) { $result[$r['item_id']] = (int)$r['total_dibagikan']; }
        return $result;
    }

    // All monthly totals for given items (for trend)
    public function getAllMonthlyTotals(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        return $this->db->table('loyalty_hadiah_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah_dibagikan) as total_dibagikan")
            ->whereIn('item_id', $itemIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }
}
