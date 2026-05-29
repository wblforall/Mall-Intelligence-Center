<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalReviewVersionModel extends Model
{
    protected $table         = 'legal_review_versions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'review_id', 'versi_ke', 'file_path', 'file_size',
        'catatan_perubahan', 'uploaded_by', 'uploaded_at',
    ];

    public function getLatest(int $reviewId): ?array
    {
        return $this->db->table('legal_review_versions v')
            ->select('v.*, u.name as uploader_name')
            ->join('users u', 'u.id = v.uploaded_by', 'left')
            ->where('v.review_id', $reviewId)
            ->orderBy('v.versi_ke', 'DESC')
            ->limit(1)
            ->get()->getRowArray() ?: null;
    }

    public function getNextVersi(int $reviewId): int
    {
        $row = $this->db->table('legal_review_versions')
            ->selectMax('versi_ke')
            ->where('review_id', $reviewId)
            ->get()->getRowArray();
        return ((int)($row['versi_ke'] ?? 0)) + 1;
    }

    public function deleteWithFile(int $id): bool
    {
        $v = $this->find($id);
        if (!$v) return false;
        $this->delete($id);
        $path = FCPATH . $v['file_path'];
        if (file_exists($path)) unlink($path);
        return true;
    }
}
