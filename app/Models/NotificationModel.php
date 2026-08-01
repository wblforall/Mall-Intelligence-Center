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

}
