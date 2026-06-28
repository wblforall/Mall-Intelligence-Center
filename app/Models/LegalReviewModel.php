<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalReviewModel extends Model
{
    protected $table         = 'legal_reviews';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'judul', 'deskripsi', 'entity_type', 'entity_id', 'status',
        'ext_token', 'ext_token_at', 'ext_link_active', 'ext_party_name',
        'created_by',
    ];

    public function getFiltered(array $f = []): array
    {
        $b = $this->db->table('legal_reviews r')
            ->select('r.*, u.name as creator_name,
                (SELECT versi_ke FROM legal_review_versions WHERE review_id = r.id ORDER BY versi_ke DESC LIMIT 1) as versi_terkini,
                (SELECT MAX(created_at) FROM legal_review_comments WHERE review_id = r.id) as last_comment_at')
            ->join('users u', 'u.id = r.created_by', 'left');

        if (!empty($f['status']))      $b->where('r.status', $f['status']);
        if (!empty($f['created_by']))  $b->where('r.created_by', $f['created_by']);
        if (!empty($f['assigned_to'])) {
            $b->join('legal_review_assignees ra', 'ra.review_id = r.id')
              ->where('ra.user_id', $f['assigned_to']);
        }
        if (!empty($f['needs_action_by'])) {
            $userId = $f['needs_action_by'];
            $b->join('legal_review_assignees ra2', 'ra2.review_id = r.id', 'left')
              ->groupStart()
                ->groupStart()
                  ->where('r.status', 'in_review')
                  ->where('ra2.user_id', $userId)
                ->groupEnd()
                ->orGroupStart()
                  ->whereIn('r.status', ['draft', 'revision'])
                  ->where('r.created_by', $userId)
                ->groupEnd()
              ->groupEnd();
        }
        if (!empty($f['q'])) $b->like('r.judul', $f['q']);

        $b->orderBy('r.updated_at', 'DESC');
        return $b->get()->getResultArray();
    }

    public function getWithDetails(int $id): ?array
    {
        $row = $this->db->table('legal_reviews r')
            ->select('r.*, u.name as creator_name')
            ->join('users u', 'u.id = r.created_by', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();

        if (!$row) return null;

        $row['assignees'] = $this->db->table('legal_review_assignees a')
            ->select('a.*, u.name as user_name, u.department_id')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.review_id', $id)
            ->get()->getResultArray();

        $row['versions'] = $this->db->table('legal_review_versions v')
            ->select('v.*, u.name as uploader_name')
            ->join('users u', 'u.id = v.uploaded_by', 'left')
            ->where('v.review_id', $id)
            ->orderBy('v.versi_ke', 'DESC')
            ->get()->getResultArray();

        return $row;
    }

    public function generateExtToken(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update($id, [
            'ext_token'       => $token,
            'ext_token_at'    => date('Y-m-d H:i:s'),
            'ext_link_active' => 1,
        ]);
        return $token;
    }

    public function findByToken(string $token): ?array
    {
        return $this->db->table('legal_reviews')
            ->where('ext_token', $token)
            ->where('ext_link_active', 1)
            ->get()->getRowArray() ?: null;
    }
}
