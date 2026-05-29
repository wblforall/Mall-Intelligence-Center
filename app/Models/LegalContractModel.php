<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalContractModel extends Model
{
    protected $table         = 'legal_contracts';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_kontrak', 'nama_vendor', 'jenis_kontrak', 'lingkup_pekerjaan',
        'mall_id', 'tanggal_mulai', 'tanggal_berakhir', 'nilai_kontrak',
        'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_contracts c')
            ->select('c.*, u.name as creator_name')
            ->join('users u', 'u.id = c.created_by', 'left');

        if (!empty($f['mall_id']))  $b->where('c.mall_id', $f['mall_id']);
        if (!empty($f['status']))   $b->where('c.status', $f['status']);
        if (!empty($f['jenis']))    $b->where('c.jenis_kontrak', $f['jenis']);
        if (!empty($f['q']))        $b->groupStart()
                                       ->like('c.nama_vendor', $f['q'])
                                       ->orLike('c.nomor_kontrak', $f['q'])
                                     ->groupEnd();
        if (!empty($f['expiring'])) $b->where('c.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('c.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('c.status', ['active', 'draft']);

        $b->orderBy('c.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_contracts')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
