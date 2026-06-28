<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalKontrakPameranModel extends Model
{
    protected $table         = 'legal_kontrak_pameran';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_kontrak', 'nama_penyelenggara', 'nama_event', 'lokasi_area',
        'mall_id', 'tanggal_mulai', 'tanggal_selesai', 'nilai_sewa', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_kontrak_pameran k')
            ->select('k.*, u.name as creator_name')
            ->join('users u', 'u.id = k.created_by', 'left');

        if (!empty($f['mall_id'])) $b->where('k.mall_id', $f['mall_id']);
        if (!empty($f['status']))  $b->where('k.status', $f['status']);
        if (!empty($f['q']))       $b->groupStart()
                                      ->like('k.nama_penyelenggara', $f['q'])
                                      ->orLike('k.nama_event', $f['q'])
                                      ->orLike('k.nomor_kontrak', $f['q'])
                                    ->groupEnd();

        $b->orderBy('k.tanggal_mulai', 'DESC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_kontrak_pameran')
            ->where('tanggal_selesai <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_selesai >=', date('Y-m-d'))
            ->whereIn('status', ['draft', 'aktif'])
            ->countAllResults();
    }
}
