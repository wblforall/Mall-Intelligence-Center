<?php

namespace App\Models;

use CodeIgniter\Model;

class IdpPlanModel extends Model
{
    protected $table         = 'idp_plans';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_id','tna_period_id','periode_label','tahun','tujuan_karir','catatan',
        'status','token_atasan','persetujuan_atasan','catatan_penolakan',
        'created_by_user_id','approved_by_user_id','approved_at',
    ];

    public function getAllWithEmployee(array $filters = []): array
    {
        $db = db_connect();
        $q  = $db->table('idp_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name,
                      u.name as created_by_name,
                      (SELECT COUNT(*) FROM idp_items WHERE idp_id = p.id) as item_count,
                      (SELECT COUNT(*) FROM idp_items WHERE idp_id = p.id AND status = \'selesai\') as item_selesai')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = p.created_by_user_id', 'left');

        if (! empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (! empty($filters['dept_id'])) {
            $q->where('e.dept_id', $filters['dept_id']);
        }
        if (! empty($filters['employee_id'])) {
            $q->where('p.employee_id', $filters['employee_id']);
        }
        if (! empty($filters['tahun'])) {
            $q->where('p.tahun', $filters['tahun']);
        }

        return $q->orderBy('p.created_at', 'DESC')->get()->getResultArray();
    }

    public function getWithEmployee(int $id): ?array
    {
        $db = db_connect();
        return $db->table('idp_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, e.dept_id, e.email as employee_email,
                      e.no_hp as employee_no_hp, d.name as dept_name,
                      u.name as created_by_name, ap.name as approved_by_name,
                      a.nama as atasan_nama, a.jabatan as atasan_jabatan,
                      a.email as atasan_email, a.no_hp as atasan_no_hp,
                      tp.nama as tna_period_nama')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('employees a', 'a.id = e.atasan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = p.created_by_user_id', 'left')
            ->join('users ap', 'ap.id = p.approved_by_user_id', 'left')
            ->join('tna_periods tp', 'tp.id = p.tna_period_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray() ?: null;
    }

    public function getDashboardStats(): array
    {
        $db   = db_connect();
        $rows = $db->table('idp_plans')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()->getResultArray();

        $stats = ['draft' => 0, 'aktif' => 0, 'selesai' => 0, 'dibatalkan' => 0];
        foreach ($rows as $r) {
            if (isset($stats[$r['status']])) {
                $stats[$r['status']] = (int)$r['total'];
            }
        }
        $stats['total'] = array_sum($stats);
        return $stats;
    }
}
