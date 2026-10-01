<?php

namespace App\Libraries;

use App\Models\AppSettingsModel;

/**
 * Password copot (uninstall) Pemantauan AI — satu password untuk SEMUA
 * perangkat, dikelola terpusat dari dashboard MIC.
 *
 * Mencopot agen dari laptop memerlukan password ini (bersama hak admin lokal),
 * supaya pemakai tak bisa diam-diam melepas pemantauan. Yang disimpan di server
 * HANYA hash-nya; agen laptop menerima hash itu lewat respons enroll/ingest dan
 * memvalidasi password yang diketik secara offline (SHA-256 cocok).
 *
 * Disimpan di app_settings (AppSettingsModel) — mekanisme setting yang sudah
 * ada, pola sama dengan AiEnrollKey, sehingga tak ada tabel baru untuk satu
 * nilai dan perubahannya ikut terekam updated_at.
 *
 * ENCODING (wajib cocok dengan PowerShell di laptop): hash dihitung dengan
 * hash('sha256', $password) atas string mentah apa adanya (byte UTF-8, TANPA
 * salt dan TANPA trim), menghasilkan hex huruf kecil 64 karakter. Sisi
 * PowerShell harus memakai byte UTF-8 password yang sama.
 */
class AiCopotPassword
{
    public const KEY   = 'ai_copot_hash';
    public const LABEL = 'Hash Password Copot Pemantauan AI';

    /** Hash saat ini (hex 64 char), atau null bila belum pernah diset. */
    public static function hashNow(): ?string
    {
        $val = (new AppSettingsModel())->get(self::KEY);
        return $val ? (string) $val : null;
    }

    /**
     * Simpan hash dari password mentah. Hanya HASH yang masuk DB — password
     * mentah tak pernah disimpan maupun di-log.
     */
    public static function set(string $passwordMentah): void
    {
        $hash = hash('sha256', $passwordMentah);
        $m    = new AppSettingsModel();
        $m->setSetting(self::KEY, $hash);
        // setSetting() tak menulis label pada baris baru — isi sekali.
        $m->where('key', self::KEY)->set('label', self::LABEL)->update();
    }
}
