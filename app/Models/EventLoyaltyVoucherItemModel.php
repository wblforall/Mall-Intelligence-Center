<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyVoucherItemModel extends Model
{
    protected $table         = 'event_loyalty_voucher_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'nama_voucher', 'nilai_voucher', 'total_diterbitkan', 'target_penyerapan', 'catatan', 'created_by',
    ];

    public function getByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->whereIn('program_id', $programIds)
            ->orderBy('program_id')->orderBy('nilai_voucher', 'DESC')->orderBy('id')
            ->findAll();
        $grouped = [];
        foreach ($rows as $row) { $grouped[$row['program_id']][] = $row; }
        return $grouped;
    }
}
