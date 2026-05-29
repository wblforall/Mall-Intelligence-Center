<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalLeaseModel extends Model
{
    protected $table         = 'legal_leases';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_kontrak', 'tenant_name', 'unit_no', 'mall_id', 'jenis_sewa',
        'tanggal_mulai', 'tanggal_berakhir', 'nilai_sewa', 'periode_pembayaran',
        'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_leases l')
            ->select('l.*, u.name as creator_name')
            ->join('users u', 'u.id = l.created_by', 'left');

        if (!empty($f['mall_id']))  $b->where('l.mall_id', $f['mall_id']);
        if (!empty($f['status']))   $b->where('l.status', $f['status']);
        if (!empty($f['jenis']))    $b->where('l.jenis_sewa', $f['jenis']);
        if (!empty($f['q']))        $b->groupStart()
                                       ->like('l.tenant_name', $f['q'])
                                       ->orLike('l.nomor_kontrak', $f['q'])
                                       ->orLike('l.unit_no', $f['q'])
                                     ->groupEnd();
        if (!empty($f['expiring'])) $b->where('l.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('l.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('l.status', ['active', 'draft']);

        $b->orderBy('l.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_leases')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
