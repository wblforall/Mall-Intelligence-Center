<?php

namespace App\Libraries;

use App\Models\NotificationModel;

/**
 * Pusat notifikasi in-app (tabel `notifications`, generik lintas modul):
 * approval masuk, hasil approval, komentar/balasan Progress Report,
 * pengingat cron. Semua modul memakai helper yang sama — app mobile kelak
 * membaca sumber yang sama (MIC_MOBILE_DESIGN.md §5).
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
        $baris   = [];
        $now     = date('Y-m-d H:i:s');
        foreach ($userIds as $uid) {
            if ($uid <= 0 || $uid === $actorId) continue;
            $baris[] = [
                'user_id'    => $uid,
                'actor_id'   => $actorId ?: null,
                'module'     => $module,
                'type'       => $type,
                'title'      => mb_substr($title, 0, 150),
                'body'       => $body !== null ? mb_substr($body, 0, 500) : null,
                'link_type'  => $linkType,
                'link_id'    => $linkId,
                'url'        => $url,
                'created_at' => $now,
            ];
        }
        if (! $baris) return;

        // Satu query untuk semua penerima — penerima berbasis peran bisa
        // berjumlah puluhan (mis. seluruh pengelola HR).
        (new NotificationModel())->insertBatch($baris);
    }
}
