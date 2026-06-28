<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalPksModel extends Model
{
    protected $table         = 'legal_pks';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_pks', 'pihak_kedua', 'ruang_lingkup', 'nilai',
        'tanggal_mulai', 'tanggal_berakhir', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_pks p')
            ->select('p.*, u.name as creator_name')
            ->join('users u', 'u.id = p.created_by', 'left');

        if (!empty($f['status'])) $b->where('p.status', $f['status']);
        if (!empty($f['q']))      $b->groupStart()
                                     ->like('p.pihak_kedua', $f['q'])
                                     ->orLike('p.nomor_pks', $f['q'])
                                   ->groupEnd();
        if (!empty($f['expiring'])) $b->where('p.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('p.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('p.status', ['active', 'draft']);

        $b->orderBy('p.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_pks')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
