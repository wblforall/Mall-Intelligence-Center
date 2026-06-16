<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalFormModel extends Model
{
    protected $table         = 'appraisal_forms';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'period_id', 'employee_id', 'jabatan_id', 'template_id',
        'bobot_kpi', 'bobot_kompetensi', 'skor_kpi', 'skor_kompetensi', 'nilai_akhir',
        'status', 'current_user_id', 'penilai_id', 'pendapat_karyawan',
        'finalized_by', 'finalized_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Form satu periode + identitas karyawan, untuk listing HR. */
    public function listByPeriod(int $periodId): array
    {
        return $this->db->table('appraisal_forms f')
            ->select('f.*, e.nama AS employee_nama, e.nik, d.name AS dept_name, j.nama AS jabatan_nama')
            ->join('employees e', 'e.id = f.employee_id', 'left')
            ->join('jabatans j', 'j.id = f.jabatan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('f.period_id', $periodId)
            ->orderBy('d.name')->orderBy('e.nama')
            ->get()->getResultArray();
    }

    /** Form yang sedang menunggu aksi user tertentu (penilai/reviewer). */
    public function inboxFor(int $userId): array
    {
        return $this->db->table('appraisal_forms f')
            ->select('f.*, e.nama AS employee_nama, e.nik, d.name AS dept_name, j.nama AS jabatan_nama, p.nama AS periode_nama')
            ->join('employees e', 'e.id = f.employee_id', 'left')
            ->join('jabatans j', 'j.id = f.jabatan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('appraisal_periods p', 'p.id = f.period_id', 'left')
            ->where('f.current_user_id', $userId)
            ->whereIn('f.status', ['input', 'in_review'])
            ->orderBy('p.nama')->orderBy('e.nama')
            ->get()->getResultArray();
    }

    public function countInboxFor(int $userId): int
    {
        return $this->where('current_user_id', $userId)
            ->whereIn('status', ['input', 'in_review'])
            ->countAllResults();
    }

    public function getDetail(int $id): ?array
    {
        return $this->db->table('appraisal_forms f')
            ->select('f.*, e.nama AS employee_nama, e.nik, e.user_id AS employee_user_id, d.name AS dept_name, j.nama AS jabatan_nama, p.nama AS periode_nama, p.status AS periode_status')
            ->join('employees e', 'e.id = f.employee_id', 'left')
            ->join('jabatans j', 'j.id = f.jabatan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('appraisal_periods p', 'p.id = f.period_id', 'left')
            ->where('f.id', $id)
            ->get()->getRowArray();
    }
}
