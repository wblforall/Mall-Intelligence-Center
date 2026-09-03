<?php

namespace App\Libraries;

/**
 * Pengirim perintah ke aplikasi lain (PAM e-Sign, dst).
 *
 * Dipakai cron `mic:sync-dispatch`, BUKAN dipanggil langsung dari controller —
 * alasannya sama seperti PushSender/push_queue: panggilan luar di tengah
 * request menahan penyimpanan HR sampai jaringan menjawab, gagal total kalau
 * tujuan sedang mati (padahal penandaan resign-nya sendiri benar dan harus
 * tetap tersimpan), dan tidak memberi kesempatan mencoba ulang.
 *
 * Pembagian konfigurasi:
 *   `apps.url` (basis data)  -> alamat dasar aplikasi tujuan. Di basis data
 *                               karena berbeda per lingkungan, dan basis data
 *                               memang sudah berbeda per lingkungan.
 *   `.env`                   -> token rahasia, per kode aplikasi:
 *                               sync.token.esign = <token>
 *                               JANGAN masuk git.
 *
 * Bila salah satu belum ada, {@see terkonfigurasi()} mengembalikan false dan
 * dispatcher menandai antrian `skipped` — bukan `failed`. Jadi seluruh alur
 * sudah bisa jalan sekarang, tinggal menaruh token saat siap.
 */
class AppSync
{
    /** Aksi yang dikenali. Ditulis eksplisit supaya salah ketik ketahuan saat antre, bukan saat kirim. */
    public const AKSI_NONAKTIFKAN = 'nonaktifkan';

