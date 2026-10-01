<?php

namespace App\Libraries;

use App\Models\AppSettingsModel;

/**
 * Kunci enrollment Pemantauan AI — satu rahasia bersama.
 *
 * Laptop memasang agen dengan kunci ini; selama cocok, perangkat masuk daftar
 * secara otomatis (lihat Api\AiMonitorController::enroll). Disimpan di
 * app_settings — mekanisme setting aplikasi yang sudah ada (AppSettingsModel),
 * supaya tak ada tabel baru untuk satu nilai dan rotasinya ikut terekam
 * updated_at seperti setting lain.
 *
 * Kunci ini bukan rahasia tinggi: ini kunci masuk DAFTAR, bukan token kirim.
 * Token kirim tiap laptop tetap diterbitkan unik saat enroll dan hanya
 * disimpan hash-nya. Meregenerasi kunci ini hanya menutup pendaftaran BARU;
 * perangkat yang sudah enroll tetap berjalan dengan tokennya sendiri.
 */
class AiEnrollKey
{
    public const KEY   = 'ai_enroll_key';
    public const LABEL = 'Kunci Enrollment Pemantauan AI';

    /**
     * Nilai kunci saat ini. Bila belum pernah ada, dibuat sekali di sini
     * supaya halaman perangkat selalu punya kunci untuk ditampilkan.
     */
    public static function current(): string
    {
        $m   = new AppSettingsModel();
        $val = $m->get(self::KEY);
        if (! $val) {
            $val = self::generate();
            $m->setSetting(self::KEY, $val);
            // setSetting() tidak menulis label pada baris baru — isi sekali.
            $m->where('key', self::KEY)->set('label', self::LABEL)->update();
        }
        return (string) $val;
    }

    /** Buat kunci baru dan simpan. Kembalikan nilai barunya. */
    public static function regenerate(): string
    {
        $val = self::generate();
        $m   = new AppSettingsModel();
        $m->setSetting(self::KEY, $val);
        $m->where('key', self::KEY)->set('label', self::LABEL)->update();
        return $val;
    }

    /** 24 byte acak kuat → 48 hex. */
    private static function generate(): string
    {
        return bin2hex(random_bytes(24));
    }
}
