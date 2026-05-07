<?php

namespace App\Models;

use CodeIgniter\Model;

class EventCreativeFileModel extends Model
{
    protected $table         = 'event_creative_files';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'creative_item_id', 'event_id', 'file_name', 'original_name', 'catatan', 'uploaded_by',
    ];

    public function getByItem(int $itemId): array
    {
        return $this->where('creative_item_id', $itemId)->orderBy('id')->findAll();
    }

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('creative_item_id', $itemIds)->orderBy('id')->findAll();
        $grouped = [];
        foreach ($rows as $row) { $grouped[$row['creative_item_id']][] = $row; }
        return $grouped;
    }
}