    /**
     * Antrekan satu perintah.
     *
     * `id_lokal` DISALIN ke antrian, tidak dibaca ulang saat kirim — supaya
     * perintah tetap menunjuk akun yang dimaksud saat kejadiannya, bukan akun
     * lain yang kebetulan tertaut belakangan.
     */
    public static function antre(
        int $appId,
        int $employeeId,
        string $aksi,
        ?string $idLokal,
        ?string $alasan = null
    ): void {
        db_connect()->table('app_sync_queue')->insert([
            'app_id'      => $appId,
            'employee_id' => $employeeId,
            'id_lokal'    => $idLokal,
            'aksi'        => $aksi,
            'alasan'      => $alasan !== null ? mb_substr($alasan, 0, 150) : null,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Antrekan satu aksi untuk SEMUA aplikasi yang aksesnya aktif bagi karyawan ini.
     *
     * Hanya baris dengan `id_lokal` terisi yang diantrekan: tanpa itu aplikasi
     * tujuan tidak bisa tahu akun mana yang dimaksud. Yang belum tertaut
     * dilewati dan dikembalikan jumlahnya, supaya pemanggil bisa memberi tahu
     * user bahwa ada yang perlu ditautkan lebih dulu.
     *
     * @return array{diantre:int, tanpa_tautan:int}
     */
    public static function antreSemuaAplikasi(int $employeeId, string $aksi, ?string $alasan = null): array
    {
        $baris = db_connect()->table('employee_app_access')
            ->select('app_id, id_lokal')
            ->where('employee_id', $employeeId)
            ->where('aktif', 1)
            ->get()->getResultArray();

        $diantre = 0;
        $tanpaTautan = 0;

        foreach ($baris as $b) {
            if (empty($b['id_lokal'])) {
                $tanpaTautan++;
                continue;
            }
            self::antre((int) $b['app_id'], $employeeId, $aksi, $b['id_lokal'], $alasan);
            $diantre++;
        }

        return ['diantre' => $diantre, 'tanpa_tautan' => $tanpaTautan];
    }

    /** Alamat dasar aplikasi tujuan, dari `apps.url`. */
    public static function alamat(string $kodeApp): string
    {
        $app = db_connect()->table('apps')->select('url')->where('kode', $kodeApp)->get()->getRowArray();

        return rtrim((string) ($app['url'] ?? ''), '/');
    }

    /** Token rahasia aplikasi tujuan, dari .env: sync.token.<kode>. */
    public static function token(string $kodeApp): string
    {
        return (string) env('sync.token.' . $kodeApp, '');
    }

    /** Siap dipakai hanya kalau alamat DAN token dua-duanya ada. */
    public static function terkonfigurasi(string $kodeApp): bool
    {
        return self::alamat($kodeApp) !== '' && self::token($kodeApp) !== '';
    }

    /**
     * Kirim satu baris antrian.
     *
     * @param  array<string,mixed>  $baris  satu baris app_sync_queue (sudah di-join dengan apps.kode)
     * @return array{0:string, 1:?string}   [status baru, pesan galat bila ada]
     */
    public static function kirim(array $baris): array
    {
        $kode = (string) ($baris['app_kode'] ?? '');

        if (! self::terkonfigurasi($kode)) {
            return ['skipped', 'belum dikonfigurasi (apps.url dan/atau sync.token.' . $kode . ')'];
        }

        $url = self::jalur($kode, (string) $baris['aksi'], (string) $baris['id_lokal']);
        if ($url === null) {
            return ['failed', 'aksi tidak dikenali: ' . $baris['aksi']];
        }

        [$httpKode, $isi] = self::http($url, json_encode([
            'alasan' => $baris['alasan'] ?? 'dinonaktifkan oleh MIC',
        ]), [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Service-Token: ' . self::token($kode),
        ]);

        if ($httpKode >= 200 && $httpKode < 300) {
            return ['sent', null];
        }

        // 503 dari tujuan = di sana pun belum dikonfigurasi. Ditandai skipped,
        // bukan failed: bukan kesalahan data, dan mencoba ulang terus-menerus
        // tidak akan menolong sampai tokennya diset di sisi sana.
        if ($httpKode === 503) {
            return ['skipped', 'tujuan belum dikonfigurasi (503)'];
        }

        return ['failed', 'HTTP ' . $httpKode . ': ' . mb_substr((string) $isi, 0, 180)];
    }

    /**
     * Tarik daftar akun aplikasi tujuan, untuk dicocokkan importer.
     *
     * Sengaja lewat HTTP, bukan koneksi basis data kedua: aplikasi tujuan
     * berada di server lain, jadi membaca basis datanya langsung hanya jalan
     * di mesin pengembangan. Importer yang cuma jalan di laptop bukan
     * importer.
     *
     * @return array{ok:bool, data:array<int,array<string,mixed>>, error:?string}
     */
    public static function ambilPengguna(string $kodeApp): array
    {
        if (! self::terkonfigurasi($kodeApp)) {
            return ['ok' => false, 'data' => [], 'error' =>
                'belum dikonfigurasi (apps.url dan/atau sync.token.' . $kodeApp . ')'];
        }

        [$httpKode, $isi] = self::httpGet(self::alamat($kodeApp) . '/api/sistem/pengguna', [
            'Accept: application/json',
            'X-Service-Token: ' . self::token($kodeApp),
        ]);

        if ($httpKode < 200 || $httpKode >= 300) {
            return ['ok' => false, 'data' => [], 'error' =>
                'HTTP ' . $httpKode . ': ' . mb_substr($isi, 0, 180)];
        }

        $data = json_decode($isi, true);
        if (! is_array($data) || ! isset($data['data']) || ! is_array($data['data'])) {
            return ['ok' => false, 'data' => [], 'error' => 'jawaban tidak dikenali'];
        }

        return ['ok' => true, 'data' => $data['data'], 'error' => null];
    }

    /**
     * @param  string[]  $headers
     * @return array{0:int, 1:string}
     */
    private static function httpGet(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            // Jangan ikuti pengalihan: X-Service-Token bisa terbawa ke host lain.
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $isi  = curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($isi === false) $isi = 'curl: ' . curl_error($ch);
        curl_close($ch);

        return [$kode, (string) $isi];
    }

    /** Peta aksi -> jalur endpoint di aplikasi tujuan. */
    private static function jalur(string $kodeApp, string $aksi, string $idLokal): ?string
    {
        $dasar = self::alamat($kodeApp);

        return match ($aksi) {
            self::AKSI_NONAKTIFKAN => $dasar . '/api/sistem/pengguna/' . rawurlencode($idLokal) . '/nonaktif',
            default => null,
        };
    }

    /**
     * @param  string[]  $headers
     * @return array{0:int, 1:string}  [kode HTTP, body]
     */
    private static function http(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $isi  = curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($isi === false) $isi = 'curl: ' . curl_error($ch);
        curl_close($ch);

        return [$kode, (string) $isi];
    }
}
