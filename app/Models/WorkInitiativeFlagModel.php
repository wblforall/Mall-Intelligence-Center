<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeFlagModel extends Model
{
    protected $table         = 'work_initiative_flags';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['initiative_id', 'flagged_by', 'flagged_at', 'is_active'];

    public function isFlagged(int $initiativeId): bool
    {
        return $this->where('initiative_id', $initiativeId)->where('is_active', 1)->countAllResults() > 0;
    }

    public function toggle(int $initiativeId, int $userId): bool
    {
        $existing = $this->where('initiative_id', $initiativeId)->where('is_active', 1)->first();
        if ($existing) {
            $this->update($existing['id'], ['is_active' => 0]);
            return false; // unflagged
        }
        $this->insert([
            'initiative_id' => $initiativeId,
            'flagged_by'    => $userId,
            'flagged_at'    => date('Y-m-d H:i:s'),
            'is_active'     => 1,
        ]);
        return true; // flagged
    }
}
