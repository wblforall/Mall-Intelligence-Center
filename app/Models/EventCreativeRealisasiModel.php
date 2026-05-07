<?php

namespace App\Models;

use CodeIgniter\Model;

class EventCreativeRealisasiModel extends Model
{
    protected $table         = 'event_creative_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'event_id', 'creative_item_id', 'tanggal', 'nilai', 'nama_influencer',
        'file_name', 'original_name',
        'serah_terima_file_name', 'serah_terima_original_name',
        'bukti_terpasang_file_name', 'bukti_terpasang_original_name',
        'catatan', 'created_by',
    ];

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('creative_item_id', $itemIds)->orderBy('tanggal')->orderBy('id')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['creative_item_id']]['entries'][] = $row;
            $grouped[$row['creative_item_id']]['total']     = ($grouped[$row['creative_item_id']]['total'] ?? 0) + (int)$row['nilai'];
        }
        return $grouped;
    }

    public function getTotalByEvent(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')
                         ->where('event_id', $eventId)
                         ->get()->getRow()->total ?? 0);
    }

    public function getMonthlyGrouped(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        [$year, $month] = explode('-', $bulan);
        $rows = $this->whereIn('creative_item_id', $itemIds)
                     ->where('YEAR(tanggal)', (int)$year)
                     ->where('MONTH(tanggal)', (int)$month)
                     ->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $cid = $row['creative_item_id'];
            $grouped[$cid]['entries'][] = $row;
            $grouped[$cid]['total']     = ($grouped[$cid]['total'] ?? 0) + (int)$row['nilai'];
        }
        return $grouped;
    }
}
