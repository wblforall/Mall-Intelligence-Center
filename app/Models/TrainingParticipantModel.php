<?php

namespace App\Models;

use CodeIgniter\Model;

class TrainingParticipantModel extends Model
{
    protected $table         = 'training_participants';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'program_id', 'employee_id', 'status_kehadiran', 'pre_test', 'post_test', 'catatan',
    ];
    protected $useTimestamps = true;

    public function getByProgram(int $programId): array
    {
        return $this->db->table('training_participants tp')
            ->select('tp.*, e.nama AS emp_nama, e.jabatan, d.name AS dept_name')
            ->join('employees e', 'e.id = tp.employee_id')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('tp.program_id', $programId)
            ->orderBy('e.nama')
            ->get()->getResultArray();
    }
}
