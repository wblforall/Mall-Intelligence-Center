<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanCardModel extends Model
{
    protected $table         = 'kanban_cards';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'board_id', 'list_id', 'judul', 'deskripsi', 'position',
        'due_date', 'due_done', 'cover_color', 'is_archived', 'created_by',
    ];

    /** Semua kartu aktif satu board, dikelompok per list_id (untuk render & state). */
    public function byListForBoard(int $boardId): array
    {
        $rows = $this->where('board_id', $boardId)
            ->where('is_archived', 0)
            ->orderBy('position')->orderBy('id')
            ->findAll();

        $grouped = [];
        foreach ($rows as $r) $grouped[(int) $r['list_id']][] = $r;
        return $grouped;
    }
}
