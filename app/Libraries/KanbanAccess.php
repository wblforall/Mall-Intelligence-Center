<?php

namespace App\Libraries;

use App\Models\KanbanBoardMemberModel;

/**
 * Helper akses board Kanban (KANBAN_DESIGN.md §3).
 * Peran efektif: 'admin' | 'owner' | 'editor' | 'viewer' | null.
 * Admin sistem bypass (bisa lihat & masuk semua board).
 */
class KanbanAccess
{
    public static function role(int $boardId, int $userId, bool $isAdmin): ?string
    {
        if ($isAdmin) {
            // Tetap hormati peran eksplisit bila ada (owner tampil sebagai owner),
            // selain itu admin dapat akses penuh.
            $r = (new KanbanBoardMemberModel())->roleFor($boardId, $userId);
            return $r ?? 'admin';
        }
        return (new KanbanBoardMemberModel())->roleFor($boardId, $userId);
    }

    /** Boleh mengubah isi board (kartu/list/dst)? viewer = tidak. */
    public static function canEdit(?string $role): bool
    {
        return in_array($role, ['admin', 'owner', 'editor'], true);
    }

    /** Boleh kelola board (rename/arsip/anggota/label/hapus)? */
    public static function canManage(?string $role): bool
    {
        return in_array($role, ['admin', 'owner'], true);
    }
}
