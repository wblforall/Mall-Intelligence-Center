<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipSponsorModel extends Model
{
    protected $table         = 'sponsorship_sponsors';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'nama_sponsor', 'kategori', 'jenis', 'nilai',
        'status_deal', 'detail', 'catatan', 'created_by',
    ];

    // Keyed by program_id → list of sponsor deals
    public function getByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->whereIn('program_id', $programIds)
                     ->orderBy('program_id')
                     ->orderBy('status_deal')
                     ->orderBy('nama_sponsor')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['program_id']][] = $row;
        }
        return $grouped;
    }

    // Sum nilai from active deals (terkonfirmasi + lunas) per program, split by jenis
    public function getCommittedByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('sponsorship_sponsors')
            ->select('program_id, jenis, SUM(nilai) as total_nilai, COUNT(*) as total_sponsor')
            ->whereIn('program_id', $programIds)
            ->whereIn('status_deal', ['terkonfirmasi', 'lunas'])
            ->groupBy('program_id, jenis')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $pid = (int)$r['program_id'];
            if (! isset($map[$pid])) {
                $map[$pid] = ['total_nilai' => 0, 'total_cash' => 0, 'total_barang' => 0, 'total_sponsor' => 0];
            }
            $map[$pid]['total_nilai']   += (int)$r['total_nilai'];
            $map[$pid]['total_sponsor'] += (int)$r['total_sponsor'];
            if ($r['jenis'] === 'cash') {
                $map[$pid]['total_cash']   = (int)$r['total_nilai'];
            } else {
                $map[$pid]['total_barang'] = (int)$r['total_nilai'];
            }
        }
        return $map;
    }
}
