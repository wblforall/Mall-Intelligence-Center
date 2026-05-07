<?php

namespace App\Models;

use CodeIgniter\Model;

class CreativeRealisasiModel extends Model
{
    protected $table         = 'creative_realisasi';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'creative_item_id', 'tanggal', 'nilai', 'nama_influencer',
        'file_name', 'original_name',
        'serah_terima_file_name', 'serah_terima_original_name',
        'bukti_terpasang_file_name', 'bukti_terpasang_original_name',
        'catatan', 'created_by',
    ];

    public function getByItem(int $itemId): array
    {
        return $this->where('creative_item_id', $itemId)
                    ->orderBy('tanggal', 'ASC')
                    ->findAll();
    }

    public function getGroupedByItems(array $ids): array
    {
        if (empty($ids)) return [];

        $rows = $this->whereIn('creative_item_id', $ids)
                     ->orderBy('tanggal', 'ASC')
                     ->orderBy('id', 'ASC')
                     ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $cid = $row['creative_item_id'];
            $grouped[$cid]['entries'][] = $row;
            $grouped[$cid]['total']     = ($grouped[$cid]['total'] ?? 0) + (int)$row['nilai'];
        }
        return $grouped;
    }

    public function getTotalRealisasi(array $ids): int
    {
        if (empty($ids)) return 0;
        return (int)($this->selectSum('nilai', 'total')
                         ->whereIn('creative_item_id', $ids)
                         ->get()->getRow()->total ?? 0);
    }

    public function getMonthlyGrouped(string $bulan, array $ids): array
    {
        if (empty($ids)) return [];
        [$year, $month] = explode('-', $bulan);
        $rows = $this->whereIn('creative_item_id', $ids)
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
