<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Perawatan writable/logs agar tidak membengkak diam-diam.
 *
 *  1. Log harian CI4 `log-YYYY-MM-DD.log` yang berumur > 60 hari → dihapus.
 *     Umur dibaca dari TANGGAL DI NAMA berkas (bukan mtime, yang bisa berubah
 *     saat berkas disalin/dipulihkan); fallback ke mtime bila nama tak cocok.
 *  2. Log cron `*-cron.log` (ditulis lewat `>>` oleh crontab) yang > 5 MB →
 *     dipangkas menjadi 5.000 baris terakhir. Dipangkas DI TEMPAT (truncate +
 *     tulis ulang), bukan rename, supaya proses cron yang sedang menambah
 *     (O_APPEND) tetap menulis ke berkas yang sama.
 *
 * Contoh:
 *   php spark mic:rapikan-log --dry-run
 *   php spark mic:rapikan-log
 *
 * Cron yang disarankan — tiap hari 03:15 (WAJIB PHP CLI, bukan `php` polos
 * yang di cPanel menunjuk ke php-cgi):
 *   15 3 * * * cd ~/public_html/mic && /usr/local/bin/php spark mic:rapikan-log >/dev/null 2>&1
 */
class RapikanLog extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:rapikan-log';
    protected $description = 'Hapus log harian > 60 hari & pangkas *-cron.log > 5 MB jadi 5.000 baris terakhir.';
    protected $usage       = 'mic:rapikan-log [--dry-run] [--hari 60] [--baris 5000] [--maks-mb 5]';
    protected $options     = [
        '--dry-run' => 'Hanya tampilkan yang akan dihapus/dipangkas, tidak mengubah berkas',
        '--hari'    => 'Umur maksimal log harian dalam hari (default 60)',
        '--baris'   => 'Jumlah baris terakhir yang disisakan pada *-cron.log (default 5000)',
        '--maks-mb' => 'Ambang ukuran *-cron.log sebelum dipangkas, MB (default 5)',
    ];

    public const HARI_DEFAULT    = 60;
    public const BARIS_DEFAULT   = 5000;
    public const MAKS_MB_DEFAULT = 5;

    public function run(array $params)
    {
        $kering = (bool) CLI::getOption('dry-run');
        $hari   = max(1, (int) (CLI::getOption('hari') ?: self::HARI_DEFAULT));
        $baris  = max(1, (int) (CLI::getOption('baris') ?: self::BARIS_DEFAULT));
        $maksMb = max(1, (int) (CLI::getOption('maks-mb') ?: self::MAKS_MB_DEFAULT));
        $dir    = WRITEPATH . 'logs';

        if (! is_dir($dir)) {
            CLI::error("Folder log tidak ada: {$dir}");
            return;
        }

        $hasil = self::rapikan($dir, $hari, $baris, $maksMb * 1024 * 1024, $kering);

        foreach ($hasil['dihapus'] as $f) {
            CLI::write(($kering ? '  [dry] hapus   ' : '  hapus   ') . $f, 'dark_gray');
        }
        foreach ($hasil['dipangkas'] as $f => $info) {
            CLI::write(($kering ? '  [dry] pangkas ' : '  pangkas ') . $f
                . ' (' . self::mb($info['sebelum']) . ' → ' . ($kering ? '≈' : '') . self::mb($info['sesudah']) . ')', 'dark_gray');
        }
        foreach ($hasil['galat'] as $g) {
            CLI::write('  galat: ' . $g, 'red');
        }

        CLI::write(sprintf(
            'Selesai — dihapus: %d berkas (%s), dipangkas: %d berkas, hemat ±%s%s',
            count($hasil['dihapus']),
            self::mb($hasil['byteDihapus']),
            count($hasil['dipangkas']),
            self::mb($hasil['byteHemat']),
            $kering ? ' (dry-run, tak ada yang diubah)' : ''
        ), 'green');
    }

    /**
     * Inti perawatan — statis & tanpa CLI agar bisa diuji dengan folder sementara.
     *
     * @return array{dihapus: list<string>, dipangkas: array<string, array{sebelum:int, sesudah:int}>,
     *               galat: list<string>, byteDihapus: int, byteHemat: int}
     */
    public static function rapikan(string $dir, int $hari, int $baris, int $maksByte, bool $kering, ?int $sekarang = null): array
    {
        $sekarang ??= time();
        $batas    = $sekarang - $hari * 86400;
        $hasil    = ['dihapus' => [], 'dipangkas' => [], 'galat' => [], 'byteDihapus' => 0, 'byteHemat' => 0];
        $dir      = rtrim($dir, '/\\');

        // 1) Log harian lama.
        foreach (glob($dir . '/log-*.log') ?: [] as $path) {
            $nama = basename($path);
            $waktu = preg_match('/^log-(\d{4}-\d{2}-\d{2})\.log$/', $nama, $m)
                ? strtotime($m[1] . ' 00:00:00')
                : @filemtime($path);
            if ($waktu === false || $waktu >= $batas) continue;

            $ukuran = (int) @filesize($path);
            if (! $kering && ! @unlink($path)) {
                $hasil['galat'][] = "gagal menghapus {$nama}";
                continue;
            }
            $hasil['dihapus'][]    = $nama;
            $hasil['byteDihapus'] += $ukuran;
        }

        // 2) Log cron yang terlalu besar.
        foreach (glob($dir . '/*-cron.log') ?: [] as $path) {
            $nama   = basename($path);
            $ukuran = (int) @filesize($path);
            if ($ukuran <= $maksByte) continue;

            if ($kering) {
                $ekor = self::ekor($path, $baris);
                $sesudah = $ekor === null ? $ukuran : strlen($ekor);
            } else {
                $sesudah = self::pangkas($path, $baris);
                if ($sesudah === null) {
                    $hasil['galat'][] = "gagal memangkas {$nama}";
                    continue;
                }
            }
            $hasil['dipangkas'][$nama] = ['sebelum' => $ukuran, 'sesudah' => $sesudah];
            $hasil['byteHemat']       += max(0, $ukuran - $sesudah);
        }
        sort($hasil['dihapus']);

        return $hasil;
    }

    /**
     * Ambil $baris baris terakhir tanpa memuat seluruh berkas ke memori —
     * dibaca mundur per blok 64 KB sampai cukup baris baru ditemukan.
     */
    public static function ekor(string $path, int $baris): ?string
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) return null;

        fseek($fh, 0, SEEK_END);
        $pos   = ftell($fh);
        $buf   = '';
        $blok  = 65536;
        // Baris baru di akhir berkas tidak dihitung sebagai baris tambahan.
        $perlu = $baris + 1;

        while ($pos > 0 && substr_count($buf, "\n") < $perlu) {
            $baca = (int) min($blok, $pos);
            $pos -= $baca;
            fseek($fh, $pos);
            $buf = fread($fh, $baca) . $buf;
        }
        fclose($fh);

        $akhirNl = str_ends_with($buf, "\n");
        $potong  = explode("\n", $akhirNl ? substr($buf, 0, -1) : $buf);
        $potong  = array_slice($potong, -$baris);

        return implode("\n", $potong) . ($akhirNl ? "\n" : '');
    }

    /**
     * Pangkas berkas di tempat menjadi $baris baris terakhir. Mengembalikan
     * ukuran baru (byte) atau null bila gagal.
     */
    public static function pangkas(string $path, int $baris): ?int
    {
        $ekor = self::ekor($path, $baris);
        if ($ekor === null) return null;

        $fh = @fopen($path, 'r+b');
        if (! $fh) return null;
        flock($fh, LOCK_EX);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, $ekor);
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        clearstatcache(true, $path);

        return strlen($ekor);
    }

    private static function mb(int $byte): string
    {
        return number_format($byte / 1048576, 1, ',', '.') . ' MB';
    }
}
