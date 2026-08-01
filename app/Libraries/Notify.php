<?php

namespace App\Libraries;

use App\Models\NotificationModel;

/**
 * Pusat notifikasi in-app (tabel `notifications`, generik lintas modul).
 * Dipakai kanban sekarang; modul lain (approval/reminder) & push mobile
 * tinggal memanggil helper yang sama.
 */
class Notify
{
    /**
     * Kirim notifikasi ke banyak user sekaligus. Pengirim ($actorId) otomatis
     * dikecualikan; duplikat user dirapikan.
     *
     * @param int[] $userIds
     */
    public static function send(
        array $userIds,
        int $actorId,
        string $module,
        string $type,
        string $title,
        ?string $body = null,
        ?string $linkType = null,
        ?int $linkId = null,
        ?string $url = null
    ): void {
        $userIds = array_unique(array_map('intval', $userIds));
        $model   = new NotificationModel();
        foreach ($userIds as $uid) {
            if ($uid <= 0 || $uid === $actorId) continue;
            $model->insert([
                'user_id'   => $uid,
                'actor_id'  => $actorId ?: null,
                'module'    => $module,
                'type'      => $type,
                'title'     => mb_substr($title, 0, 150),
                'body'      => $body !== null ? mb_substr($body, 0, 500) : null,
                'link_type' => $linkType,
                'link_id'   => $linkId,
                'url'       => $url,
            ]);
        }
    }

    /** Tandai terbaca semua notif user utk satu tautan (mis. buka kartu). */
    public static function markReadForLink(int $userId, string $linkType, int $linkId): void
    {
        db_connect()->table('notifications')
            ->where('user_id', $userId)->where('is_read', 0)
            ->where('link_type', $linkType)->where('link_id', $linkId)
            ->update(['is_read' => 1]);
    }
}
