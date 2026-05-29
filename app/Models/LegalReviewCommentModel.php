<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalReviewCommentModel extends Model
{
    protected $table         = 'legal_review_comments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'review_id', 'version_id', 'parent_id', 'user_id', 'ext_name',
        'komentar', 'tipe',
    ];

    public function getThread(int $reviewId): array
    {
        $rows = $this->db->table('legal_review_comments c')
            ->select('c.*, u.name as user_name, v.versi_ke')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->join('legal_review_versions v', 'v.id = c.version_id', 'left')
            ->where('c.review_id', $reviewId)
            ->orderBy('c.created_at', 'ASC')
            ->get()->getResultArray();

        // Build tree: top-level comments with replies nested
        $byId    = [];
        $roots   = [];
        foreach ($rows as $r) {
            $r['replies'] = [];
            $byId[$r['id']] = $r;
        }
        foreach ($byId as $id => &$r) {
            if ($r['parent_id']) {
                $byId[$r['parent_id']]['replies'][] = &$r;
            } else {
                $roots[] = &$r;
            }
        }
        return $roots;
    }
}
