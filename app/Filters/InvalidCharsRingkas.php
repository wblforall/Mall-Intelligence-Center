<?php

namespace App\Filters;

use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

/**
 * Pengganti filter `invalidchars` bawaan CI4 dengan pesan penolakan RINGKAS.
 *
 * Pemeriksaannya sama persis (UTF-8 tidak valid & karakter kontrol selain
 * baris baru/tab pada GET, POST, cookie, dan body mentah). Bedanya hanya pada
 * isi pesan pengecualian: bawaan CI4 menempelkan SELURUH nilai yang ditolak
 * ke pesan — dan pesan itu ikut tercatat di writable/logs. Pada 2 Okt 2026 satu
 * agen Pemantauan AI mengirim body non-UTF-8 berulang kali; tiap penolakan
 * mencatat ±600 KB transkrip sehingga log membengkak jadi 245 MB.
 *
 * Di sini pesan hanya memuat: sumber, nama field (dipotong), ukuran nilai, dan
 * potongan awal ≤ 200 byte yang sudah dibersihkan (mb_scrub + karakter kontrol
 * ditampilkan sebagai \xNN) — cukup untuk menelusuri, tanpa membocorkan isi utuh.
 */
class InvalidCharsRingkas extends InvalidChars
{
    /** Batas potongan nilai yang boleh masuk pesan/log (byte). */
    public const MAKS_POTONGAN = 200;

    /** Batas panjang nama field di pesan (byte) — kunci parse_str bisa raksasa. */
    public const MAKS_NAMA = 60;

    /**
     * @param list<string>|null $arguments
     *
     * @return void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $data = [
            'get'      => $request->getGet(),
            'post'     => $request->getPost(),
            'cookie'   => $request->getCookie(),
            'rawInput' => $request->getRawInput(),
        ];

        foreach ($data as $source => $values) {
            $this->source = $source;
            $galat        = $this->periksa($values, '');

            // Dilempar DI SINI, bukan di dalam periksa(): log CI4 mencetak
            // argumen tiap frame trace lewat var_export() TANPA batas panjang.
            // Bila dilempar dari frame yang memegang nilai mentah, isi utuh
            // tetap bocor ke log lewat trace (persis yang terjadi 2 Okt).
            // Frame before() hanya memegang objek Request → tercetak Object(...).
            if ($galat !== null) {
                throw new SecurityException($galat, 400);
            }
        }
    }

    /**
     * Telusuri nilai (rekursif) sambil membawa nama field-nya agar pesan
     * penolakan bisa menyebut field mana yang bermasalah. Mengembalikan pesan
     * ringkas bila ada yang tidak valid, null bila semuanya lolos.
     *
     * @param array|string|null $value
     */
    protected function periksa($value, string $nama): ?string
    {
        if (is_array($value)) {
            foreach ($value as $kunci => $isi) {
                $galat = $this->periksa($isi, $nama === '' ? (string) $kunci : $nama . '.' . $kunci);
                if ($galat !== null) {
                    return $galat;
                }
            }

            return null;
        }

        $value = (string) $value;

        if (! mb_check_encoding($value, 'UTF-8')) {
            return $this->pesan('Karakter UTF-8 tidak valid', $nama, $value);
        }

        if (preg_match($this->controlCodeRegex, $value) !== 1) {
            return $this->pesan('Karakter kontrol tidak valid', $nama, $value);
        }

        return null;
    }

    /**
     * Susun pesan ringkas. Contoh:
     *   Karakter UTF-8 tidak valid di rawInput[lines] (612345 byte): {"a":"b\xE9...
     */
    public function pesan(string $jenis, string $nama, string $value): string
    {
        return $jenis . ' di ' . $this->source
            . '[' . self::ringkas($nama, self::MAKS_NAMA) . ']'
            . ' (' . strlen($value) . ' byte): '
            . self::ringkas($value, self::MAKS_POTONGAN);
    }

    /**
     * Potong ke ≤ $maks byte lalu buat aman untuk log: byte non-UTF-8 dan
     * karakter kontrol (termasuk baris baru) ditulis sebagai \xNN, sehingga
     * satu penolakan = satu baris log pendek.
     */
    public static function ringkas(string $teks, int $maks): string
    {
        $dipotong = strlen($teks) > $maks;
        $teks     = substr($teks, 0, $maks);

        $hasil = preg_replace_callback(
            // Urutan UTF-8 valid dibiarkan; byte lain (tak valid) & kontrol di-escape.
            '/[\x00-\x1F\x7F]|[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF4][\x80-\xBF]{3}|[\x80-\xFF]/',
            // Multi-byte (UTF-8 valid) → biarkan; satu byte (kontrol/tak valid) → \xNN.
            static fn (array $m): string => strlen($m[0]) > 1 ? $m[0] : sprintf('\x%02X', ord($m[0])),
            $teks
        );

        return ($hasil ?? '') . ($dipotong ? '…' : '');
    }
}
