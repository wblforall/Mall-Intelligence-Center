<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeTrainingModel extends Model
{
    protected $table         = 'employee_trainings';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'nama', 'tipe', 'penyelenggara',
        'tanggal_mulai', 'tanggal_selesai',
        'sertifikat_file', 'sertifikat_original', 'catatan',
    ];
    protected $useTimestamps = true;

    public function getByEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('tanggal_mulai', 'DESC')
            ->findAll();
    }
}
