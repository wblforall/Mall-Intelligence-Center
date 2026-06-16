<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalDivisionDeputyModel extends Model
{
    protected $table         = 'appraisal_division_deputies';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['division_id', 'user_id'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** division_id => user_id. */
    public function map(): array
    {
        $map = [];
        foreach ($this->findAll() as $r) $map[(int) $r['division_id']] = (int) $r['user_id'];
        return $map;
    }

    public function deputyOfDivision(?int $divisionId): ?int
    {
        if ($divisionId === null) return null;
        $row = $this->where('division_id', $divisionId)->first();
        return $row ? (int) $row['user_id'] : null;
    }

    public function divisionIdsForUser(int $userId): array
    {
        return array_map('intval', array_column($this->where('user_id', $userId)->findAll(), 'division_id'));
    }

    public function setDeputy(int $divisionId, ?int $userId): void
    {
        $existing = $this->where('division_id', $divisionId)->first();
        if ($userId === null) { if ($existing) $this->delete($existing['id']); return; }
        if ($existing) $this->update($existing['id'], ['user_id' => $userId]);
        else $this->insert(['division_id' => $divisionId, 'user_id' => $userId]);
    }
}
