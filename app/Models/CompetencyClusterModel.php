<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetencyClusterModel extends Model
{
    protected $table         = 'competency_clusters';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'deskripsi', 'urutan'];
    protected $useTimestamps = true;

    public function getAllWithCount(): array
    {
        return db_connect()->table('competency_clusters cl')
            ->select('cl.*, COUNT(c.id) AS competency_count')
            ->join('competencies c', 'c.cluster_id = cl.id', 'left')
            ->groupBy('cl.id')
            ->orderBy('cl.urutan')
            ->orderBy('cl.nama')
            ->get()->getResultArray();
    }

    public function getNextUrutan(): int
    {
        $max = $this->selectMax('urutan')->first();
        return (int)($max['urutan'] ?? 0) + 1;
    }
}
