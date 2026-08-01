<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanBoardMemberModel extends Model
{
    protected $table         = 'kanban_board_members';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $updatedField  = ''; // tabel hanya punya created_at
    protected $allowedFields = ['board_id', 'user_id', 'role'];

    /** Peran user pada board: 'owner'|'editor'|'viewer'|null. Owner board selalu 'owner'. */
    public function roleFor(int $boardId, int $userId): ?string
    {
        $db = db_connect();
        $board = $db->table('kanban_boards')->select('owner_id')->where('id', $boardId)->get()->getRowArray();
        if (! $board) return null;
        if ((int) $board['owner_id'] === $userId) return 'owner';

        $m = $this->where('board_id', $boardId)->where('user_id', $userId)->first();
        return $m['role'] ?? null;
    }

    /** Anggota board + nama (untuk header avatar & kelola anggota). */
    public function membersWithName(int $boardId): array
    {
        return db_connect()->table('kanban_board_members m')
            ->select('m.id, m.user_id, m.role, u.name, u.email')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.board_id', $boardId)
            ->orderBy("FIELD(m.role,'owner','editor','viewer')", '', false)
            ->orderBy('u.name')
            ->get()->getResultArray();
    }
}
