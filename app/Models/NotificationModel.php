<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Notifikasi in-app GENERIK lintas modul (lonceng navbar).
 * Semua modul menulis lewat App\Libraries\Notify::send().
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

    /**
     * ID tautan yang punya notif belum-dibaca untuk user pada satu jenis tautan
     * — dipakai halaman modul untuk memberi badge "belum dibaca" per item.
     */
    public function unreadLinkIds(int $userId, string $linkType): array
    {
        $rows = $this->select('link_id')
            ->where('user_id', $userId)->where('is_read', 0)
            ->where('link_type', $linkType)
            ->findAll();
        return array_map(static fn ($r) => (int) $r['link_id'], $rows);
    }
}
