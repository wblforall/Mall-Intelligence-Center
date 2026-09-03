<?php

namespace App\Commands;

use App\Libraries\ActivityLog;
use App\Libraries\AppSync;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Kirim antrian perintah ke aplikasi lain (mis. nonaktifkan akun di PAM e-Sign).
 *
 * Cron yang disarankan — tiap 5 menit. Ditulis dalam bentuk daftar menit
 * (bukan bentuk singkat dengan garis miring) supaya bisa disalin langsung:
 *
 *   0,5,10,15,20,25,30,35,40,45,50,55 * * * * cd ~/public_html/mic && php spark mic:sync-dispatch
 *
 * Tidak perlu tiap menit: propagasi resign tidak menuntut hitungan detik, dan
 * jeda 5 menit mengurangi panggilan sia-sia saat antrian kosong.
 *
 * Aman dijalankan sebelum token layanan disiapkan: antrian ditandai `skipped`
 * dan tidak menumpuk selamanya sebagai `pending`.
 */
class SyncDispatch extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:sync-dispatch';
    protected $description = 'Kirim antrian perintah ke aplikasi lain (nonaktifkan akun, dst)';
    protected $usage       = 'mic:sync-dispatch [--batas N] [--dry-run]';

    private const MAKS_PERCOBAAN = 3;

    public function run(array $params)
    {
        $batas  = (int) (CLI::getOption('batas') ?: 100);
        $kering = (bool) CLI::getOption('dry-run');
        $db     = db_connect();

        $antrian = $db->table('app_sync_queue q')
            ->select('q.*, a.kode AS app_kode, a.nama AS app_nama, e.nama AS karyawan')
            ->join('apps a', 'a.id = q.app_id')
            ->join('employees e', 'e.id = q.employee_id', 'left')
            ->where('q.status', 'pending')
            ->where('q.attempts <', self::MAKS_PERCOBAAN)
            ->orderBy('q.created_at')
            ->limit($batas)
            ->get()->getResultArray();

        if (! $antrian) {
            CLI::write('Antrian kosong.', 'yellow');

            return;
        }

        CLI::write(count($antrian) . ' perintah dalam antrian.', 'white');

        $rekap = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($antrian as $baris) {
            $label = ($baris['karyawan'] ?? '#' . $baris['employee_id'])
                . ' → ' . $baris['app_nama'] . ' (' . $baris['aksi'] . ')';

            if ($kering) {
                CLI::write('  [dry-run] ' . $label, 'cyan');
                continue;
            }

            [$status, $galat] = AppSync::kirim($baris);
            $rekap[$status] = ($rekap[$status] ?? 0) + 1;

            $ubah = [
                'status'     => $status,
                'attempts'   => (int) $baris['attempts'] + 1,
                'last_error' => $galat,
            ];
            if ($status === 'sent') $ubah['sent_at'] = date('Y-m-d H:i:s');

            // `failed` dikembalikan ke `pending` selama percobaan belum habis,
            // supaya cron berikutnya mencobanya lagi. Setelah MAKS_PERCOBAAN
            // ia berhenti dicoba — dan `last_error` tetap tersimpan supaya
            // penyebabnya bisa dilihat, bukan hilang.
            if ($status === 'failed' && $ubah['attempts'] < self::MAKS_PERCOBAAN) {
                $ubah['status'] = 'pending';
            }

            $db->table('app_sync_queue')->where('id', $baris['id'])->update($ubah);

            $warna = match ($status) {
                'sent'    => 'green',
                'skipped' => 'yellow',
                default   => 'red',
            };
            CLI::write('  ' . strtoupper($status) . ' — ' . $label . ($galat ? ' :: ' . $galat : ''), $warna);

            // Hanya keberhasilan dan kegagalan permanen yang dicatat ke
            // ActivityLog. `skipped` dan percobaan yang masih akan diulang
            // tidak — kalau tidak, log audit tergenangi baris berulang setiap
            // 5 menit untuk satu kejadian yang sama.
            if ($status === 'sent') {
                ActivityLog::write('update', 'app_sync', (string) $baris['id'], $label, [
                    'aksi'     => $baris['aksi'],
                    'aplikasi' => $baris['app_nama'],
                    'id_lokal' => $baris['id_lokal'],
                ]);
            } elseif ($status === 'failed' && $ubah['attempts'] >= self::MAKS_PERCOBAAN) {
                ActivityLog::write('update', 'app_sync', (string) $baris['id'], $label . ' — GAGAL', [
                    'aksi'       => $baris['aksi'],
                    'aplikasi'   => $baris['app_nama'],
                    'last_error' => $galat,
                    'percobaan'  => $ubah['attempts'],
                ]);
            }
        }

        if (! $kering) {
            CLI::write('');
            CLI::write('Selesai: ' . $rekap['sent'] . ' terkirim, '
                . $rekap['failed'] . ' gagal, ' . $rekap['skipped'] . ' dilewati.', 'white');
        }
    }
}
