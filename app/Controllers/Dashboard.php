<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\DailyTrafficModel;
use App\Models\ThemePeriodModel;

class Dashboard extends BaseController
{
    public function index()
    {
        // Inputter traffic-only (mis. Security outsource: can_edit tanpa can_view)
        // tidak boleh lihat dashboard makro — arahkan langsung ke input traffic.
        $isAdmin   = session()->get('role_is_admin') || session()->get('user_role') === 'admin';
        $deptMenus = session()->get('dept_menus');
        if (! $isAdmin && is_array($deptMenus)
            && ! ($deptMenus['traffic']['can_view'] ?? false)
            && ($deptMenus['traffic']['can_edit'] ?? false)) {
            return redirect()->to('/traffic');
        }

        $user   = $this->currentUser();
        $model  = new EventModel();
        $events = $model->getEventsForUser($user['id'], $user['role']);

        $counts = [
            'total'     => count($events),
            'active'    => count(array_filter($events, fn($e) => $e['status'] === 'active')),
            'draft'     => count(array_filter($events, fn($e) => $e['status'] === 'draft')),
            'completed' => count(array_filter($events, fn($e) => $e['status'] === 'completed')),
        ];

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $trafficModel = new DailyTrafficModel();

        $traffic = [
            'ewalk' => [
                'today'     => $trafficModel->getPeriodTotal($today, $today, 'ewalk'),
                'month'     => $trafficModel->getPeriodTotal($monthStart, $today, 'ewalk'),
                'last_date' => $trafficModel->getLatestDate('ewalk'),
            ],
            'pentacity' => [
                'today'     => $trafficModel->getPeriodTotal($today, $today, 'pentacity'),
                'month'     => $trafficModel->getPeriodTotal($monthStart, $today, 'pentacity'),
                'last_date' => $trafficModel->getLatestDate('pentacity'),
            ],
        ];

        // ── Lintas-dept: event hari ini & minggu ini + hari libur terdekat ──
        $weekEnd = date('Y-m-d', strtotime('+7 days'));
        $todayEvents = $upcomingEvents = [];
        foreach ($events as $e) {
            if (empty($e['start_date'])) continue;
            $start = $e['start_date'];
            $days  = max(1, (int) ($e['event_days'] ?? 1));
            $end   = date('Y-m-d', strtotime($start . ' +' . ($days - 1) . ' days'));
            if ($start <= $today && $end >= $today) {
                $e['day_now'] = (int) ((strtotime($today) - strtotime($start)) / 86400) + 1;
                $e['day_total'] = $days;
                $todayEvents[] = $e;
            } elseif ($start > $today && $start <= $weekEnd) {
                $upcomingEvents[] = $e;
            }
        }
        usort($upcomingEvents, fn($a, $b) => strcmp($a['start_date'], $b['start_date']));

        $upcomingHolidays = (new \App\Models\PublicHolidayModel())
            ->where('tanggal >=', $today)->orderBy('tanggal')->findAll(4);

        $bbmNews = [];

        // BI Rate, Inflasi, IHSG: pakai cache jika ada, fallback ke loading state (async JS fetch)
        $biRate  = cache('eco_bi_rate')  ?? ['pct' => '—', 'per' => '', 'live' => false, 'loading' => true];
        $inflasi = cache('eco_inflasi')  ?? ['pct' => '—', 'per' => '', 'live' => false, 'loading' => true];
        $ihsg    = cache('eco_ihsg')     ?? ['pct' => '—', 'per' => '', 'live' => false, 'loading' => true, 'chg' => null, 'chg_dir' => 'flat'];

        // GDP & PDRB: baca dari DB (bisa di-edit admin), fallback ke nilai default
        $gdp    = $this->getMacroIndicator('gdp',     '5,61', 'Q1 2026 (YoY, BPS)');
        $gdpBpn = $this->getMacroIndicator('gdp_bpn', '7,97', 'Q1 2025 (YoY, BPS Balikpapan)');

        $economicData = [
            'bi_rate'   => $biRate,
            'inflation' => $inflasi,
            'gdp'       => $gdp,
            'gdp_bpn'   => $gdpBpn,
            'ihsg'      => $ihsg,
            'bbm'       => $this->getBbmPrices(),
            'bbm_per'   => $this->getBbmPer(),
        ];

        $themePeriods = (new ThemePeriodModel())->getTodayPeriods();

        return view('dashboard/index', [
            'user'         => $user,
            'events'       => $events,
            'counts'       => $counts,
            'traffic'      => $traffic,
            'today'        => $today,
            'todayEvents'      => $todayEvents,
            'upcomingEvents'   => $upcomingEvents,
            'upcomingHolidays' => $upcomingHolidays,
            'economicData' => $economicData,
            'bbmNews'      => $bbmNews,
            'themePeriods' => $themePeriods,
        ]);
    }

