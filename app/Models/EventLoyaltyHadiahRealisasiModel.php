<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyHadiahRealisasiModel extends Model
{
    protected $table         = 'event_loyalty_hadiah_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'item_id', 'tanggal', 'jumlah_dibagikan', 'catatan', 'created_by',
    ];

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('item_id', $itemIds)->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $iid = $row['item_id'];
            if (! isset($grouped[$iid])) {
                $grouped[$iid] = ['total' => 0, 'entries' => []];
            }
            $grouped[$iid]['total']    += (int)$row['jumlah_dibagikan'];
            $grouped[$iid]['entries'][] = $row;
        }
        return $grouped;
    }

    public function getMonthlyByItems(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->db->table('event_loyalty_hadiah_realisasi')
            ->select('item_id, SUM(jumlah_dibagikan) as total_dibagikan')
            ->whereIn('item_id', $itemIds)
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('item_id')
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $r) { $result[$r['item_id']] = (int)$r['total_dibagikan']; }
        return $result;
    }

    public function getAllMonthlyTotals(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        return $this->db->table('event_loyalty_hadiah_realisasi')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah_dibagikan) as total_dibagikan")
            ->whereIn('item_id', $itemIds)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->get()->getResultArray();
    }
}
