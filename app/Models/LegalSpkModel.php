<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalSpkModel extends Model
{
    protected $table         = 'legal_spk';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_spk', 'nama_vendor', 'deskripsi_pekerjaan', 'nilai_spk',
        'tanggal_terbit', 'tanggal_selesai', 'pic_user_id', 'status', 'catatan', 'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_spk s')
            ->select('s.*, u.name as creator_name, p.name as pic_name')
            ->join('users u', 'u.id = s.created_by', 'left')
            ->join('users p', 'p.id = s.pic_user_id', 'left');

        if (!empty($f['status'])) $b->where('s.status', $f['status']);
        if (!empty($f['q']))      $b->groupStart()
                                     ->like('s.nama_vendor', $f['q'])
                                     ->orLike('s.nomor_spk', $f['q'])
                                     ->orLike('s.deskripsi_pekerjaan', $f['q'])
                                   ->groupEnd();

        $b->orderBy('s.tanggal_selesai', 'ASC');
        return $b->get()->getResultArray();
    }

    public function getExpiringCount(int $days = 30): int
    {
        return $this->db->table('legal_spk')
            ->where('tanggal_selesai <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('tanggal_selesai >=', date('Y-m-d'))
            ->whereIn('status', ['draft', 'aktif'])
            ->countAllResults();
    }
}
