<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeCommentModel extends Model
{
    protected $table         = 'work_initiative_comments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['initiative_id', 'parent_id', 'body', 'author_id', 'visibility', 'created_at'];

    // Komentar Deputy → Dept (terlihat Dept Head + Deputy)
    public function deptDeputyComments(int $initiativeId): array
    {
        return $this->select('work_initiative_comments.*, e.nama AS author_name')
            ->join('employees e', 'e.id = work_initiative_comments.author_id', 'left')
            ->where('initiative_id', $initiativeId)
            ->where('visibility', 'dept_deputy')
            ->where('work_initiative_comments.parent_id IS NULL')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    // Thread GM ↔ Deputy (terlihat GM + Deputy)
    public function gmDeputyThread(int $initiativeId): array
    {
        return $this->select('work_initiative_comments.*, e.nama AS author_name')
            ->join('employees e', 'e.id = work_initiative_comments.author_id', 'left')
            ->where('initiative_id', $initiativeId)
            ->where('visibility', 'gm_deputy')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
