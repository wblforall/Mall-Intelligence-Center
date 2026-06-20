<?php

namespace App\Services;

/**
 * Penghubung read-only ke sistem SPI Web Reporting (parkir Balikpapan Superblock).
 *
 * Data tetap milik & sumber-kebenaran di server SPI; MIC hanya MENARIK SALINAN
 * untuk ditampilkan ulang. Tidak ada operasi tulis ke sistem mereka.
 *
 * Sumber endpoint (terverifikasi):
 *  - Live occupancy/income : {host}/parking3/load2.php?siteid={SITE}      (JSON, tanpa auth)
 *  - Qty harian / kendaraan: {base}/ajax-daily-summary-qty               (JSON, login+CSRF)
 *  - Income harian         : {base}/ajax-daily-summary-income            (JSON, login+CSRF)
 *  - Income bulanan (tren) : {base}/income-summary-sites-data            (JSON, login+CSRF)
 *  - Distribusi durasi     : {apiHost}/reporting2_api/statistik.php      (HTML, tanpa auth)
 *
 * Konfigurasi via .env (lihat key SPI_*). Sesi login (cookie+CSRF) di-cache di
 * writable/spi/. Respons di-cache singkat via cache() agar tidak membebani server SPI.
 */
class SpiReportingService
{
    private string $base;       // .../reporting2/public/index.php
    private string $liveHost;   // http://host:port  (untuk parking3/*)
    private string $apiHost;    // http://host:port  (untuk reporting2_api/*)
    private string $site;       // kode site, mis. BSB
    private string $user;
    private string $pass;
    private string $cookieFile;

    public function __construct()
    {
        $this->base     = rtrim(env('SPI_BASE_URL', 'http://103.119.142.252:8001/reporting2/public/index.php'), '/');
        $this->liveHost = rtrim(env('SPI_LIVE_HOST', 'http://103.119.142.252:8001'), '/');
        $this->apiHost  = rtrim(env('SPI_API_HOST',  'http://103.119.142.252:83'), '/');
        $this->site     = env('SPI_SITE', 'BSB');
        $this->user     = env('SPI_USER', 'BSB');
        $this->pass     = env('SPI_PASS', 'bsb@2023');

        // Cookie jar harus writable oleh siapa pun yang menjalankan (Apache 'daemon'
        // maupun CLI/cron user berbeda). Pakai nama file per-user agar tidak ada
        // konflik kepemilikan lintas-proses; fallback ke temp dir bila WRITEPATH/spi
        // tidak bisa dibuat/ditulisi (quirk XAMPP: PHP mkdir kadang gagal).
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : get_current_user();
        $dir = WRITEPATH . 'spi';
        if (! is_dir($dir)) { @mkdir($dir, 0777, true); @chmod($dir, 0777); }
        if (! is_dir($dir) || ! is_writable($dir)) {
            $dir = rtrim(sys_get_temp_dir(), '/\\') . '/mic_spi';
            if (! is_dir($dir)) { @mkdir($dir, 0777, true); @chmod($dir, 0777); }
        }
        $this->cookieFile = $dir . '/cookies_' . md5($this->base . $this->user) . '_' . $uid . '.txt';
    }

    // ── PUBLIC API ───────────────────────────────────────────────

