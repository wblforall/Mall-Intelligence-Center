<?php

namespace App\Models;

use CodeIgniter\Model;

class PestFindingPhotoModel extends Model
{
    protected $table         = 'pest_finding_photos';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['finding_id', 'file_name', 'original_name', 'created_at'];
    protected $useTimestamps = false;

    public function byFinding(int $findingId): array
    {
        return $this->where('finding_id', $findingId)->orderBy('id')->findAll();
    }

    public function hitung(int $findingId): int
    {
        return $this->where('finding_id', $findingId)->countAllResults();
    }

    /** Nama berkas seluruh foto satu kunjungan — untuk dibersihkan setelah commit. */
    public function fileNamesByVisit(int $visitId): array
    {
        $rows = $this->db->table('pest_finding_photos p')
            ->select('p.file_name')
            ->join('pest_findings f', 'f.id = p.finding_id')
            ->where('f.visit_id', $visitId)
            ->get()->getResultArray();
        return array_column($rows, 'file_name');
    }

    public function fileNamesByFinding(int $findingId): array
    {
        $rows = $this->db->table('pest_finding_photos')
            ->select('file_name')->where('finding_id', $findingId)
            ->get()->getResultArray();
        return array_column($rows, 'file_name');
    }
}
