<?php

namespace App\Models;

use CodeIgniter\Model;

class TrainingCompetencyModel extends Model
{
    protected $table         = 'training_competencies';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['program_id', 'competency_id'];
    protected $useTimestamps = true;

    public function getCompetencyIdsByProgram(int $programId): array
    {
        return array_column(
            $this->where('program_id', $programId)->findAll(),
            'competency_id'
        );
    }

    // [competency_id => [programs]] — upcoming/ongoing programs covering given competency IDs
    public function getRecommendedByCompetencies(array $competencyIds): array
    {
        if (empty($competencyIds)) return [];

        $rows = db_connect()->table('training_competencies tc')
            ->select('tc.competency_id, p.id, p.nama, p.tipe, p.vendor, p.tanggal_mulai, p.tanggal_selesai, p.status')
            ->join('training_programs p', 'p.id = tc.program_id')
            ->whereIn('tc.competency_id', $competencyIds)
            ->whereIn('p.status', ['upcoming', 'ongoing'])
            ->orderBy('p.tanggal_mulai', 'ASC')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['competency_id']][] = $r;
        }
        return $map;
    }

    public function saveForProgram(int $programId, array $competencyIds): void
    {
        $db = db_connect();
        $db->table('training_competencies')->where('program_id', $programId)->delete();
        $now    = date('Y-m-d H:i:s');
        $insert = [];
        foreach ($competencyIds as $cid) {
            $insert[] = ['program_id' => $programId, 'competency_id' => (int)$cid, 'created_at' => $now, 'updated_at' => $now];
        }
        if ($insert) $db->table('training_competencies')->insertBatch($insert);
    }
}
