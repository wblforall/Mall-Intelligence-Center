<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanLabelModel extends Model
{
    protected $table         = 'kanban_labels';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['board_id', 'nama', 'color'];

    public function forBoard(int $boardId): array
    {
        return $this->where('board_id', $boardId)->orderBy('id')->findAll();
    }
}
