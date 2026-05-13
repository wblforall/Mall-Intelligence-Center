<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\DailyTrafficModel;

class Dashboard extends BaseController
{
    public function index()
    {
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

        $bbmNews = [];

        // BI Rate & Inflasi: pakai cache jika ada, fallback ke loading state (async JS fetch)
        $biRate  = cache('eco_bi_rate')  ?? ['pct' => '—', 'per' => '', 'live' => false, 'loading' => true];
        $inflasi = cache('eco_inflasi')  ?? ['pct' => '—', 'per' => '', 'live' => false, 'loading' => true];

        // GDP & PDRB: baca dari DB (bisa di-edit admin), fallback ke nilai default
        $gdp    = $this->getMacroIndicator('gdp',     '5,61', 'Q1 2026 (YoY, BPS)');
        $gdpBpn = $this->getMacroIndicator('gdp_bpn', '7,97', 'Q1 2025 (YoY, BPS Balikpapan)');

        $economicData = [
            'bi_rate'   => $biRate,
            'inflation' => $inflasi,
            'gdp'       => $gdp,
            'gdp_bpn'   => $gdpBpn,
            'bbm'       => $this->getBbmPrices(),
            'bbm_per'   => $this->getBbmPer(),
        ];

        return view('dashboard/index', [
            'user'         => $user,
            'events'       => $events,
            'counts'       => $counts,
            'traffic'      => $traffic,
            'today'        => $today,
            'economicData' => $economicData,
            'bbmNews'      => $bbmNews,
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
        $fallback = ['pct' => '4,75', 'per' => '22 Apr 2026', 'live' => false];
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
