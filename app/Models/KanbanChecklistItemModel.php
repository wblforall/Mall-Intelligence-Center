<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanChecklistItemModel extends Model
{
    protected $table         = 'kanban_checklist_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['checklist_id', 'teks', 'is_done', 'position', 'done_by', 'done_at'];
}
