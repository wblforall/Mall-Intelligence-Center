<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeUpdateModel extends Model
{
    protected $table         = 'work_initiative_updates';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'initiative_id', 'status', 'progress_pct', 'catatan', 'hambatan', 'updated_by', 'created_at',
    ];

    public function historyFor(int $initiativeId): array
    {
        return $this->select('work_initiative_updates.*, e.nama AS updated_by_name')
            ->join('employees e', 'e.id = work_initiative_updates.updated_by', 'left')
            ->where('initiative_id', $initiativeId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function latestFor(int $initiativeId): ?array
    {
        return $this->where('initiative_id', $initiativeId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
