<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ImageCompressor;

/**
 * Kompresi massal foto lama di public/uploads (resize maks 1600px + re-encode,
 * nama & format file tetap — aman untuk referensi DB).
 *
 * Contoh:
 *   php spark mic:compress-images --dir loyalty-realisasi --dry-run
 *   php spark mic:compress-images --dir loyalty-realisasi --convert-png
 *   php spark mic:compress-images --dir content-realisasi
 *
 * --convert-png: PNG fotografik dikonversi ke JPEG (jauh lebih kecil) DAN
 * referensi nama file di DB ikut diupdate — hanya untuk folder yang
 * terpetakan di DB_MAP. Tanpa opsi ini file hanya dikompres in-place.
 *
 * Catatan opsi: gunakan SPASI (--dir loyalty-realisasi), bukan tanda sama dengan.
 */
class CompressImages extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:compress-images';
    protected $description = 'Kompres foto di public/uploads/<dir> (rekursif, in-place).';
    protected $usage       = 'mic:compress-images --dir <folder> [--max 1600] [--quality 78] [--convert-png] [--dry-run]';
    protected $options     = [
        '--dir'         => 'Folder di bawah public/uploads (wajib), mis. loyalty-realisasi',
        '--max'         => 'Sisi terpanjang maksimum px (default 1600)',
        '--quality'     => 'Kualitas JPEG/WebP (default 78)',
        '--convert-png' => 'Konversi PNG→JPEG + update referensi DB (folder terpetakan saja)',
        '--dry-run'     => 'Hanya hitung kandidat, tidak mengubah file',
    ];

    /** Folder → daftar [tabel, kolom] yang menyimpan NAMA file (untuk --convert-png). */
    private const DB_MAP = [
        'loyalty-realisasi' => [
            ['loyalty_hadiah_realisasi', 'foto'],
            ['loyalty_voucher_realisasi', 'foto'],
            ['event_loyalty_hadiah_realisasi', 'foto'],
            ['event_loyalty_voucher_realisasi', 'foto'],
        ],
        'work_report' => [
            ['work_initiative_update_images', 'file_name'],
        ],
    ];

    public function run(array $params)
    {
        $dir = (string) (CLI::getOption('dir') ?? '');
        $dir = trim(str_replace(['..', '\\'], '', $dir), '/');
        if ($dir === '') { CLI::error('Wajib: --dir <folder di bawah public/uploads>'); return; }

        $base = rtrim(FCPATH, '/') . '/uploads/' . $dir;
        if (! is_dir($base)) { CLI::error('Folder tidak ditemukan: ' . $base); return; }

        $max     = (int) (CLI::getOption('max') ?? ImageCompressor::MAX_DIM) ?: ImageCompressor::MAX_DIM;
        $quality = (int) (CLI::getOption('quality') ?? ImageCompressor::QUALITY) ?: ImageCompressor::QUALITY;
        $dryRun  = CLI::getOption('dry-run') !== null;
        $convert = CLI::getOption('convert-png') !== null;

        if ($convert && ! isset(self::DB_MAP[$dir])) {
            CLI::error("--convert-png hanya untuk folder terpetakan (" . implode(', ', array_keys(self::DB_MAP)) . ") — nama file di DB harus ikut diupdate.");
            return;
        }
        $db = $convert ? \Config\Database::connect() : null;

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        $exts = ['jpg', 'jpeg', 'png', 'webp'];
        $total = $done = $converted = 0; $savedBytes = 0; $beforeBytes = 0;

        foreach ($it as $f) {
            $ext = strtolower($f->getExtension());
            if (! $f->isFile() || ! in_array($ext, $exts, true)) continue;
            $total++;
            $sizeBefore   = $f->getSize();
            $beforeBytes += $sizeBefore;
            if ($dryRun) continue;

            $path = $f->getPathname();

            // PNG/WebP → JPEG (+ update referensi DB) bila diminta & hasil lebih kecil
            if ($convert && in_array($ext, ['png', 'webp'], true)) {
                $oldName = $f->getFilename();
                $newName = substr($oldName, 0, -strlen($ext)) . 'jpg';
                $newPath = $f->getPath() . '/' . $newName;
                if (ImageCompressor::convertToJpeg($path, $newPath, $max, $quality)) {
                    $db->transStart();
                    foreach (self::DB_MAP[$dir] as [$table, $col]) {
                        $db->table($table)->where($col, $oldName)->update([$col => $newName]);
                    }
                    $db->transComplete();
                    if ($db->transStatus() !== false) {
                        @unlink($path);
                        $converted++;
                        $done++;
                        $savedBytes += $sizeBefore - (int) filesize($newPath);
                        continue;
                    }
                    @unlink($newPath); // DB gagal → batalkan, file asli tetap
                }
            }

            $saved = ImageCompressor::compress($path, $max, $quality);
            if ($saved > 0) { $done++; $savedBytes += $saved; }
        }

        $mb = fn($b) => number_format($b / 1048576, 1) . ' MB';
        if ($dryRun) {
            CLI::write("Dry-run: {$total} file gambar, total " . $mb($beforeBytes) . " — jalankan tanpa --dry-run untuk kompres.", 'yellow');
            return;
        }
        CLI::write("Selesai: {$done}/{$total} file dikompres" . ($convert ? " ({$converted} PNG→JPEG + update DB)" : '')
            . ' · ' . $mb($beforeBytes) . ' → ' . $mb($beforeBytes - $savedBytes)
            . ' (hemat ' . $mb($savedBytes) . ')', 'green');
    }
}
