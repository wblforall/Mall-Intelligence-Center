<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanBoardModel extends Model
{
    protected $table         = 'kanban_boards';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama', 'deskripsi', 'color', 'owner_id', 'event_id', 'dept_id', 'is_archived',
    ];

    /**
     * Daftar board yang bisa diakses user (owner ATAU anggota; admin = semua),
     * plus kolom `my_role` dan `member_count`.
     */
    public function boardsForUser(int $userId, bool $isAdmin, bool $archived = false): array
    {
        $db = db_connect();
        $b  = $db->table('kanban_boards b')
            ->select('b.*, m.role AS my_role,
                      (SELECT COUNT(*) FROM kanban_board_members bm WHERE bm.board_id = b.id) AS member_count,
                      (SELECT COUNT(*) FROM kanban_cards c WHERE c.board_id = b.id AND c.is_archived = 0) AS card_count')
            ->join('kanban_board_members m', 'm.board_id = b.id AND m.user_id = ' . $userId, 'left')
            ->where('b.is_archived', $archived ? 1 : 0)
            ->orderBy('b.updated_at', 'DESC');

        if (! $isAdmin) {
            $b->groupStart()->where('b.owner_id', $userId)->orWhere('m.user_id', $userId)->groupEnd();
        }

        $rows = $b->get()->getResultArray();
        foreach ($rows as &$r) {
            if ((int) $r['owner_id'] === $userId) $r['my_role'] = 'owner';
            elseif ($r['my_role'] === null)       $r['my_role'] = $isAdmin ? 'admin' : null;
        }
        return $rows;
    }

    /** Bump updated_at board — penanda versi murah untuk polling sync. */
    public function touch(int $boardId): void
    {
        db_connect()->table('kanban_boards')
            ->where('id', $boardId)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }
}
