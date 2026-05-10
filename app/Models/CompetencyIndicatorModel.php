<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetencyIndicatorModel extends Model
{
    protected $table         = 'competency_indicators';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['competency_id', 'level', 'deskripsi', 'urutan'];
    protected $useTimestamps = true;

    public function getByCompetency(int $compId): array
    {
        return $this->where('competency_id', $compId)
                    ->orderBy('level')->orderBy('urutan')->orderBy('id')
                    ->findAll();
    }

    // [comp_id => [level => [indicator_id, ...]]] for level calculation
    public function getAllGroupedForCalc(): array
    {
        $rows = $this->orderBy('competency_id')->orderBy('level')->orderBy('urutan')->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['competency_id']][$r['level']][] = (int)$r['id'];
        }
        return $map;
    }

    // [comp_id => [level => [rows]]] for display
    public function getAllGroupedForDisplay(): array
    {
        $rows = $this->orderBy('competency_id')->orderBy('level')->orderBy('urutan')->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['competency_id']][$r['level']][] = $r;
        }
        return $map;
    }

    public function getNextUrutan(int $compId, int $level): int
    {
        $row = $this->selectMax('urutan')->where('competency_id', $compId)->where('level', $level)->first();
        return ($row['urutan'] ?? 0) + 1;
    }
}