    // Auto-fetch BBM prices from MyPertamina — available to all authenticated users
    public function autoFetchBbm()
    {
        // Prevent simultaneous fetches (e.g. multiple users on dashboard at once)
        if (cache('bbm_fetching')) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Sedang dalam proses fetch oleh user lain.']);
        }
        cache()->save('bbm_fetching', true, 60);

        $result = $this->fetchBbmFromPertamina();
        cache()->delete('bbm_fetching');
        if (! $result) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Gagal mengambil data dari Pertamina. Coba update manual.']);
        }

        $db  = db_connect();
        $per = date('d M Y');
        $db->table('economic_indicators')
           ->replace(['key' => 'bbm_prices', 'value' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
        $db->table('economic_indicators')
           ->replace(['key' => 'bbm_per', 'value' => $per]);
        cache()->delete('eco_bbm');

        return $this->response->setJSON(['ok' => true, 'prices' => $result, 'per' => $per]);
    }

    private function fetchBbmFromPertamina(): ?array
    {
        // MyPertamina public API — no auth required, not Cloudflare-protected
        $province = env('BBM_PROVINCE', 'kalimantan timur');
        $url = 'https://api.web.mypertamina.id/price?search=' . urlencode($province);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MallIC/1.4)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Referer: https://mypertamina.id/about/product-price',
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! $raw || $code < 200 || $code >= 300) return null;

        $json = json_decode($raw, true);
        if (($json['status'] ?? '') !== 'success') return null;

        // Get the first province match
        $provinces = $json['data']['data'] ?? [];
        if (empty($provinces)) return null;

        $listPrice = $provinces[0]['list_price'] ?? [];
        return $this->parseBbmResponse($listPrice);
    }

    private function parseBbmResponse(array $listPrice): ?array
    {
        // Display name + subsidi flag per product code
        $productMap = [
            'PERTALITE'                   => ['Pertalite',           true],
            'PERTAMAX'                    => ['Pertamax',            false],
            'PERTAMAX TURBO'              => ['Pertamax Turbo',      false],
            'PERTAMAX GREEN 95'           => ['Pertamax Green 95',   false],
            'PERTAMAX PERTASHOP'          => ['Pertamax Pertashop',  false],
            'DEXLITE'                     => ['Dexlite',             false],
            'PERTAMINA DEX'               => ['Pertamina Dex',       false],
            'PERTAMINA BIOSOLAR SUBSIDI'  => ['Solar Subsidi',       true],
            'PERTAMINA BIOSOLAR NON SUBSIDI' => ['Biosolar Non Subsidi', false],
        ];

        $prices = [];
        foreach ($listPrice as $item) {
            $code = strtoupper(trim($item['product'] ?? ''));
            if (! isset($productMap[$code])) continue;

            // Normalize price: strip "Rp", dots, spaces → integer
            $raw   = preg_replace('/[^0-9]/', '', $item['price'] ?? '0');
            $harga = (int)$raw;
            if ($harga < 1000 || $harga > 100000) continue; // skip zero or bogus

            [$nama, $subsidi] = $productMap[$code];
            $prices[] = ['nama' => $nama, 'harga' => $harga, 'subsidi' => $subsidi];
        }

        return count($prices) >= 2 ? $prices : null;
    }

    // Admin only — update BBM prices from dashboard modal
    public function updateBbm()
    {
        if ($this->currentUser()['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $post   = $this->request->getPost();
        $prices = [];

        foreach (($post['nama'] ?? []) as $i => $nama) {
            $nama  = trim($nama);
            $harga = (int)preg_replace('/\D/', '', $post['harga'][$i] ?? '0');
            if (! $nama || $harga <= 0) continue;
            $prices[] = [
                'nama'    => $nama,
                'harga'   => $harga,
                'subsidi' => ! empty($post['subsidi'][$i]),
            ];
        }

        if (empty($prices)) {
            return redirect()->back()->with('error', 'Data BBM tidak valid.');
        }

        $per = trim($post['bbm_per'] ?? date('M Y'));
        $db  = db_connect();
        $db->table('economic_indicators')
            ->replace(['key' => 'bbm_prices', 'value' => json_encode($prices, JSON_UNESCAPED_UNICODE)]);
        $db->table('economic_indicators')
            ->replace(['key' => 'bbm_per', 'value' => $per]);

        cache()->delete('eco_bbm');

        return redirect()->to('/')->with('success', 'Harga BBM diperbarui.');
    }

    // JSON endpoint — dipanggil AJAX dari browser setelah halaman render
    public function newsFeed()
    {
        $eco = $this->fetchEconomicNews();
        $bpn = $this->fetchBalikapanNews();
        return $this->response->setJSON([
            'eco' => $eco,
            'bpn' => $bpn,
            'bbm' => $this->filterBbmNews($eco),
        ]);
    }

    // JSON endpoint — fetch BI Rate & Inflasi secara async (dipanggil jika cache kosong)
    public function economicLive()
    {
        $data = $this->getEconomicData();
        return $this->response->setJSON([
            'bi_rate'   => $data['bi_rate'],
            'inflation' => $data['inflation'],
        ]);
    }

    // JSON endpoint — fetch IHSG (^JKSE) dari Yahoo Finance secara async
    public function ihsgLive()
    {
        $cached = cache('eco_ihsg');
        if ($cached !== null && empty($cached['loading'])) {
            return $this->response->setJSON(['ihsg' => $cached]);
        }
        return $this->response->setJSON(['ihsg' => $this->fetchIhsg()]);
    }

    // Prakiraan cuaca 7 hari (Balikpapan) via Open-Meteo — gratis, tanpa API key. Cache 2 jam.
    public function weatherForecast()
    {
        $cached = cache('weather_bpn_v3');
        if ($cached !== null) return $this->response->setJSON(['ok' => true] + $cached);

        $url = 'https://api.open-meteo.com/v1/forecast?latitude=-1.2675&longitude=116.8289'
            . '&daily=weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_probability_max'
            . '&hourly=weather_code,temperature_2m,apparent_temperature,precipitation_probability'
            . '&timezone=Asia%2FMakassar&forecast_days=7';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'MallIC/1.0',
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = $raw ? json_decode($raw, true) : null;
        if ($code !== 200 || empty($json['daily']['time'])) {
            return $this->response->setJSON(['ok' => false, 'msg' => 'Gagal mengambil data cuaca.']);
        }

        $d = $json['daily'];
        $days = [];
        $hari = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
        foreach ($d['time'] as $i => $tgl) {
            $info = $this->weatherCodeInfo((int) ($d['weather_code'][$i] ?? 0));
            $days[] = [
                'tanggal'  => $tgl,
                'hari'     => $hari[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl)),
                'tgl_label'=> date('d/m', strtotime($tgl)),
                'tmax'     => isset($d['temperature_2m_max'][$i]) ? round($d['temperature_2m_max'][$i]) : null,
                'tmin'     => isset($d['temperature_2m_min'][$i]) ? round($d['temperature_2m_min'][$i]) : null,
                'feels_max'=> isset($d['apparent_temperature_max'][$i]) ? round($d['apparent_temperature_max'][$i]) : null,
                'feels_min'=> isset($d['apparent_temperature_min'][$i]) ? round($d['apparent_temperature_min'][$i]) : null,
                'hujan'    => $d['precipitation_probability_max'][$i] ?? null,
                'icon'     => $info['icon'],
                'label'    => $info['label'],
            ];
        }

        // Hourly hari ini saja (per jam)
        $hours = [];
        $today = date('Y-m-d');
        $h = $json['hourly'] ?? [];
        foreach (($h['time'] ?? []) as $i => $t) {
            if (strpos($t, $today) !== 0) continue; // hanya hari ini
            $info = $this->weatherCodeInfo((int) ($h['weather_code'][$i] ?? 0));
            $hours[] = [
                'jam'   => date('H:i', strtotime($t)),
                'temp'  => isset($h['temperature_2m'][$i]) ? round($h['temperature_2m'][$i]) : null,
                'feels' => isset($h['apparent_temperature'][$i]) ? round($h['apparent_temperature'][$i]) : null,
                'hujan' => $h['precipitation_probability'][$i] ?? null,
                'icon'  => $info['icon'],
                'label' => $info['label'],
            ];
        }

        $payload = ['days' => $days, 'hours' => $hours];
        cache()->save('weather_bpn_v3', $payload, 3600); // 1 jam (hourly perlu lebih segar)
        return $this->response->setJSON(['ok' => true] + $payload);
    }

    // WMO weather code -> ikon Bootstrap + label Indonesia
    private function weatherCodeInfo(int $c): array
    {
        if ($c === 0) return ['icon' => 'bi-sun-fill', 'label' => 'Cerah'];
        if (in_array($c, [1, 2])) return ['icon' => 'bi-cloud-sun-fill', 'label' => 'Cerah Berawan'];
        if ($c === 3) return ['icon' => 'bi-clouds-fill', 'label' => 'Berawan'];
        if (in_array($c, [45, 48])) return ['icon' => 'bi-cloud-fog2-fill', 'label' => 'Berkabut'];
        if (in_array($c, [51, 53, 55, 56, 57])) return ['icon' => 'bi-cloud-drizzle-fill', 'label' => 'Gerimis'];
        if (in_array($c, [61, 63, 66, 80, 81])) return ['icon' => 'bi-cloud-rain-fill', 'label' => 'Hujan'];
        if (in_array($c, [65, 67, 82])) return ['icon' => 'bi-cloud-rain-heavy-fill', 'label' => 'Hujan Lebat'];
        if (in_array($c, [95, 96, 99])) return ['icon' => 'bi-cloud-lightning-rain-fill', 'label' => 'Badai Petir'];
        return ['icon' => 'bi-cloud-fill', 'label' => 'Berawan'];
    }

    private function fetchIhsg(): array
    {
        $fallback = ['pct' => '—', 'per' => 'IDX', 'live' => false, 'chg' => null, 'chg_dir' => 'flat'];
        if (! function_exists('curl_init')) {
            cache()->save('eco_ihsg', $fallback, 1800);
            return $fallback;
        }

        $url = 'https://query1.finance.yahoo.com/v8/finance/chart/%5EJKSE?interval=1d&range=2d&includePrePost=false';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MallIC/1.9)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! $raw || $code < 200 || $code >= 300) {
            cache()->save('eco_ihsg', $fallback, 1800);
            return $fallback;
        }

        $json = json_decode($raw, true);
        $meta = $json['chart']['result'][0]['meta'] ?? null;
        if (! $meta || empty($meta['regularMarketPrice'])) {
            cache()->save('eco_ihsg', $fallback, 1800);
            return $fallback;
        }

        $price = (float)$meta['regularMarketPrice'];
        $prev  = (float)($meta['previousClose'] ?? 0);

        $chgPct = $prev > 0 ? (($price - $prev) / $prev) * 100 : null;
        $chgDir = $chgPct === null ? 'flat' : ($chgPct > 0.05 ? 'up' : ($chgPct < -0.05 ? 'down' : 'flat'));
        $chgStr = $chgPct !== null
            ? ($chgPct >= 0 ? '+' : '') . number_format($chgPct, 2, ',', '.') . '%'
            : null;

        $ts  = isset($meta['regularMarketTime']) ? (int)$meta['regularMarketTime'] : time();
        $per = 'IDX • ' . date('d M Y H:i', $ts) . ' WIB';

        $result = [
            'pct'        => number_format($price, 2, ',', '.'),
            'per'        => $per,
            'live'       => true,
            'chg'        => $chgStr,
            'chg_dir'    => $chgDir,
            'fetched_at' => date('d M Y H:i'),
        ];

        cache()->save('eco_ihsg', $result, 1800);
        return $result;
    }

    public function economicDebug()
    {
        if (session()->get('user_role') !== 'admin') {
            return $this->response->setJSON(['error' => 'admin only'])->setStatusCode(403);
        }

        $result = [
            'curl_available'       => function_exists('curl_init'),
            'curl_multi_available' => function_exists('curl_multi_init'),
            'php_version'          => PHP_VERSION,
            'server_ip'            => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        ];

        // Test outbound connection to bi.go.id
        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.bi.go.id/id/statistik/indikator/BI-Rate.aspx');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT      => 'Mozilla/5.0',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $html = curl_exec($ch);
            $result['bi_curl_http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result['bi_curl_error']     = curl_error($ch) ?: null;
            $result['bi_curl_errno']     = curl_errno($ch);
            $result['bi_response_len']   = $html ? strlen($html) : 0;
            curl_close($ch);

            // Try to parse rate from response
            if ($html) {
                $months = 'Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember';
                if (preg_match('/(\d{1,2}\s+(?:' . $months . ')\s+\d{4})[^%]{0,200}?(\d+[,\.]\d+)\s*%/s', $html, $m)) {
                    $result['parsed_date'] = $m[1];
                    $result['parsed_rate'] = $m[2] . '%';
                } else {
                    $result['parsed_date'] = null;
                    $result['parsed_rate'] = 'regex no match';
                    // Snippet for debugging
                    $result['html_snippet'] = substr(strip_tags($html), 0, 500);
                }
            }
        }

        // Cache status
        $cached = cache('eco_bi_rate');
        $result['cache_bi_rate'] = $cached;

        return $this->response->setJSON($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ── Macro indicators — BI Rate & Inflasi di-fetch paralel ────────────────
    private function getEconomicData(): array
    {
        $biRate  = cache('eco_bi_rate');
        $inflasi = cache('eco_inflasi');

        if ($biRate === null || $inflasi === null) {
            $html    = $this->fetchBiPagesParallel();
            $biRate  = $biRate  ?? $this->parseBiRate($html['bi_rate']);
            $inflasi = $inflasi ?? $this->parseInflasi($html['inflasi']);
        }

        return [
            'bi_rate'   => $biRate,
            'inflation' => $inflasi,
            'gdp'       => ['pct' => '5,61', 'per' => 'Q1 2026 (YoY, BPS)', 'live' => false],
            'gdp_bpn'   => ['pct' => '7,97', 'per' => 'Q1 2025 (YoY, BPS Balikpapan)', 'live' => false],
            'bbm'       => $this->getBbmPrices(),
            'bbm_per'   => $this->getBbmPer(),
        ];
    }

    private function fetchBiPagesParallel(): array
    {
        $empty = ['bi_rate' => '', 'inflasi' => ''];
        if (! function_exists('curl_multi_init')) return $empty;

        $urls = [
            'bi_rate' => 'https://www.bi.go.id/id/statistik/indikator/BI-Rate.aspx',
            'inflasi'  => 'https://www.bi.go.id/id/statistik/indikator/data-inflasi.aspx',
        ];
        $mh      = curl_multi_init();
        $handles = [];
        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MallIC/1.2)',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING       => 'gzip, deflate',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh);
        } while ($running && $status === CURLM_OK);

        $result = [];
        foreach ($handles as $key => $ch) {
            $raw  = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $result[$key] = ($raw && $code >= 200 && $code < 400) ? $raw : '';
        }
        curl_multi_close($mh);
        return $result;
    }

    private function parseBiRate(string $html): array
    {
        $fallback = ['pct' => '4,75', 'per' => '20 Mei 2026', 'live' => false];
        if (! $html) {
            cache()->save('eco_bi_rate', $fallback, 3600);
            return $fallback;
        }
        $months = 'Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember';
        if (preg_match('/(\d{1,2}\s+(?:' . $months . ')\s+\d{4})[^%]{0,200}?(\d+[,\.]\d+)\s*%/s', $html, $m)) {
            $result = ['pct' => str_replace('.', ',', $m[2]), 'per' => $m[1], 'live' => true, 'fetched_at' => date('d M Y H:i')];
            cache()->save('eco_bi_rate', $result, 21600);
            return $result;
        }
        cache()->save('eco_bi_rate', $fallback, 3600);
        return $fallback;
    }

    private function parseInflasi(string $html): array
    {
        $fallback = ['pct' => '2,42', 'per' => 'Apr 2026 (YoY, BPS)', 'live' => false];
        if (! $html) {
            cache()->save('eco_inflasi', $fallback, 3600);
            return $fallback;
        }
        $months = 'Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember';
        if (preg_match('/((?:' . $months . ')\s+\d{4})[^%]{0,100}?(\d+[,\.]\d+)\s*%/s', $html, $m)) {
            $result = ['pct' => str_replace('.', ',', $m[2]), 'per' => $m[1] . ' (YoY, BPS)', 'live' => true, 'fetched_at' => date('d M Y H:i')];
            cache()->save('eco_inflasi', $result, 21600);
            return $result;
        }
        cache()->save('eco_inflasi', $fallback, 3600);
        return $fallback;
    }

    private function getBbmPrices(): array
    {
        $cached = cache('eco_bbm');
        if ($cached !== null) return $cached;

        $row = db_connect()->table('economic_indicators')
            ->where('key', 'bbm_prices')->get()->getRowArray();

        $prices = $row ? (json_decode($row['value'], true) ?? []) : [];
        cache()->save('eco_bbm', $prices, 3600);
        return $prices;
    }

    private function getBbmPer(): string
    {
        $row = db_connect()->table('economic_indicators')
            ->where('key', 'bbm_per')->get()->getRowArray();
        return $row ? $row['value'] : date('M Y');
    }

    private function getMacroIndicator(string $key, string $defaultPct, string $defaultPer): array
    {
        $cached = cache('eco_' . $key);
        if ($cached !== null) return $cached;

        $row = db_connect()->table('economic_indicators')
            ->where('key', $key)->get()->getRowArray();
        if ($row) {
            $val = json_decode($row['value'], true);
            if (is_array($val)) {
                cache()->save('eco_' . $key, $val, 86400);
                return $val;
            }
        }

        return ['pct' => $defaultPct, 'per' => $defaultPer, 'live' => false];
    }

    // Admin only — update GDP & PDRB manual indicators
    public function updateMacro()
    {
        if ($this->currentUser()['role'] !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }
        $post = $this->request->getPost();
        $db   = db_connect();
        foreach (['gdp', 'gdp_bpn'] as $key) {
            $pct = trim($post[$key . '_pct'] ?? '');
            $per = trim($post[$key . '_per'] ?? '');
            if (! $pct || ! $per) continue;
            $db->table('economic_indicators')->replace([
                'key'   => $key,
                'value' => json_encode(['pct' => $pct, 'per' => $per, 'live' => false], JSON_UNESCAPED_UNICODE),
            ]);
            cache()->delete('eco_' . $key);
        }
        // BI Rate — simpan langsung ke cache (tidak ada API, harus manual jika server diblokir bi.go.id)
        $biPct = trim($post['bi_rate_pct'] ?? '');
        $biPer = trim($post['bi_rate_per'] ?? '');
        if ($biPct && $biPer) {
            cache()->save('eco_bi_rate', ['pct' => $biPct, 'per' => $biPer, 'live' => false], 30 * 24 * 3600);
        }
        return redirect()->to(base_url())->with('success', 'Data makro diperbarui.');
    }

    // ── Berita Balikpapan — IniBalikpapan + Tribun Kaltim, cache hingga tengah malam ──
    private function fetchBalikapanNews(): array
    {
        $cached = cache('bpn_news');
        if ($cached !== null) return $cached;

        $urls = [
            'https://www.inibalikpapan.com/feed/',
            'https://kaltim.tribunnews.com/rss',
        ];

        $all = $this->fetchMultiRss($urls, 40);

        // Prioritaskan berita yang berkaitan ekonomi/pembangunan Balikpapan
        $priority = ['ekonomi','bisnis','investasi','pembangunan','proyek','ikn',
                     'mall','pasar','umkm','perdagangan','infrastruktur','harga',
                     'pajak','industri','pertumbuhan','lapangan kerja','phk',
                     'kenaikan','anggaran','apbd','pelabuhan','bandara','tol'];

        $ranked = array_map(function ($item) use ($priority) {
            $title  = mb_strtolower($item['title']);
            $weight = 0;
            foreach ($priority as $kw) {
                if (str_contains($title, $kw)) $weight++;
            }
            return array_merge($item, ['weight' => $weight]);
        }, $all);

        usort($ranked, fn($a, $b) => $b['weight'] !== $a['weight']
            ? $b['weight'] - $a['weight']
            : $b['ts'] - $a['ts']
        );

        $items = array_slice($ranked, 0, 8);
        $ttl   = max(300, strtotime('tomorrow') - time());
        cache()->save('bpn_news', $items, $ttl);
        return $items;
    }

    // ── RSS multi-source, fetch paralel, cache hingga tengah malam ────────────
    private function fetchEconomicNews(): array
    {
        $cached = cache('eco_news');
        if ($cached !== null) return $cached;

        if (! function_exists('curl_multi_init')) return [];

        // Default: 4 sumber yang telah diverifikasi aktif.
        // Override via ECONOMIC_NEWS_RSS di .env (comma-separated URLs).
        $defaultFeeds = [
            'https://www.cnbcindonesia.com/rss',
            'https://www.cnnindonesia.com/ekonomi/rss',
            'https://finance.detik.com/rss.xml',
            'https://www.antaranews.com/rss/ekonomi.xml',
            'https://www.antaranews.com/rss/ekonomi-finansial.xml',
        ];

        $envVal = env('ECONOMIC_NEWS_RSS', '');
        $urls   = $envVal
            ? array_filter(array_map('trim', explode(',', $envVal)))
            : $defaultFeeds;

        $items = $this->fetchMultiRss($urls, 20);   // ambil lebih banyak untuk filter BBM

        $ttl = max(300, strtotime('tomorrow') - time());
        cache()->save('eco_news', $items, $ttl);
        return $items;
    }

    private function fetchMultiRss(array $urls, int $limit = 20): array
    {
        $mh      = curl_multi_init();
        $handles = [];

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'MallIC/1.2',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        // Jalankan semua request secara paralel
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh);
        } while ($running && $status === CURLM_OK);

        $all = [];
        foreach ($handles as $ch) {
            $raw  = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if (! $raw || $code < 200 || $code >= 400) continue;

            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (! $xml) continue;

            $channel = $xml->channel ?? $xml;
            foreach (($channel->item ?? []) as $item) {
                $title = html_entity_decode(trim((string)$item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $link  = trim((string)$item->link);
                $ts    = @strtotime((string)$item->pubDate) ?: 0;
                if (! $title || ! $link) continue;
                $all[] = ['title' => $title, 'link' => $link, 'ts' => $ts];
            }
        }
        curl_multi_close($mh);

        // Urutkan terbaru dulu, lalu deduplikasi berdasarkan judul
        usort($all, fn($a, $b) => $b['ts'] - $a['ts']);

        $seen  = [];
        $items = [];
        foreach ($all as $a) {
            $key = mb_strtolower(mb_substr($a['title'], 0, 40));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $ts      = $a['ts'];
            $items[] = [
                'title'    => $a['title'],
                'link'     => $a['link'],
                'ts'       => $a['ts'],
                'date_fmt' => $ts ? date('d M Y H:i', $ts) : '',
                'age_min'  => $ts ? max(0, (int)round((time() - $ts) / 60)) : null,
            ];
            if (count($items) >= $limit) break;
        }
        return $items;
    }

    // Filter berita yang mengandung kata kunci BBM
    private function filterBbmNews(array $news): array
    {
        $keywords = ['bbm', 'pertalite', 'pertamax', 'bensin', 'solar subsidi',
                     'bahan bakar', 'harga minyak', 'dexlite', 'biosolar',
                     'pertamina dex', 'kenaikan bbm', 'harga bbm'];
        $result = [];
        foreach ($news as $item) {
            $title = mb_strtolower($item['title']);
            foreach ($keywords as $kw) {
                if (str_contains($title, $kw)) {
                    $result[] = $item;
                    break;
                }
            }
            if (count($result) >= 3) break;
        }
        return $result;
    }
}
