<?php

namespace App\Models;

use CodeIgniter\Model;

class CreativeFileModel extends Model
{
    protected $table         = 'creative_files';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'creative_item_id', 'file_name', 'original_name', 'catatan', 'uploaded_by',
    ];

    public function getByItem(int $itemId): array
    {
        return $this->where('creative_item_id', $itemId)->orderBy('id')->findAll();
    }

    public function getGroupedByItems(array $ids): array
    {
        if (empty($ids)) return [];
        $rows = $this->whereIn('creative_item_id', $ids)->orderBy('id')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['creative_item_id']][] = $row;
        }
        return $grouped;
    }
}
