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
            return ['ok' => false, 'mobil' => 0, 'motor' => 0, 'other' => 0, 'total' => 0,
                'lot_mobil' => 0, 'lot_motor' => 0, 'lot_mobil_tersedia' => 0,
                'lot_motor_tersedia' => 0, 'tunai' => 0, 'nontunai' => 0, 'totalincome' => 0];
        }
        $num = fn($v) => (int) str_replace([',', '.'], '', (string) ($v ?? '0'));
        $out = [
            'ok'                 => true,
            'mobil'              => $num($j['mobil'] ?? 0),
            'motor'              => $num($j['motor'] ?? 0),
            'other'              => $num($j['other'] ?? 0),
            'total'              => $num($j['total'] ?? 0),
            'lot_mobil'          => $num($j['lot_mobil'] ?? 0),
            'lot_motor'          => $num($j['lot_motor'] ?? 0),
            'lot_mobil_tersedia' => $num($j['lot_mobil_tersedia'] ?? 0),
            'lot_motor_tersedia' => $num($j['lot_motor_tersedia'] ?? 0),
            'tunai'              => $num($j['tunai'] ?? 0),
            'nontunai'           => $num($j['nontunai'] ?? 0),
            'totalincome'        => $num($j['totalincome'] ?? 0),
        ];
        if ($out['ok']) { cache()->save($cacheKey, $out, 15); } // jangan cache kegagalan; 15s utk Live cepat
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
            . '&tgl1=' . rawurlencode($startDate) . '&tgl2=' . rawurlencode(date('Y-m-d', strtotime($endDate . ' +1 day'))); // end eksklusif → +1 hari
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

        // Kumpulkan per metode ter-normalisasi (hindari duplikat, mis. "TUNAI" vs "Tunai")
        $byMethod = []; // method => amount (urut sesuai kemunculan)
        $add = function (string $method, int $amount) use (&$byMethod) {
            $m = $this->normMethod($method);
            if (! array_key_exists($m, $byMethod)) { $byMethod[$m] = 0; }
            $byMethod[$m] += $amount;
        };

        // e-money + (kadang) tunai per metode dari home (login)
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
                        $add($label, $ys[$i] ?? 0);
                    }
                    break;
                }
            }
        }
        // Tunai dari feed live HANYA jika home tidak menyertakannya
        $live = $this->fetchLive();
        if ($live['ok'] && ! array_key_exists('Tunai', $byMethod)) {
            $byMethod = ['Tunai' => $live['tunai']] + $byMethod;
        }

        $out = [];
        foreach ($byMethod as $method => $amount) { $out[] = ['method' => $method, 'amount' => $amount]; }
        if (count($out) > 1) { cache()->save($cacheKey, $out, 60); }
        return $out;
    }

    private function normMethod(string $m): string
    {
        $map = ['emoney' => 'e-Money', 'tapcash' => 'TapCash', 'doomo' => 'Doomo',
            'brizzi' => 'Brizzi', 'flazz' => 'Flazz', 'tunai' => 'Tunai'];
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
            . '&tgl1=' . rawurlencode($startDate) . '&tgl2=' . rawurlencode(date('Y-m-d', strtotime($endDate . ' +1 day'))); // end eksklusif → +1 hari
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
            . '&tgl1=' . rawurlencode($startDate) . '&tgl2=' . rawurlencode(date('Y-m-d', strtotime($endDate . ' +1 day'))); // end eksklusif → +1 hari
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

    /** Peta field payment table-casual-income → nama tampil. */
    private const PAY_MAP = [
        'flazz' => 'Flazz', 'emoney' => 'e-Money', 'tapcash' => 'BNI TapCash', 'brizzi' => 'BRI Brizzi',
        'dana' => 'DANA', 'gopay' => 'GoPay', 'lt' => 'Lost Ticket', 'helm' => 'Helm', 'inap' => 'Inap',
        'mega' => 'Mega/Allo QR', 'vocher' => 'Voucher', 'valet1' => 'Valet 1', 'valet2' => 'Valet 2',
        'bnimobile' => 'BNI Mobile', 'vip' => 'VIP', 'ovo' => 'OVO', 'lnk' => 'LinkAja', 'domo' => 'Doomo',
        'sbt' => 'Sobatku', 'shp' => 'ShopeePay', 'valet3' => 'Valet 3', 'lbs' => 'Lebih Setor', 'on' => 'ON',
        'qrisnisp' => 'QRIS NISP', 'other' => 'Other', 'allo' => 'Allobank', 'brimo' => 'BRIMO',
        'mp' => 'QRIS Merah Putih', 'jd' => 'JakCard', 'vallet' => 'Vallet', 'vallet2' => 'Vallet 2',
        'voucher' => 'Voucher 2', 'penalty' => 'Penalty', 'others' => 'Others', 'others2' => 'Others 2',
    ];

    /**
     * Tarik tabel detail casual income (table-casual-income) per tanggal — HISTORIS.
     * Mengandung income, per-kendaraan (casual/pass/qty), dan SEMUA metode pembayaran.
     * Kunci: dt format 'd M Y'. Cache 1 jam.
     * @return array<int,array{tanggal:string, income:int, qty:array, paid:array, free:array, payments:array}>
     */
    public function fetchCasualTable(string $startDate, string $endDate): array
    {
        $cacheKey = 'spi_ctab_' . md5($this->site . $startDate . $endDate);
        if (($hit = cache($cacheKey)) !== null) { return $hit; }

        $veh  = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        // DataTables params (kolom 0..100 cukup)
        $cols = '';
        for ($i = 0; $i < 101; $i++) {
            $cols .= "&columns[$i][data]=$i&columns[$i][name]=&columns[$i][orderable]=false&columns[$i][searchable]=false";
        }
        $body = 'draw=1&start=0&length=400&order[0][column]=0&order[0][dir]=asc' . $cols
            . '&kdsite=' . rawurlencode($this->site)
            . '&dt1=' . rawurlencode($this->fmtDM($startDate))
            . '&dt2=' . rawurlencode($this->fmtDM($endDate));

        $raw = $this->postRaw('/table-casual-income', $body, 'application/x-www-form-urlencoded');
        $j   = $raw ? json_decode($raw, true) : null;
        $out = [];
        foreach (($j['data'] ?? []) as $r) {
            $payments = [];
            foreach (self::PAY_MAP as $key => $label) {
                $amt = (int) ($r['amount_' . $key] ?? 0);
                if ($amt !== 0) {
                    $payments[$label] = ($payments[$label] ?? 0) + $amt;
                }
            }
            $qty = $paid = $free = [];
            foreach ($veh as $v) {
                $qty[$v]  = (int) ($r['total_qty_' . $v] ?? 0);
                $paid[$v] = (int) ($r['to' . $v . 'casualout'] ?? 0);
                $free[$v] = (int) ($r['to' . $v . 'passout'] ?? 0);
            }
            $out[] = [
                'tanggal'  => substr((string) ($r['tglticket'] ?? ''), 0, 10),
                'income'   => (int) ($r['casualincome'] ?? 0),
                'qty'      => $qty,
                'paid'     => $paid,
                'free'     => $free,
                'payments' => $payments,
            ];
        }
        if (! empty($out)) { cache()->save($cacheKey, $out, 3600); }
        return $out;
    }

    /** Agregat metode pembayaran (rupiah) untuk rentang — HISTORIS. method=>amount, urut desc. */
    public function fetchPaymentHistory(string $startDate, string $endDate): array
    {
        $agg = [];
        foreach ($this->fetchCasualTable($startDate, $endDate) as $row) {
            foreach ($row['payments'] as $m => $amt) { $agg[$m] = ($agg[$m] ?? 0) + $amt; }
        }
        arsort($agg);
        return $agg;
    }

    /**
     * table-casual-income per potongan ≤7 hari (endpoint error utk range besar) lalu gabung.
     * Dipakai SYNC (lambat ~8dtk/potongan) — JANGAN dipakai di page-load.
     * @return array<int,array> sama bentuk dgn fetchCasualTable
     */
    public function fetchCasualTableChunked(string $startDate, string $endDate, int $chunkDays = 7): array
    {
        $out = [];
        try { $cur = new \DateTime($startDate); $end = new \DateTime($endDate); }
        catch (\Throwable $t) { return $this->fetchCasualTable($startDate, $endDate); }
        $guard = 0;
        while ($cur <= $end && $guard < 400) {
            $cs = clone $cur;
            $ce = clone $cur; $ce->modify('+' . ($chunkDays - 1) . ' days');
            if ($ce > $end) { $ce = clone $end; }
            foreach ($this->fetchCasualTable($cs->format('Y-m-d'), $ce->format('Y-m-d')) as $r) { $out[] = $r; }
            $cur->modify('+' . $chunkDays . ' days');
            $guard++;
        }
        return $out;
    }

    /**
     * Tanggal terakhir yang SUDAH ada datanya (qty>0). Untuk banner "data masuk s/d ...".
     * Diambil dari salinan lokal (spi_vehicle_daily, MAX tanggal) — TIDAK menyentuh SPI live.
     * Fallback LIVE 14 hari hanya bila tabel lokal belum ada/masih kosong. Cache 1 jam.
     */
    public function latestDataDate(): ?string
    {
        $cacheKey = 'spi_latest_' . $this->site;
        if (($hit = cache($cacheKey)) !== null) { return $hit ?: null; }

        $latest = '';
        $db = \Config\Database::connect();
        if ($db->tableExists('spi_vehicle_daily')) {
            $row = $db->table('spi_vehicle_daily')->selectMax('tanggal', 'mx')
                ->where('total >', 0)->get()->getRowArray();
            $latest = (string) ($row['mx'] ?? '');
        }
        if ($latest === '') { // fallback LIVE (DB belum terisi)
            $start = date('Y-m-d', strtotime('-14 days'));
            foreach ($this->fetchDailyQty($start, date('Y-m-d')) as $r) {
                if (($r['total'] ?? 0) > 0 && $r['tanggal'] > $latest) { $latest = $r['tanggal']; }
            }
        }
        if ($latest !== '') { cache()->save($cacheKey, $latest, 3600); }
        return $latest ?: null;
    }

    /** Bucket durasi (urut) — dipakai parser & penyimpanan. */
    private const DUR_KEYS = ['le1', 'h1_2', 'h2_3', 'h3_4', 'h4_5', 'h5_6', 'h6_7', 'gt7'];

    /**
     * Statistik PER HARI dari statistik.php: distribusi durasi (8 bucket) + jumlah Casual (paid)
     * & Pass (free) per jenis. Endpoint balik kosong utk rentang besar → di-chunk ≤ $chunkDays.
     * Sumber LENGKAP (≠ table-casual-income yg patchy). Dipakai SYNC (lambat, JANGAN di page-load).
     * @return array<string,array{dur:array<string,int>, paid:array<string,int>, free:array<string,int>}>
     */
    public function fetchStatistikDaily(string $startDate, string $endDate, int $chunkDays = 45): array
    {
        $out = [];
        try { $cur = new \DateTime($startDate); $end = new \DateTime($endDate); }
        catch (\Throwable $t) { return $out; }
        $guard = 0;
        while ($cur <= $end && $guard < 200) {
            $cs = clone $cur;
            $ce = clone $cur; $ce->modify('+' . ($chunkDays - 1) . ' days');
            if ($ce > $end) { $ce = clone $end; }
            $this->parseStatistikChunk($cs->format('Y-m-d'), $ce->format('Y-m-d'), $out);
            $cur->modify('+' . $chunkDays . ' days');
            $guard++;
        }
        return $out;
    }

    /** Parse satu potongan statistik.php → akumulasi durasi + paid/free per jenis per tanggal. */
    private function parseStatistikChunk(string $start, string $end, array &$out): void
    {
        $veh = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $url = $this->apiHost . '/reporting2_api/statistik.php?site=' . rawurlencode($this->site)
            . '&tgl1=' . rawurlencode($start)
            . '&tgl2=' . rawurlencode(date('Y-m-d', strtotime($end . ' +1 day'))); // end eksklusif
        $html = $this->httpGet($url, false);
        if (! $html || ! preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows)) { return; }
        $curDate = null;
        foreach ($rows[1] as $row) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/s', $row, $cells);
            $txt = array_values(array_filter(
                array_map(fn($c) => trim(strip_tags($c)), $cells[1] ?? []),
                fn($c) => $c !== ''
            ));
            if (! $txt) { continue; }
            $idx = 0;
            if (preg_match('/^(\d{2}-[A-Za-z]{3}-\d{4})/', $txt[0], $dm)) {
                $ts = strtotime($dm[1]);
                if ($ts) { $curDate = date('Y-m-d', $ts); }
                $idx = 1; // label kendaraan di kolom berikutnya
            }
            $label = $txt[$idx] ?? '';
            if (! $curDate || ! preg_match('/^(Mobil|Motor|Box|Truck|Taxi|Bus)\s+(Casual|Pass)/i', $label, $lm)) { continue; }
            $v    = strtolower($lm[1]);
            $type = strtolower($lm[2]);
            $nums = array_values(array_map(
                fn($c) => (int) str_replace(',', '', $c),
                array_filter($txt, fn($c) => preg_match('/^[\d,]+$/', $c))
            ));
            if (count($nums) < 9) { continue; }
            $buckets = array_slice($nums, -9, 8);   // 8 bucket sebelum TOTAL
            $total   = $nums[count($nums) - 1];      // TOTAL baris (= jumlah kendaraan jenis×tipe hari itu)
            if (! isset($out[$curDate])) {
                $out[$curDate] = ['dur' => array_fill_keys(self::DUR_KEYS, 0),
                    'paid' => array_fill_keys($veh, 0), 'free' => array_fill_keys($veh, 0)];
            }
            foreach (self::DUR_KEYS as $i => $k) { $out[$curDate]['dur'][$k] += $buckets[$i] ?? 0; }
            if ($type === 'pass') { $out[$curDate]['free'][$v] += $total; }
            else                  { $out[$curDate]['paid'][$v] += $total; }
        }
    }

    /**
     * Arus masuk/keluar HARI INI dari dashboard SPI /home (server-rendered, kumulatif berjalan):
     *  - hourly: per jam, jumlah SEMUA jenis kendaraan (sum datasets).
     *  - gates:  per pintu (gate), masuk & keluar.
     * @return array{hourly:array<int,array{masuk:int,keluar:int}>, gates:array{masuk:array<string,int>,keluar:array<string,int>}}
     */
    public function fetchDashboardFlows(): array
    {
        $out = ['hourly' => [], 'gates' => ['masuk' => [], 'keluar' => []], 'types' => [], 'payments' => []];
        if (! $this->ensureLogin()) { return $out; }
        $html = $this->httpGet($this->base . '/home', true);
        if (! $html) { return $out; }

        $out['gates']['masuk']  = $this->parseGateChart($html, 'myChartAksesMasuk');
        $out['gates']['keluar'] = $this->parseGateChart($html, 'myChartAksesKeluar');

        // Per jenis kendaraan (dikelompokkan ke jenis dasar mobil/motor/box/truck/taxi/bus):
        //  - masuk dari "Jenis Kendaraan" (myChartJenisKendaraan)
        //  - income dari "Income per Jenis" (myChartTypeBar) — label ber-prefix operator
        $types = [];
        foreach ($this->parseGateChart($html, 'myChartJenisKendaraan') as $lab => $v) {
            $bt = $this->baseType($lab); $types[$bt]['masuk'] = ($types[$bt]['masuk'] ?? 0) + (int) $v;
        }
        foreach ($this->parseGateChart($html, 'myChartTypeBar') as $lab => $v) {
            $bt = $this->baseType($lab); $types[$bt]['income'] = ($types[$bt]['income'] ?? 0) + (int) $v;
        }
        $out['types'] = $types;

        // Income per metode pembayaran (myChartTypeBar2 "Jenis Pembayaran")
        foreach ($this->parseGateChart($html, 'myChartTypeBar2') as $lab => $v) {
            $out['payments'][$lab] = (int) $v;
        }

        $masuk  = $this->parseHourChart($html, 'myChartMasukStatistik');
        $keluar = $this->parseHourChart($html, 'statistics');
        foreach ($masuk as $h => $v)  { $out['hourly'][$h]['masuk']  = $v; $out['hourly'][$h]['keluar'] ??= 0; }
        foreach ($keluar as $h => $v) { $out['hourly'][$h]['keluar'] = $v; $out['hourly'][$h]['masuk']  ??= 0; }
        ksort($out['hourly']);
        return $out;
    }

    /** Chart per-pintu: ambil var xValues/yValues terakhir sebelum new Chart("$canvas"). */
    private function parseGateChart(string $html, string $canvas): array
    {
        $p = strpos($html, 'new Chart("' . $canvas . '"');
        if ($p === false) { $p = strpos($html, "new Chart('" . $canvas . "'"); }
        if ($p === false) { return []; }
        $pre = substr($html, 0, $p);
        if (! preg_match_all('/var\s+xValues\s*=\s*\[([^\]]*)\]/s', $pre, $xm)
            || ! preg_match_all('/var\s+yValues\s*=\s*\[([^\]]*)\]/s', $pre, $ym)) { return []; }
        $gates = array_map(fn($s) => trim($s, " \t\n\r\"'"), array_filter(explode(',', end($xm[1])), fn($s) => trim($s) !== ''));
        $vals  = array_map(fn($s) => (int) trim($s), array_filter(explode(',', end($ym[1])), fn($s) => trim($s) !== ''));
        $res = [];
        foreach ($gates as $i => $g) { if ($g !== '') { $res[$g] = (int) ($vals[$i] ?? 0); } }
        return $res;
    }

    /** Chart jam: jumlahkan semua dataset data:[...] per bucket, map label "H - H+1" → jam. */
    private function parseHourChart(string $html, string $canvas): array
    {
        $p = strpos($html, 'new Chart("' . $canvas . '"');
        if ($p === false) { $p = strpos($html, "new Chart('" . $canvas . "'"); }
        if ($p === false) { return []; }
        $pre = substr($html, 0, $p);
        if (! preg_match_all('/var\s+xValues\s*=\s*\[([^\]]*)\]/s', $pre, $xm)) { return []; }
        $labels = array_values(array_filter(array_map('trim', explode(',', end($xm[1])))));
        $labels = array_map(fn($s) => trim($s, " \t\n\r\"'"), $labels);

        $nextP = strpos($html, 'new Chart(', $p + 10);
        $block = substr($html, $p, $nextP !== false ? $nextP - $p : 5000);
        preg_match_all('/data:\s*\[([^\]]*)\]/s', $block, $dm);
        $sum = array_fill(0, count($labels), 0);
        foreach ($dm[1] as $arr) {
            $i = 0;
            foreach (explode(',', $arr) as $tok) {
                $tok = trim($tok);
                if ($tok === '') { continue; }
                if (isset($sum[$i])) { $sum[$i] += (int) $tok; }
                $i++;
            }
        }
        $res = [];
        foreach ($labels as $i => $lab) {
            if (preg_match('/^(\d+)\s*-\s*(\d+)/', $lab, $lm)) { $res[(int) $lm[1]] = $sum[$i] ?? 0; }
        }
        return $res;
    }

    /** Kelompokkan label kendaraan (kadang ber-prefix operator: MOBILJATRA) ke jenis dasar. */
    private function baseType(string $label): string
    {
        $l = strtolower(preg_replace('/[^a-z]/i', '', $label));
        foreach (['mobil', 'motor', 'box', 'taxi', 'bus'] as $t) {
            if (str_starts_with($l, $t)) { return $t; }
        }
        if (str_starts_with($l, 'truk') || str_starts_with($l, 'truck')) { return 'truck'; }
        return 'lain';
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
