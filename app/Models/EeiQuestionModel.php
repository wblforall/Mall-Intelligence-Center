<?php

namespace App\Models;

use CodeIgniter\Model;

class EeiQuestionModel extends Model
{
    protected $table         = 'eei_questions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dimension_id', 'pertanyaan', 'urutan', 'is_reversed'];
    protected $useTimestamps = true;

    public function getNextUrutan(int $dimensionId): int
    {
        $max = $this->where('dimension_id', $dimensionId)->selectMax('urutan')->first();
        return (int)($max['urutan'] ?? 0) + 1;
    }
}
