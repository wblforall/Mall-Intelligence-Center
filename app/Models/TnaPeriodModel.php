<?php

namespace App\Models;

use CodeIgniter\Model;

class TnaPeriodModel extends Model
{
    protected $table         = 'tna_periods';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'status', 'catatan',
                                'weight_self', 'weight_atasan', 'weight_rekan'];
    protected $useTimestamps = true;

    public function getAllWithStats(): array
    {
        return $this->db->table('tna_periods p')
            ->select('p.*, COUNT(DISTINCT a.employee_id) AS employee_count,
                      COUNT(a.id) AS total_forms,
                      SUM(a.status = "submitted") AS submitted_forms')
            ->join('tna_assessments a', 'a.period_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.tahun', 'DESC')
            ->orderBy('p.tanggal_mulai', 'DESC')
            ->get()->getResultArray();
    }
}
