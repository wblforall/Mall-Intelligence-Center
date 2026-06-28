<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalPsmDeveloperModel extends Model
{
    protected $table         = 'legal_psm_developer';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_psm', 'nama_developer', 'objek_perjanjian', 'nilai',
        'mall_id', 'tanggal_mulai', 'tanggal_berakhir', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_psm_developer d')
            ->select('d.*, u.name as creator_name')
            ->join('users u', 'u.id = d.created_by', 'left');

        if (!empty($f['mall_id'])) $b->where('d.mall_id', $f['mall_id']);
        if (!empty($f['status']))  $b->where('d.status', $f['status']);
        if (!empty($f['q']))       $b->groupStart()
                                      ->like('d.nama_developer', $f['q'])
                                      ->orLike('d.nomor_psm', $f['q'])
                                      ->orLike('d.objek_perjanjian', $f['q'])
                                    ->groupEnd();
        if (!empty($f['expiring'])) $b->where('d.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('d.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('d.status', ['active', 'draft']);

        $b->orderBy('d.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_psm_developer')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
