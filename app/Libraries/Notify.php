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

        self::antrikanPush($baris);
    }

    /**
     * Titipkan notifikasi yang sama ke antrian push (app mobile).
     *
     * Ditulis ke tabel, BUKAN dikirim di sini: satu panggilan FCM per perangkat
     * akan menahan request web sampai belasan detik untuk penerima yang banyak.
     * Cron `mic:push-dispatch` yang mengirimkannya. Kegagalan di sini tidak
     * boleh menjatuhkan aksi utama user — karena itu dibungkus try/catch.
     */
    private static function antrikanPush(array $baris): void
    {
        try {
            $db  = db_connect();
            if (! $db->tableExists('push_queue')) return; // belum migrasi

            $antri = [];
            foreach ($baris as $r) {
                $antri[] = [
                    'user_id'    => $r['user_id'],
                    'title'      => $r['title'],
                    'body'       => $r['body'],
                    'url'        => $r['url'],
                    'module'     => $r['module'],
                    'status'     => 'pending',
                    'created_at' => $r['created_at'],
                ];
            }
            if ($antri) $db->table('push_queue')->insertBatch($antri);
        } catch (\Throwable $e) {
            log_message('error', 'Notify: gagal mengantrikan push — ' . $e->getMessage());
        }
    }
}
