<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetencyQuestionModel extends Model
{
    protected $table         = 'competency_questions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['competency_id', 'pertanyaan', 'urutan',
                                'level_1', 'level_2', 'level_3', 'level_4', 'level_5'];
    protected $useTimestamps = true;

    public function getByCompetency(int $compId): array
    {
        return $this->where('competency_id', $compId)->orderBy('urutan')->orderBy('id')->findAll();
    }

    // [comp_id => [questions]]
    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('competency_id')->orderBy('urutan')->orderBy('id')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['competency_id']][] = $r;
        }
        return $out;
    }

    public function getNextUrutan(int $compId): int
    {
        $max = $this->selectMax('urutan')->where('competency_id', $compId)->first();
        return (int)($max['urutan'] ?? 0) + 1;
    }
}
