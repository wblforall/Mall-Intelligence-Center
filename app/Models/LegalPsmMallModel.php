<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalPsmMallModel extends Model
{
    protected $table         = 'legal_psm_mall';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_psm', 'nama_tenant', 'unit_lokasi', 'luas_m2', 'nilai_sewa',
        'periode_pembayaran', 'mall_id', 'tanggal_mulai', 'tanggal_berakhir',
        'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_psm_mall m')
            ->select('m.*, u.name as creator_name')
            ->join('users u', 'u.id = m.created_by', 'left');

        if (!empty($f['mall_id'])) $b->where('m.mall_id', $f['mall_id']);
        if (!empty($f['status']))  $b->where('m.status', $f['status']);
        if (!empty($f['q']))       $b->groupStart()
                                      ->like('m.nama_tenant', $f['q'])
                                      ->orLike('m.nomor_psm', $f['q'])
                                      ->orLike('m.unit_lokasi', $f['q'])
                                    ->groupEnd();
        if (!empty($f['expiring'])) $b->where('m.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('m.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('m.status', ['active', 'draft']);

        $b->orderBy('m.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_psm_mall')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
