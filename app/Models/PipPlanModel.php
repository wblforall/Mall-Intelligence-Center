<?php

namespace App\Models;

use CodeIgniter\Model;

class PipPlanModel extends Model
{
    protected $table      = 'pip_plans';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_id','created_by_user_id','approved_by_user_id','judul','alasan',
        'level_sp','dukungan','konsekuensi',
        'persetujuan_atasan','catatan_penolakan_atasan','token_atasan',
        'persetujuan_karyawan','catatan_penolakan','token_karyawan',
        'tanggal_mulai','tanggal_selesai','frekuensi_review','status','catatan_penutup',
    ];

    public function getAllWithEmployee(array $filters = []): array
    {
        $db = db_connect();
        $q  = $db->table('pip_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name,
                      u.name as created_by_name, ap.name as approved_by_name, a.nama as atasan_nama,
                      (SELECT COUNT(*) FROM pip_items WHERE pip_id = p.id) as item_count,
                      (SELECT COUNT(*) FROM pip_reviews WHERE pip_id = p.id) as review_count,
                      (SELECT MAX(tanggal_review) FROM pip_reviews WHERE pip_id = p.id) as last_review_date')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('employees a', 'a.id = e.atasan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = p.created_by_user_id', 'left')
            ->join('users ap', 'ap.id = p.approved_by_user_id', 'left');

        if (! empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (! empty($filters['dept_id'])) {
            $q->where('e.dept_id', $filters['dept_id']);
        }
        if (! empty($filters['employee_id'])) {
            $q->where('p.employee_id', $filters['employee_id']);
        }

        return $q->orderBy('p.tanggal_mulai', 'DESC')->get()->getResultArray();
    }

    public function getWithEmployee(int $id): ?array
    {
        $db = db_connect();
        return $db->table('pip_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, e.dept_id, e.no_hp as employee_no_hp,
                      d.name as dept_name, u.name as created_by_name, ap.name as approved_by_name,
                      a.nama as atasan_nama, a.jabatan as atasan_jabatan, a.no_hp as atasan_no_hp')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('employees a', 'a.id = e.atasan_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = p.created_by_user_id', 'left')
            ->join('users ap', 'ap.id = p.approved_by_user_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray() ?: null;
    }

    public static function nextReviewDate(array $plan): ?string
    {
        $frek = $plan['frekuensi_review'] ?? 'mingguan';
        $days = match($frek) {
            '2mingguan' => 14,
            'bulanan'   => 30,
            default     => 7,
        };
        $base = $plan['last_review_date'] ?? $plan['tanggal_mulai'];
        return date('Y-m-d', strtotime($base . ' +' . $days . ' days'));
    }

    public function getDashboardStats(): array
    {
        $db = db_connect();
        $rows = $db->table('pip_plans')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()->getResultArray();

        $stats = ['draft' => 0, 'menunggu_persetujuan' => 0, 'aktif' => 0, 'selesai' => 0, 'diperpanjang' => 0, 'dihentikan' => 0];
        foreach ($rows as $r) {
            $stats[$r['status']] = (int)$r['total'];
        }
        $stats['total'] = array_sum($stats);
        return $stats;
    }
}
