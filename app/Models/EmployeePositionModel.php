<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeePositionModel extends Model
{
    protected $table         = 'employee_positions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['employee_id', 'jabatan', 'dept_id', 'tanggal_mulai', 'tanggal_selesai', 'keterangan'];
    protected $useTimestamps = true;

    public function getByEmployee(int $employeeId): array
    {
        return $this->db->table('employee_positions p')
            ->select('p.*, d.name as dept_name')
            ->join('departments d', 'd.id = p.dept_id', 'left')
            ->where('p.employee_id', $employeeId)
            ->orderBy('p.tanggal_mulai', 'DESC')
            ->get()->getResultArray();
    }
}
