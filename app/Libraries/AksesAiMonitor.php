<?php

namespace App\Libraries;

/**
 * Siapa yang boleh membuka Pemantauan AI (dashboard, rekap, transkrip sesi,
 * komputer, laporan, perangkat & token).
 *
 * Keputusan pemilik produk 23 Nov 2026: HANYA akun di daftar ini — bukan semua
 * admin, bukan lewat hak menu per orang/departemen. Transkrip berisi percakapan
 * orang, jadi pintunya sengaja sesempit mungkin. Rubrik skor (/ai-monitor/rubrik)
 * tetap terbuka bagi semua yang login (transparansi kriteria penilaian).
 *
 * Mengubah daftar = ubah kode + deploy, supaya setiap perubahan tercatat di git.
 */
class AksesAiMonitor
{
    /** Email (huruf kecil) yang boleh membuka Pemantauan AI. */
    public const PEMANTAU = ['affan.ridha@pam-group.com'];

    /** Murni: apakah email ini termasuk pemantau. */
    public static function emailBoleh(?string $email): bool
    {
        $e = strtolower(trim((string) $email));
        return $e !== '' && in_array($e, self::PEMANTAU, true);
    }

    /** Pengguna yang sedang login boleh membuka Pemantauan AI? */
    public static function boleh(): bool
    {
        if (! session()->get('logged_in')) {
            return false;
        }
        return self::emailBoleh(session()->get('user_email'));
    }
}
