<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalPermitModel extends Model
{
    protected $table         = 'legal_permits';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_izin', 'nama_izin', 'jenis_izin', 'instansi_penerbit', 'mall_id',
        'tanggal_terbit', 'tanggal_berakhir', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_permits p')
            ->select('p.*, u.name as creator_name')
            ->join('users u', 'u.id = p.created_by', 'left');

        if (!empty($f['mall_id']))  $b->where('p.mall_id', $f['mall_id']);
        if (!empty($f['status']))   $b->where('p.status', $f['status']);
        if (!empty($f['jenis']))    $b->where('p.jenis_izin', $f['jenis']);
        if (!empty($f['q']))        $b->groupStart()
                                       ->like('p.nama_izin', $f['q'])
                                       ->orLike('p.nomor_izin', $f['q'])
                                       ->orLike('p.instansi_penerbit', $f['q'])
                                     ->groupEnd();
        if (!empty($f['expiring'])) $b->where('p.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('p.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('p.status', ['active']);

        $b->orderBy('p.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_permits')
            ->where('tanggal_berakhir IS NOT NULL')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->where('status', 'active')
            ->countAllResults();
    }
}