    /**
     * Live occupancy + income hari ini berjalan (real-time, cache 30 dtk).
     * @return array{ok:bool, mobil:int, motor:int, total:int, lot_mobil:int,
     *   lot_motor:int, lot_mobil_tersedia:int, lot_motor_tersedia:int,
     *   tunai:int, nontunai:int, totalincome:int}
     */
    public function fetchLive(): array
    {
        $cacheKey = 'spi_live_' . $this->site;
        if ($hit = cache($cacheKey)) { return $hit; }

        $raw = $this->httpGet($this->liveHost . '/parking3/load2.php?siteid=' . rawurlencode($this->site), false);
        $j   = $raw ? json_decode($raw, true) : null;
        if (! is_array($j)) {
            return ['ok' => false, 'mobil' => 0, 'motor' => 0, 'total' => 0,
                'lot_mobil' => 0, 'lot_motor' => 0, 'lot_mobil_tersedia' => 0,
                'lot_motor_tersedia' => 0, 'tunai' => 0, 'nontunai' => 0, 'totalincome' => 0];
        }
        $num = fn($v) => (int) str_replace([',', '.'], '', (string) ($v ?? '0'));
        $out = [
            'ok'                 => true,
            'mobil'              => $num($j['mobil'] ?? 0),
            'motor'              => $num($j['motor'] ?? 0),
            'total'              => $num($j['total'] ?? 0),
            'lot_mobil'          => $num($j['lot_mobil'] ?? 0),
            'lot_motor'          => $num($j['lot_motor'] ?? 0),
            'lot_mobil_tersedia' => $num($j['lot_mobil_tersedia'] ?? 0),
            'lot_motor_tersedia' => $num($j['lot_motor_tersedia'] ?? 0),
            'tunai'              => $num($j['tunai'] ?? 0),
            'nontunai'           => $num($j['nontunai'] ?? 0),
            'totalincome'        => $num($j['totalincome'] ?? 0),
        ];
        if ($out['ok']) { cache()->save($cacheKey, $out, 30); } // jangan cache kegagalan
        return $out;
    }

