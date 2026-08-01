<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanListModel extends Model
{
    protected $table         = 'kanban_lists';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['board_id', 'nama', 'position', 'is_archived'];

    public function forBoard(int $boardId, bool $archived = false): array
    {
        return $this->where('board_id', $boardId)
            ->where('is_archived', $archived ? 1 : 0)
            ->orderBy('position')->orderBy('id')
            ->findAll();
    }
}
