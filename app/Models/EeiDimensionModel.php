<?php

namespace App\Models;

use CodeIgniter\Model;

class EeiDimensionModel extends Model
{
    protected $table         = 'eei_dimensions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'deskripsi', 'urutan'];
    protected $useTimestamps = true;

    public function getWithQuestions(): array
    {
        $dims = $this->orderBy('urutan')->orderBy('nama')->findAll();
        if (empty($dims)) return [];

        $questions = db_connect()->table('eei_questions')
            ->orderBy('urutan')->get()->getResultArray();

        $qMap = [];
        foreach ($questions as $q) {
            $qMap[$q['dimension_id']][] = $q;
        }
        foreach ($dims as &$dim) {
            $dim['questions'] = $qMap[$dim['id']] ?? [];
        }
        return $dims;
    }

    public function getNextUrutan(): int
    {
        $max = $this->selectMax('urutan')->first();
        return (int)($max['urutan'] ?? 0) + 1;
    }
}
