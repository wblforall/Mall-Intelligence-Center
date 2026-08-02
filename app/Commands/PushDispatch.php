<?php

namespace App\Commands;

use App\Libraries\PushSender;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Kirim antrian push notification ke perangkat (FCM).
 *
 * Cron yang disarankan — tiap menit:
 *   * * * * * cd ~/public_html/mic && php spark mic:push-dispatch
 *
 * Aman dijalankan sebelum Firebase disiapkan: bila kredensial belum ada,
 * antrian ditandai `skipped` dan tak menumpuk selamanya.
 */
class PushDispatch extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:push-dispatch';
    protected $description = 'Kirim antrian push notification ke perangkat (FCM)';
    protected $usage       = 'mic:push-dispatch [--batas N] [--dry-run]';

    private const MAKS_PERCOBAAN = 3;

    public function run(array $params)
    {
        $batas  = (int) (CLI::getOption('batas') ?: 200);
        $kering = (bool) CLI::getOption('dry-run');
        $db     = db_connect();

        $antrian = $db->table('push_queue')
            ->where('status', 'pending')
            ->where('attempts <', self::MAKS_PERCOBAAN)
            ->orderBy('created_at')->limit($batas)->get()->getResultArray();

        if (! $antrian) { CLI::write('Antrian kosong.', 'yellow'); return; }
        CLI::write(count($antrian) . ' notifikasi dalam antrian.', 'white');

        if (! PushSender::terkonfigurasi()) {
            $ids = array_column($antrian, 'id');
            if (! $kering) {
                $db->table('push_queue')->whereIn('id', $ids)
                    ->update(['status' => 'skipped', 'last_error' => 'FCM belum dikonfigurasi', 'sent_at' => date('Y-m-d H:i:s')]);
            }
            CLI::write('FCM belum dikonfigurasi (fcm.projectId / fcm.credentialsPath di .env) — ' . count($ids) . ' item dilewati.', 'yellow');
            return;
        }

        // Ambil semua perangkat sekali, bukan per notifikasi.
        $perangkat = [];
        foreach ($db->table('api_tokens')->select('user_id, token, push_token')
            ->where('push_token IS NOT NULL', null, false)
            ->where('expires_at >', date('Y-m-d H:i:s'))->get()->getResultArray() as $t) {
            $perangkat[(int) $t['user_id']][] = $t;
        }

        $terkirim = $gagal = $lewat = 0;
        foreach ($antrian as $n) {
            $devices = $perangkat[(int) $n['user_id']] ?? [];
            if (! $devices) {
                // User belum pernah membuka app — bukan kegagalan.
                $lewat++;
                if (! $kering) $this->tandai($db, $n['id'], 'skipped', 'Tidak ada perangkat terdaftar');
                continue;
            }

            $adaSukses = true;
            $pesanGagal = null;
            foreach ($devices as $d) {
                if ($kering) { CLI::write("  [dry] user {$n['user_id']} ← {$n['title']}", 'dark_gray'); continue; }

                $hasil = PushSender::kirim($d['push_token'], $n['title'], $n['body'], [
                    'url'    => (string) $n['url'],
                    'module' => (string) $n['module'],
                ]);
                if ($hasil['ok']) continue;

                $adaSukses  = false;
                $pesanGagal = $hasil['error'];
                // Token perangkat mati → bersihkan supaya tak dicoba terus.
                if ($hasil['invalid']) {
                    $db->table('api_tokens')->where('token', $d['token'])->update(['push_token' => null]);
                }
            }

            if ($kering) continue;
            if ($adaSukses) { $terkirim++; $this->tandai($db, $n['id'], 'sent'); }
            else            { $gagal++;    $this->gagalkan($db, $n['id'], (int) $n['attempts'] + 1, $pesanGagal); }
        }

        CLI::write("Selesai — terkirim: {$terkirim}, gagal: {$gagal}, dilewati: {$lewat}"
            . ($kering ? ' (dry-run, tak ada yang diubah)' : ''), 'green');
    }

    private function tandai($db, int $id, string $status, ?string $error = null): void
    {
        $db->table('push_queue')->where('id', $id)->update([
            'status' => $status, 'last_error' => $error, 'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Gagal → tetap `pending` sampai batas percobaan, baru jadi `failed`. */
    private function gagalkan($db, int $id, int $percobaan, ?string $error): void
    {
        $db->table('push_queue')->where('id', $id)->update([
            'status'     => $percobaan >= self::MAKS_PERCOBAAN ? 'failed' : 'pending',
            'attempts'   => $percobaan,
            'last_error' => $error !== null ? mb_substr($error, 0, 255) : null,
        ]);
    }
}
