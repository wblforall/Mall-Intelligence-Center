<?php

namespace App\Models;

use CodeIgniter\Model;

class TnaAssessmentModel extends Model
{
    protected $table         = 'tna_assessments';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'period_id', 'employee_id', 'assessor_type', 'assessor_name', 'status', 'submitted_at',
        'fill_token', 'token_expires_at',
    ];
    protected $useTimestamps = true;

    // Returns one aggregated row per employee in the period
    public function getEmployeesByPeriod(int $periodId): array
    {
        return $this->db->table('tna_assessments a')
            ->select('a.employee_id, e.nama AS emp_nama, e.jabatan, d.name AS dept_name,
                      e.dept_id, e.atasan_id, atasan.nama AS atasan_nama,
                      j.grade AS jabatan_grade,
                      COUNT(a.id) AS total_forms,
                      SUM(a.status = "submitted") AS submitted_forms,
                      MAX(a.assessor_type = "self") AS has_self,
                      MAX(a.assessor_type = "atasan") AS has_atasan,
                      SUM(a.assessor_type = "rekan") AS rekan_count')
            ->join('employees e',       'e.id = a.employee_id',    'left')
            ->join('departments d',     'd.id = e.dept_id',        'left')
            ->join('employees atasan',  'atasan.id = e.atasan_id', 'left')
            ->join('jabatans j',        'j.id = e.jabatan_id',     'left')
            ->where('a.period_id', $periodId)
            ->groupBy('a.employee_id')
            ->orderBy('e.nama')
            ->get()->getResultArray();
    }

    // Returns all individual assessment records for one employee in one period
    public function getByPeriodEmployee(int $periodId, int $empId): array
    {
        return $this->where('period_id', $periodId)
                    ->where('employee_id', $empId)
                    ->orderBy('assessor_type')
                    ->orderBy('id')
                    ->findAll();
    }
}
