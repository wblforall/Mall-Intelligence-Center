<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyHadiahItemModel extends Model
{
    protected $table         = 'event_loyalty_hadiah_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'nama_hadiah', 'stok', 'nilai_satuan', 'catatan', 'created_by',
    ];

    public function getByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->whereIn('program_id', $programIds)->orderBy('program_id')->orderBy('id')->findAll();
        $grouped = [];
        foreach ($rows as $row) { $grouped[$row['program_id']][] = $row; }
        return $grouped;
    }
}
