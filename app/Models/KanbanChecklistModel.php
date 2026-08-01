<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanChecklistModel extends Model
{
    protected $table         = 'kanban_checklists';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['card_id', 'judul', 'position'];
}
