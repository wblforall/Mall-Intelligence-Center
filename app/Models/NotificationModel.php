<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Notifikasi in-app GENERIK lintas modul (lonceng navbar).
 * Kanban = produser pertama; modul lain & app mobile memakai tabel yang sama.
 */
class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = [
        'user_id', 'actor_id', 'module', 'type', 'title', 'body',
        'link_type', 'link_id', 'url', 'is_read',
    ];

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function latestFor(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)->orderBy('id', 'DESC')->findAll($limit);
    }

    /** Card id yang punya notif belum-dibaca utk user (badge merah kartu board). */
    public function unreadCardIds(int $userId): array
    {
        $rows = $this->select('link_id')
            ->where('user_id', $userId)->where('is_read', 0)
            ->where('link_type', 'kanban_card')
            ->findAll();
        return array_map(static fn ($r) => (int) $r['link_id'], $rows);
    }
}
