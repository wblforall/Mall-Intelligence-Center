<?php

namespace App\Libraries;

/**
 * Pengirim push notification lewat Firebase Cloud Messaging (HTTP v1).
 *
 * Dipakai oleh cron `mic:push-dispatch`, BUKAN dipanggil langsung dari
 * controller — lihat migrasi `push_queue` untuk alasannya.
 *
 * Konfigurasi lewat .env (JANGAN masuk git):
 *   fcm.projectId       = nama-proyek-firebase
 *   fcm.credentialsPath = /path/ke/service-account.json
 *
 * Bila belum dikonfigurasi, {@see terkonfigurasi()} mengembalikan false dan
 * dispatcher menandai antrian sebagai `skipped` — jadi seluruh alur sudah
 * jalan sejak sekarang, tinggal menaruh kredensial saat Firebase siap.
 */
class PushSender
{
    private const OAUTH_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE     = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CACHE_KEY = 'fcm_access_token';

    public static function terkonfigurasi(): bool
    {
        $path = (string) env('fcm.credentialsPath');
        return $path !== '' && is_file($path) && (string) env('fcm.projectId') !== '';
    }

    /**
     * Kirim satu pesan ke satu perangkat.
     *
     * @return array{ok:bool, error:?string, invalid:bool} `invalid` = token
     *         perangkat sudah tak berlaku, pemanggil sebaiknya menghapusnya.
     */
    public static function kirim(string $pushToken, string $title, ?string $body, array $data = []): array
    {
        if (! self::terkonfigurasi()) {
            return ['ok' => false, 'error' => 'FCM belum dikonfigurasi', 'invalid' => false];
        }

        $access = self::accessToken();
        if ($access === null) {
            return ['ok' => false, 'error' => 'Gagal mendapatkan access token FCM', 'invalid' => false];
        }

        $payload = ['message' => [
            'token'        => $pushToken,
            'notification' => ['title' => mb_substr($title, 0, 150), 'body' => $body !== null ? mb_substr($body, 0, 240) : ''],
            // Semua nilai data WAJIB string di FCM v1.
            'data'         => array_map(static fn ($v) => (string) $v, $data),
            'android'      => ['priority' => 'high'],
            'apns'         => ['headers' => ['apns-priority' => '10']],
        ]];

        $url = 'https://fcm.googleapis.com/v1/projects/' . env('fcm.projectId') . '/messages:send';
        [$kode, $isi] = self::http($url, json_encode($payload), [
            'Authorization: Bearer ' . $access,
            'Content-Type: application/json',
        ]);

        if ($kode === 200) return ['ok' => true, 'error' => null, 'invalid' => false];

        // 404 UNREGISTERED / 400 INVALID_ARGUMENT = token perangkat sudah mati.
        $invalid = in_array($kode, [400, 404], true) && str_contains((string) $isi, 'UNREGISTERED')
                || $kode === 404;

        return ['ok' => false, 'error' => 'HTTP ' . $kode . ' ' . mb_substr((string) $isi, 0, 180), 'invalid' => $invalid];
    }

    /** Access token OAuth2, di-cache 55 menit (berlaku 60 menit di Google). */
    private static function accessToken(): ?string
    {
        $cache = \Config\Services::cache();
        $token = $cache->get(self::CACHE_KEY);
        if (is_string($token) && $token !== '') return $token;

        $kredensial = json_decode((string) file_get_contents((string) env('fcm.credentialsPath')), true);
        if (! is_array($kredensial) || empty($kredensial['client_email']) || empty($kredensial['private_key'])) {
            log_message('error', 'PushSender: service-account.json tidak valid.');
            return null;
        }

        $now = time();
        $jwt = self::jwtRs256([
            'iss'   => $kredensial['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::OAUTH_URL,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ], $kredensial['private_key']);
        if ($jwt === null) return null;

        [$kode, $isi] = self::http(self::OAUTH_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]), ['Content-Type: application/x-www-form-urlencoded']);

        $hasil = json_decode((string) $isi, true);
        if ($kode !== 200 || empty($hasil['access_token'])) {
            log_message('error', 'PushSender: gagal tukar JWT → ' . mb_substr((string) $isi, 0, 200));
            return null;
        }

        $cache->save(self::CACHE_KEY, $hasil['access_token'], 3300);
        return $hasil['access_token'];
    }

    private static function jwtRs256(array $claim, string $privateKey): ?string
    {
        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $isi = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode($claim));

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            log_message('error', 'PushSender: private key FCM tidak bisa dibaca.');
            return null;
        }
        $tanda = '';
        if (! openssl_sign($isi, $tanda, $key, OPENSSL_ALGO_SHA256)) return null;

        return $isi . '.' . $b64($tanda);
    }

    /** @return array{0:int, 1:string|false} [kode HTTP, body] */
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

        return [$kode, $isi];
    }
}
