<?php

namespace App\Libraries;

/**
 * Kompresi foto upload (GD): resize ke sisi terpanjang maks 1600px + re-encode
 * (JPEG q78 / PNG level 9, alpha dipertahankan). Format & nama file TIDAK
 * berubah sehingga aman untuk file yang sudah tercatat di DB.
 *
 * Dipakai saat upload foto bukti (loyalty realisasi, work report, dst) dan
 * oleh command backfill `mic:compress-images` untuk foto lama.
 */
class ImageCompressor
{
    public const MAX_DIM  = 1600;
    public const QUALITY  = 78;   // JPEG/WebP
    // File di bawah ambang ini & dimensinya sudah kecil → dilewati (tak berarti)
    public const SKIP_BYTES = 150 * 1024;

    /**
     * Normalisasi foto BARU setelah move(): PNG/WebP fotografik dikonversi ke
     * JPEG (alpha di-flatten putih) bila hasilnya lebih kecil, sisanya cukup
     * dikompres in-place. Return NAMA FILE FINAL — panggil SEBELUM nama
     * disimpan ke DB. Gagal apa pun → nama asli tetap dipakai.
     */
    public static function normalizeUpload(string $dir, string $name): string
    {
        $dir  = rtrim($dir, '/');
        $path = $dir . '/' . $name;
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, ['png', 'webp'], true) && extension_loaded('gd') && is_file($path)) {
            $newName = substr($name, 0, -strlen($ext)) . 'jpg';
            $newPath = $dir . '/' . $newName;
            if (self::convertToJpeg($path, $newPath)) {
                @unlink($path);
                return $newName;
            }
        }
        self::compress($path);
        return $name;
    }

    /**
     * Konversi PNG/WebP → JPEG (flatten alpha ke putih + resize maks 1600px).
     * Hanya sukses bila hasil lebih kecil dari file asal. Return true bila
     * $dst tertulis (file $src TIDAK dihapus — tanggung jawab pemanggil).
     */
    public static function convertToJpeg(string $src, string $dst, int $maxDim = self::MAX_DIM, int $quality = self::QUALITY): bool
    {
        if (! extension_loaded('gd') || ! is_file($src)) return false;
        $info = @getimagesize($src);
        if (! $info) return false;
        [$w, $h] = $info;
        $mime = $info['mime'] ?? '';

        try {
            $img = match ($mime) {
                'image/png'  => @imagecreatefrompng($src),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null,
                'image/jpeg' => @imagecreatefromjpeg($src),
                default      => null,
            };
            if (! $img) return false;

            $scale = max($w, $h) > $maxDim ? $maxDim / max($w, $h) : 1;
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            // Flatten ke kanvas putih (JPEG tak mendukung transparansi)
            $canvas = imagecreatetruecolor($nw, $nh);
            $white  = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);

            $ok = imagejpeg($canvas, $dst, $quality);
            imagedestroy($canvas);
            if (! $ok || ! is_file($dst)) { @unlink($dst); return false; }
            if (filesize($dst) >= filesize($src)) { @unlink($dst); return false; }
            return true;
        } catch (\Throwable $e) {
            log_message('warning', 'ImageCompressor convert gagal: ' . $src . ' — ' . $e->getMessage());
            @unlink($dst);
            return false;
        }
    }

    /**
     * Kompres file gambar di-tempat. Return byte yang dihemat (0 = dilewati/gagal).
     * Tidak melempar exception — kegagalan kompresi tidak boleh menggagalkan upload.
     */
    public static function compress(string $path, int $maxDim = self::MAX_DIM, int $quality = self::QUALITY): int
    {
        if (! extension_loaded('gd') || ! is_file($path)) return 0;

        $sizeBefore = (int) @filesize($path);
        $info = @getimagesize($path);
        if (! $info) return 0;
        [$w, $h] = $info;
        $mime = $info['mime'] ?? '';

        $needResize = max($w, $h) > $maxDim;
        if (! $needResize && $sizeBefore <= self::SKIP_BYTES) return 0;

        try {
            switch ($mime) {
                case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
                case 'image/png':  $src = @imagecreatefrompng($path);  break;
                case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null; break;
                default: return 0;
            }
            if (! $src) return 0;

            if ($needResize) {
                $scale = $maxDim / max($w, $h);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                if ($mime === 'image/png' || $mime === 'image/webp') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            // Tulis ke file temp dulu; pakai hasil hanya bila benar-benar lebih kecil.
            $tmp = $path . '.tmp';
            $ok  = match ($mime) {
                'image/jpeg' => imagejpeg($src, $tmp, $quality),
                'image/png'  => imagepng($src, $tmp, 9),
                'image/webp' => function_exists('imagewebp') ? imagewebp($src, $tmp, $quality) : false,
                default      => false,
            };
            imagedestroy($src);
            if (! $ok || ! is_file($tmp)) { @unlink($tmp); return 0; }

            $sizeAfter = (int) filesize($tmp);
            if ($sizeAfter > 0 && $sizeAfter < $sizeBefore) {
                if (@rename($tmp, $path)) return $sizeBefore - $sizeAfter;
            }
            @unlink($tmp);
            return 0;
        } catch (\Throwable $e) {
            log_message('warning', 'ImageCompressor gagal: ' . $path . ' — ' . $e->getMessage());
            return 0;
        }
    }
}
