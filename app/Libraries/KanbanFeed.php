<?php

namespace App\Libraries;

/**
 * Feed aktivitas per board (tabel kanban_activity) — tampil di modal kartu.
 * Terpisah dari ActivityLog global MIC (audit) sesuai KANBAN_DESIGN.md §2.10.
 */
class KanbanFeed
{
    public static function log(int $boardId, ?int $cardId, int $userId, string $action, string $detail = ''): void
    {
        db_connect()->table('kanban_activity')->insert([
            'board_id'   => $boardId,
            'card_id'    => $cardId,
            'user_id'    => $userId,
            'action'     => $action,
            'detail'     => mb_substr($detail, 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function forCard(int $cardId, int $limit = 30): array
    {
        return db_connect()->table('kanban_activity a')
            ->select('a.*, u.name AS user_name')
            ->join('users u', 'u.id = a.user_id')
            ->where('a.card_id', $cardId)
            ->orderBy('a.id', 'DESC')->limit($limit)
            ->get()->getResultArray();
    }
}
