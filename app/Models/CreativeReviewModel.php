<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Riwayat keputusan approval materi creative — dipakai bersama oleh
 * creative standalone (scope 'standalone') dan creative per-event
 * (scope 'event').
 */
class CreativeReviewModel extends Model
{
    protected $table         = 'creative_reviews';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'scope', 'item_id', 'event_id', 'versi', 'aksi', 'catatan',
        'basis_file_id', 'opsi_label', 'jumlah_opsi', 'oleh',
    ];

    /** Riwayat satu item, terlama dulu, lengkap dengan nama pelaku. */
    public function getForItem(string $scope, int $itemId): array
    {
        return $this->select('creative_reviews.*, u.name AS oleh_nama')
            ->join('users u', 'u.id = creative_reviews.oleh', 'left')
            ->where('creative_reviews.scope', $scope)
            ->where('creative_reviews.item_id', $itemId)
            ->orderBy('creative_reviews.id', 'ASC')
            ->findAll();
    }

    /**
     * Riwayat banyak item sekaligus, dikelompokkan per item_id.
     * Satu query — dipakai halaman index yang menampilkan puluhan item.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getGroupedByItems(string $scope, array $itemIds): array
    {
        if (empty($itemIds)) return [];

        $rows = $this->select('creative_reviews.*, u.name AS oleh_nama')
            ->join('users u', 'u.id = creative_reviews.oleh', 'left')
            ->where('creative_reviews.scope', $scope)
            ->whereIn('creative_reviews.item_id', $itemIds)
            ->orderBy('creative_reviews.id', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['item_id']][] = $row;
        }
        return $grouped;
    }
}