    /**
     * Jumlah kendaraan per hari per jenis untuk rentang tanggal.
     * @return array<int,array{tanggal:string, mobil:int, motor:int, box:int,
     *   truck:int, taxi:int, bus:int, total:int}>
     */
    public function fetchDailyQty(string $startDate, string $endDate): array
    {
        $rows = $this->postJson('/ajax-daily-summary-qty',
            ['kdsite' => $this->site, 'dates' => $this->dateRange($startDate, $endDate)], 1800);
        $out = [];
        foreach ((array) $rows as $r) {
            $out[] = [
                'tanggal' => $r['tglticket'] ?? '',
                'mobil'   => (int) ($r['mobil'] ?? 0),
                'motor'   => (int) ($r['motor'] ?? 0),
                'box'     => (int) ($r['box'] ?? 0),
                'truck'   => (int) ($r['truck'] ?? 0),
                'taxi'    => (int) ($r['taxi'] ?? 0),
                'bus'     => (int) ($r['bus'] ?? 0),
                'total'   => (int) ($r['totalqty'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Income (rupiah) per hari per jenis untuk rentang tanggal. Basis tanggal tiket.
     * @return array<int,array{tanggal:string, mobil:int, motor:int, box:int,
     *   truck:int, taxi:int, bus:int, total:int}>
     */
    public function fetchDailyIncome(string $startDate, string $endDate): array
    {
        $rows = $this->postJson('/ajax-daily-summary-income',
            ['kdsite' => $this->site, 'dates' => $this->dateRange($startDate, $endDate)], 1800);
        $out = [];
        foreach ((array) $rows as $r) {
            $out[] = [
                'tanggal' => $r['tglticket'] ?? '',
                'mobil'   => (int) ($r['mobil'] ?? 0),
                'motor'   => (int) ($r['motor'] ?? 0),
                'box'     => (int) ($r['box'] ?? 0),
                'truck'   => (int) ($r['truck'] ?? 0),
                'taxi'    => (int) ($r['taxi'] ?? 0),
                'bus'     => (int) ($r['bus'] ?? 0),
                'total'   => (int) ($r['totalincome'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Tren income bulanan. $income: 0=Casual, 1=Member, 2=Casual+Member.
     * Acuan resmi laporan (basis tanggal bayar). Cache 1 jam.
     * @return array<int,array{label:string, value:int}>
     */
    public function fetchMonthlyIncome(string $startDate, string $endDate, int $income = 0): array
    {
        $cacheKey = 'spi_mon_' . md5($this->site . $startDate . $endDate . $income);
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $body = http_build_query([
            'startDate' => $this->fmtDM($startDate),
            'endDate'   => $this->fmtDM($endDate),
            'income'    => $income,
        ]) . '&sites%5B%5D=' . rawurlencode($this->site); // sites[] = array
        $raw = $this->postRaw('/income-summary-sites-data', $body, 'application/x-www-form-urlencoded');
        $j   = $raw ? json_decode($raw, true) : null;
        $out = [];
        if (is_array($j) && ! empty($j['labels'])) {
            $data = $j['datasets'][0]['data'] ?? [];
            foreach ($j['labels'] as $i => $lab) {
                $out[] = ['label' => $lab, 'value' => (int) ($data[$i] ?? 0)];
            }
        }
        if (! empty($out)) { cache()->save($cacheKey, $out, 3600); } // jangan cache kosong
        return $out;
    }

    /**
     * Distribusi durasi parkir (counts per bucket) untuk rentang tanggal — agregat.
     * Buckets: le1, h1_2, h2_3, h3_4, h4_5, h5_6, h6_7, gt7.
     * @return array<string,int>
     */
    public function fetchDurationDist(string $startDate, string $endDate): array
    {
        $cacheKey = 'spi_dur_' . md5($this->site . $startDate . $endDate);
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $url = $this->apiHost . '/reporting2_api/statistik.php?site=' . rawurlencode($this->site)
            . '&tgl1=' . $startDate . '&tgl2=' . date('Y-m-d', strtotime($endDate . ' +1 day')); // end eksklusif → +1 hari
        $html = $this->httpGet($url, false);
        $keys = ['le1', 'h1_2', 'h2_3', 'h3_4', 'h4_5', 'h5_6', 'h6_7', 'gt7'];
        $out  = array_fill_keys($keys, 0);
        if ($html) {
            // Tiap baris kendaraan: 8 bucket durasi lalu TOTAL. Jumlahkan kolom bucket.
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows)) {
                foreach ($rows[1] as $row) {
                    preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells);
                    $vals = array_map(fn($c) => (int) str_replace(',', '', trim(strip_tags($c))), $cells[1] ?? []);
                    // baris valid: kendaraan (label di kolom 0) + 8 bucket + total → minimal 9 angka di kanan
                    if (count($vals) >= 9) {
                        $slice = array_slice($vals, -9, 8); // 8 bucket sebelum TOTAL
                        foreach ($keys as $i => $k) { $out[$k] += $slice[$i] ?? 0; }
                    }
                }
            }
        }
        if (array_sum($out) > 0) { cache()->save($cacheKey, $out, 3600); } // jangan cache kosong
        return $out;
    }

    /**
     * Rincian metode pembayaran HARI INI (Tunai + e-money: eMoney/TapCash/Doomo/
     * Brizzi/Flazz). Diparse dari dashboard home SPI (server-rendered, hanya hari ini).
     * Jumlah e-money = nontunai. Cache 60 dtk.
     * @return array<int,array{method:string, amount:int}>
     */
    public function fetchPaymentBreakdown(): array
    {
        $cacheKey = 'spi_pay_' . $this->site;
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $out = [];
        // Tunai dari feed live (no-auth)
        $live = $this->fetchLive();
        if ($live['ok']) { $out[] = ['method' => 'Tunai', 'amount' => $live['tunai']]; }

        // e-money per metode dari home (login)
        if ($this->ensureLogin()) {
            $home = $this->httpGet($this->base . '/home', true);
            if ($home && preg_match_all(
                '/var xValues = \[([^\]]*)\];\s*var yValues = \[([^\]]*)\];.*?new Chart\("([^"]+)"/s',
                $home, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $blk) {
                    if (($blk[3] ?? '') !== 'myChartTypeBar2') { continue; }
                    preg_match_all('/"([^"]*)"/', $blk[1], $xs);
                    // yValues bisa ber-leading-zero (mis. '0132000') → bersihkan lalu cast int
                    $ys = array_values(array_map(
                        fn($v) => (int) preg_replace('/^0+(?=\d)/', '', trim($v)),
                        array_filter(explode(',', $blk[2]), fn($v) => trim($v) !== '')
                    ));
                    foreach (($xs[1] ?? []) as $i => $label) {
                        $out[] = ['method' => $this->normMethod($label), 'amount' => $ys[$i] ?? 0];
                    }
                    break;
                }
            }
        }
        if (count($out) > 1) { cache()->save($cacheKey, $out, 60); }
        return $out;
    }

    private function normMethod(string $m): string
    {
        $map = ['emoney' => 'e-Money', 'tapcash' => 'TapCash', 'doomo' => 'Doomo',
            'brizzi' => 'Brizzi', 'flazz' => 'Flazz'];
        return $map[strtolower(trim($m))] ?? trim($m);
    }

    /**
     * Parse statistik.php sekali → kembalikan distribusi durasi + jumlah kendaraan
     * dipisah BAYAR (Casual) vs LANGGANAN/free (Pass) per jenis. Cache 1 jam.
     * Catatan: kategori 'Other' di statistik.php diketahui bermasalah (duplikat Bus),
     * jadi diabaikan untuk paid/free; hanya 6 jenis utama dihitung.
     * @return array{duration:array<string,int>, paid:array<string,int>, free:array<string,int>}
     */
    public function fetchStatistik(string $startDate, string $endDate): array
    {
        $cacheKey = 'spi_stat_' . md5($this->site . $startDate . $endDate);
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $durKeys = ['le1', 'h1_2', 'h2_3', 'h3_4', 'h4_5', 'h5_6', 'h6_7', 'gt7'];
        $veh     = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $out = [
            'duration' => array_fill_keys($durKeys, 0),
            'paid'     => array_fill_keys($veh, 0),
            'free'     => array_fill_keys($veh, 0),
        ];

        $url  = $this->apiHost . '/reporting2_api/statistik.php?site=' . rawurlencode($this->site)
            . '&tgl1=' . $startDate . '&tgl2=' . date('Y-m-d', strtotime($endDate . ' +1 day')); // end eksklusif → +1 hari
        $html = $this->httpGet($url, false);
        if ($html && preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows)) {
            foreach ($rows[1] as $row) {
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/s', $row, $cells);
                $cellsTxt = array_map(fn($c) => trim(strip_tags($c)), $cells[1] ?? []);
                $cellsTxt = array_values(array_filter($cellsTxt, fn($c) => $c !== ''));
                if (! $cellsTxt) { continue; }

                // Label jenis ada di kolom pertama (kadang diawali tanggal "16-Jun-2026").
                $label = $cellsTxt[0];
                if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}/', $label) && count($cellsTxt) > 1) {
                    $label = $cellsTxt[1];
                }
                if (! preg_match('/^(Mobil|Motor|Box|Truck|Taxi|Bus)\s+(Casual|Pass)/i', $label, $lm)) {
                    continue;
                }
                $v    = strtolower($lm[1]);
                $type = strtolower($lm[2]);
                $nums = array_map(fn($c) => (int) str_replace(',', '', $c),
                    array_filter($cellsTxt, fn($c) => preg_match('/^[\d,]+$/', $c)));
                $nums = array_values($nums);
                if (count($nums) < 9) { continue; }
                $total   = $nums[count($nums) - 1];
                $buckets = array_slice($nums, -9, 8);
                foreach ($durKeys as $i => $k) { $out['duration'][$k] += $buckets[$i] ?? 0; }
                if ($type === 'casual') { $out['paid'][$v] += $total; }
                else                    { $out['free'][$v] += $total; }
            }
        }
        if (array_sum($out['duration']) > 0 || array_sum($out['paid']) > 0) {
            cache()->save($cacheKey, $out, 3600);
        }
        return $out;
    }

    /**
     * Jumlah kendaraan LANGGANAN (Pass) per HARI per jenis, dari statistik.php.
     * Dipakai sync untuk mengisi kolom *_free di daily_vehicles. Cache 1 jam.
     * @return array<string,array<string,int>>  tanggal(Y-m-d) => [mobil=>,motor=>,...]
     */
    public function fetchDailyPass(string $startDate, string $endDate): array
    {
        $cacheKey = 'spi_dpass_' . md5($this->site . $startDate . $endDate);
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $veh  = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $out  = [];
        $url  = $this->apiHost . '/reporting2_api/statistik.php?site=' . rawurlencode($this->site)
            . '&tgl1=' . $startDate . '&tgl2=' . date('Y-m-d', strtotime($endDate . ' +1 day')); // end eksklusif → +1 hari
        $html = $this->httpGet($url, false);
        $cur  = null;
        if ($html && preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows)) {
            foreach ($rows[1] as $row) {
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/s', $row, $cells);
                $c = array_values(array_filter(array_map(fn($x) => trim(strip_tags($x)), $cells[1] ?? []), fn($x) => $x !== ''));
                if (! $c) { continue; }
                $label = $c[0];
                if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{4})/', $label, $dm)) {
                    $cur   = $this->idnDate($dm[3], $dm[2], $dm[1]);
                    $label = $c[1] ?? '';
                }
                if (! $cur || ! preg_match('/^(Mobil|Motor|Box|Truck|Taxi|Bus)\s+Pass/i', $label, $lm)) { continue; }
                $v    = strtolower($lm[1]);
                $nums = array_values(array_filter($c, fn($x) => preg_match('/^[\d,]+$/', $x)));
                if (! $nums) { continue; }
                $total = (int) str_replace(',', '', $nums[count($nums) - 1]);
                $out[$cur] = $out[$cur] ?? array_fill_keys($veh, 0);
                $out[$cur][$v] = $total;
            }
        }
        if (! empty($out)) { cache()->save($cacheKey, $out, 3600); }
        return $out;
    }

    private function idnDate(string $y, string $mon, string $d): string
    {
        $m = ['Jan'=>'01','Feb'=>'02','Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06',
            'Jul'=>'07','Aug'=>'08','Sep'=>'09','Oct'=>'10','Nov'=>'11','Dec'=>'12'];
        return $y . '-' . ($m[$mon] ?? '01') . '-' . $d;
    }

    /** Cek kredensial & konektivitas (untuk diagnostik). */
    public function ping(): bool
    {
        return $this->ensureLogin();
    }

    // ── INTERNAL: auth ───────────────────────────────────────────

    private string $csrf = '';

    private function ensureLogin(): bool
    {
        if ($this->csrf !== '') { return true; }

        // 0) REUSE sesi dari cookie bila masih valid — hindari login berulang yang
        //    saling mematikan sesi (SPI hanya izinkan 1 sesi aktif per user).
        if (is_file($this->cookieFile)) {
            $home = $this->httpGet($this->base . '/home', true);
            if ($home && preg_match('/<meta name="csrf-token" content="([^"]+)"/', $home, $mr)
                && strpos($home, 'name="kduser"') === false) {
                $this->csrf = $mr[1];
                return true;
            }
        }

        // 1) ambil halaman login utk _token + cookie awal
        $loginHtml = $this->httpGet($this->base . '/login', true);
        if (! $loginHtml) { return false; }
        if (! preg_match('/name="_token"[^>]*value="([^"]+)"/', $loginHtml, $m)) { return false; }
        $token = $m[1];

        // 2) POST login
        $body = http_build_query([
            '_token'   => $token,
            'kduser'   => $this->user,
            'password' => $this->pass,
            'remember' => 'on',
        ]);
        $this->httpPost($this->base . '/login', $body, 'application/x-www-form-urlencoded', []);

        // 3) ambil csrf-token dari halaman home (dipakai sebagai X-CSRF-TOKEN untuk endpoint ajax)
        $home = $this->httpGet($this->base . '/home', true);
        if ($home && preg_match('/<meta name="csrf-token" content="([^"]+)"/', $home, $mm)) {
            $this->csrf = $mm[1];
            return true;
        }
        return false;
    }

    /** POST JSON ke endpoint ajax (login+CSRF). Return array hasil decode atau []. */
    private function postJson(string $path, array $payload, int $ttl = 0)
    {
        $cacheKey = 'spi_pj_' . md5($path . json_encode($payload));
        if ($ttl > 0 && ($hit = cache($cacheKey)) !== null) { return $hit; }

        if (! $this->ensureLogin()) { return []; }
        $raw = $this->httpPost($this->base . $path, json_encode($payload), 'application/json', [
            'X-CSRF-TOKEN: ' . $this->csrf,
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ]);
        // 419/redirect (sesi basi) → relogin sekali
        if (! $raw || $raw[0] === '<') {
            $this->csrf = '';
            @unlink($this->cookieFile);
            if (! $this->ensureLogin()) { return []; }
            $raw = $this->httpPost($this->base . $path, json_encode($payload), 'application/json', [
                'X-CSRF-TOKEN: ' . $this->csrf,
                'X-Requested-With: XMLHttpRequest',
                'Accept: application/json',
            ]);
        }
        $j = $raw ? json_decode($raw, true) : null;
        $out = is_array($j) ? $j : [];
        if ($ttl > 0 && ! empty($out)) { cache()->save($cacheKey, $out, $ttl); } // jangan cache kosong
        return $out;
    }

    /** POST form-urlencoded ber-CSRF (untuk income-summary-sites-data). Return raw string. */
    private function postRaw(string $path, string $body, string $ctype): ?string
    {
        if (! $this->ensureLogin()) { return null; }
        $raw = $this->httpPost($this->base . $path, $body, $ctype, [
            'X-CSRF-TOKEN: ' . $this->csrf,
            'X-Requested-With: XMLHttpRequest',
        ]);
        if (! $raw || $raw[0] === '<') {
            $this->csrf = '';
            @unlink($this->cookieFile);
            if (! $this->ensureLogin()) { return null; }
            $raw = $this->httpPost($this->base . $path, $body, $ctype, [
                'X-CSRF-TOKEN: ' . $this->csrf,
                'X-Requested-With: XMLHttpRequest',
            ]);
        }
        return $raw ?: null;
    }

    // ── INTERNAL: low-level HTTP ────────────────────────────────

    private function httpGet(string $url, bool $withCookies): ?string
    {
        $ch = curl_init($url);
        $opt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'MallIC/1.0 (+parking-sync)',
        ];
        if ($withCookies) {
            $opt[CURLOPT_COOKIEJAR]  = $this->cookieFile;
            $opt[CURLOPT_COOKIEFILE] = $this->cookieFile;
        }
        curl_setopt_array($ch, $opt);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($raw !== false && $code >= 200 && $code < 400) ? $raw : null;
    }

    private function httpPost(string $url, string $body, string $ctype, array $headers): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_USERAGENT      => 'MallIC/1.0 (+parking-sync)',
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: ' . $ctype], $headers),
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($raw !== false && $code >= 200 && $code < 400) ? $raw : null;
    }

    // ── INTERNAL: util tanggal ──────────────────────────────────

    /** ['Y-m-d', ...] inklusif dari start..end (maks 370 hari sebagai pengaman). */
    private function dateRange(string $start, string $end): array
    {
        $out = [];
        try {
            $d = new \DateTime($start);
            $e = new \DateTime($end);
        } catch (\Throwable $t) {
            return [$start];
        }
        $i = 0;
        while ($d <= $e && $i < 370) {
            $out[] = $d->format('Y-m-d');
            $d->modify('+1 day');
            $i++;
        }
        return $out ?: [$start];
    }

    /** 'Y-m-d' → 'j M Y' (format datepicker income-summary, mis. "1 Jan 2026"). */
    private function fmtDM(string $ymd): string
    {
        try {
            return (new \DateTime($ymd))->format('j M Y');
        } catch (\Throwable $t) {
            return $ymd;
        }
    }
}
