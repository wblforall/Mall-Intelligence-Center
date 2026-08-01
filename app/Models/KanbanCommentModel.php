<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanCommentModel extends Model
{
    protected $table         = 'kanban_comments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['card_id', 'user_id', 'body'];

    public function forCard(int $cardId): array
    {
        return db_connect()->table('kanban_comments c')
            ->select('c.*, u.name AS user_name')
            ->join('users u', 'u.id = c.user_id')
            ->where('c.card_id', $cardId)
            ->orderBy('c.id', 'DESC')
            ->get()->getResultArray();
    }
}
