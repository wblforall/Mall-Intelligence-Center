<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalTemplateModel extends Model
{
    protected $table         = 'appraisal_templates';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'jabatan_id', 'nama', 'status', 'bobot_kpi', 'bobot_kompetensi',
        'created_by', 'submitted_at', 'approved_by', 'approved_at', 'catatan_hr',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    public function getForJabatan(int $jabatanId): ?array
    {
        return $this->where('jabatan_id', $jabatanId)->first();
    }

    /** Daftar template + nama jabatan/dept untuk listing. */
    public function listWithJabatan(): array
    {
        return $this->db->table('appraisal_templates t')
            ->select('t.*, j.nama AS jabatan_nama, j.grade AS grade, j.dept_id AS jabatan_dept_id, d.name AS dept_name')
            ->join('jabatans j', 'j.id = t.jabatan_id', 'left')
            ->join('departments d', 'd.id = j.dept_id', 'left')
            ->orderBy('d.name')->orderBy('j.grade')
            ->get()->getResultArray();
    }
}
