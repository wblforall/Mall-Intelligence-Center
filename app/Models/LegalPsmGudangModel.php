<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalPsmGudangModel extends Model
{
    protected $table         = 'legal_psm_gudang';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_psm', 'nama_penyewa', 'lokasi_gudang', 'luas_m2', 'nilai_sewa',
        'periode_pembayaran', 'tanggal_mulai', 'tanggal_berakhir', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_psm_gudang g')
            ->select('g.*, u.name as creator_name')
            ->join('users u', 'u.id = g.created_by', 'left');

        if (!empty($f['status'])) $b->where('g.status', $f['status']);
        if (!empty($f['q']))      $b->groupStart()
                                     ->like('g.nama_penyewa', $f['q'])
                                     ->orLike('g.nomor_psm', $f['q'])
                                     ->orLike('g.lokasi_gudang', $f['q'])
                                   ->groupEnd();
        if (!empty($f['expiring'])) $b->where('g.tanggal_berakhir <=', date('Y-m-d', strtotime("+{$f['expiring']} days")))
                                       ->where('g.tanggal_berakhir >=', date('Y-m-d'))
                                       ->whereIn('g.status', ['active', 'draft']);

        $b->orderBy('g.tanggal_berakhir', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_psm_gudang')
            ->where('tanggal_berakhir <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_berakhir >=', date('Y-m-d'))
            ->whereIn('status', ['active', 'draft'])
            ->countAllResults();
    }
}
