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

    // Sum nilai from active deals (terkonfirmasi + lunas) per program
    public function getCommittedByPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $rows = $this->db->table('sponsorship_sponsors')
            ->select('program_id, SUM(nilai) as total_nilai, COUNT(*) as total_sponsor')
            ->whereIn('program_id', $programIds)
            ->whereIn('status_deal', ['terkonfirmasi', 'lunas'])
            ->groupBy('program_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['program_id']] = [
                'total_nilai'   => (int)$r['total_nilai'],
                'total_sponsor' => (int)$r['total_sponsor'],
            ];
        }
        return $map;
    }
}
