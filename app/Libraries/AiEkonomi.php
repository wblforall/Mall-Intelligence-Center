<?php

namespace App\Libraries;

use App\Controllers\Dashboard;

/**
 * Analisa AI Ekonomi harian untuk tab Market Intelligence (Insight, Daya Beli
 * & Segmen, Segmen Prospektif).
 *
 * Alur ({@see buat}):
 *   1. segarkanSumber()  — ambil ulang BI Rate/Inflasi/IHSG/berita (lewati cache).
 *   2. kumpulFakta()     — susun fakta; tiap indikator yang BERHASIL disimpan ke
 *                          eco_snapshots, yang gagal dipulihkan dari snapshot
 *                          terakhir (umurnya dicatat) atau dilaporkan hilang.
 *   3. tulis()           — Claude (API Messages Anthropic) menulis analisis JSON
 *                          ketat; divalidasi ketat ({@see normalisasi}).
 *   4. simpan()          — satu baris di ai_ekonomi (riwayat dipertahankan).
 *
 * Fungsi pengambil data milik Dashboard (private) dipakai ulang lewat
 * Reflection, sehingga perilaku Dashboard::index() TIDAK berubah.
 *
 * Aturan lama di view tetap menjadi cadangan bila belum ada hasil / AI gagal.
 * Kunci API hanya dibaca dari .env, tidak pernah dicetak atau disimpan.
 */
class AiEkonomi
{
    public const MODEL_BAWAAN   = 'claude-sonnet-5-5';
    public const MAX_TOKENS     = 8000;
    public const TIMEOUT        = 150;
    public const JEDA_MANUAL_DT = 600; // batas laju tombol manual: 10 menit

    /** Nama persis seperti dipakai tampilan (app/Views/dashboard/index.php). */
    public const SEKTOR = [
        'F&B / Kuliner', 'Fashion & Lifestyle', 'Elektronik & Gadget', 'Properti & Dekorasi',
        'Jasa & Hiburan', 'Logistik & Distribusi', 'Otomotif & Aksesori',
    ];
    public const KELAS = ['Kelas Bawah', 'Kelas Menengah', 'Kelas Atas', 'UMKM & Pelaku Usaha'];
    public const PROSPEKTIF = [
        'Gen Z', 'Mahasiswa', 'Milenial Produktif', 'Keluarga Muda', 'Pekerja Migas & IKN', 'Lansia & Pensiunan',
    ];
    public const ARAH   = ['naik', 'stabil', 'turun'];
    public const STATUS = ['membaik', 'terjaga', 'waspada', 'tertekan'];

    /** Ambang "basi" (hari) per indikator. */
    private const AMBANG_BASI = [
        'bi_rate' => 45, 'inflasi' => 45, 'ihsg' => 3, 'pdb' => 150, 'pdrb_bpn' => 200,
        'bbm' => 45, 'berita_ekonomi' => 3, 'berita_balikpapan' => 3,
    ];
    private const LABEL = [
        'bi_rate' => 'BI Rate', 'inflasi' => 'Inflasi', 'ihsg' => 'IHSG', 'pdb' => 'PDB nasional',
        'pdrb_bpn' => 'PDRB Balikpapan', 'bbm' => 'Harga BBM', 'berita_ekonomi' => 'Berita ekonomi nasional',
        'berita_balikpapan' => 'Berita Balikpapan',
    ];

    // ── Riset web (jadwal MINGGUAN: Senin 08.00 WITA) ──────────────────────
    /** Versi tool web search; yang pertama dicoba, bila ditolak API turun ke berikutnya. */
    public const VERSI_TOOL      = ['web_search_20260318', 'web_search_20260209'];
    public const RISET_MAX_USES  = 8;
    public const RISET_MAX_TOKEN = 16000;
    public const RISET_TIMEOUT   = 280;
    /** true = tool 20260318 menghilangkan blok hasil mentah (hemat token) TAPI URL tak bisa diverifikasi → dimatikan. */
    public const HASIL_MENTAH_DIBUANG = false;
    public const RISET_LANJUT    = 5;     // maks putaran lanjutan setelah pause_turn
    public const JEDA_RISET_DT   = 21600; // tombol admin "Riset ulang": 1x / 6 jam
    public const SEGAR_HARI      = 8;     // snapshot riset <= 8 hari = segar
    public const BASI_HARI       = 14;    // > 14 hari = peringatan; 9-14 = catatan umur

    /** Domain tepercaya (allowed_domains; subdomain ikut cocok, mis. kaltim.bps.go.id). */
    public const DOMAIN_RESMI = [
        'bi.go.id', 'bps.go.id', 'pertamina.com', 'mypertamina.id', 'idx.co.id', 'ojk.go.id',
        'kemenkeu.go.id', 'kemendag.go.id', 'balikpapankota.go.id',
    ];
    public const DOMAIN_BERITA = [
        'cnbcindonesia.com', 'cnnindonesia.com', 'antaranews.com', 'kontan.co.id', 'bisnis.com',
        'kompas.com', 'detik.com', 'tribunkaltim.co.id', 'inibalikpapan.com',
    ];

    /** Rentang wajar nilai per indikator (min, maks). */
    private const RENTANG = [
        'bi_rate' => [0, 15], 'inflasi' => [-3, 25], 'pdb' => [-15, 20], 'pdrb_bpn' => [-15, 20],
        'ihsg' => [2000, 15000], 'bbm' => [2000, 60000],
    ];

    /** Pengait uji: callable(array $body): array{code:int, body:string}. Null = HTTP sungguhan. */
    public static $transport = null;
    /** Jeda sebelum ulang galat sementara (detik); uji menaruh 0. */
    public static int $jedaUlang = 4;
    /** Galat jaringan terakhir (tanpa kunci) untuk diagnosis. */
    public static string $galatJaringan = '';
    /** true = tidak menulis apa pun ke DB (mode --coba). */
    public static bool $keringTanpaTulis = false;
    /** Snapshot riset dalam memori (mode --coba): kunci → payload. */
    private static array $memWeb = [];

    // ═════════════════════════════════════════════════════════════════════
    //  Orkestrasi
    // ═════════════════════════════════════════════════════════════════════

    /**
     * @param array{paksa?:bool, coba?:bool, tanpa_ai?:bool, riset?:bool, oleh?:?int} $opt
     *   riset=true → jalankan riset web dulu (berbayar). Bawaan false: memakai snapshot riset terakhir.
     * @return array{status:string, pesan:string, fakta?:array, hasil?:array, id?:int, token_masuk?:int, token_keluar?:int, model?:string}
     *   status: ok | sesuai | gagal | tanpa_kunci | coba
     */
    public static function buat(array $opt = []): array
    {
        $coba = ! empty($opt['coba']);
        self::$keringTanpaTulis = $coba;
        if ($coba) self::$memWeb = [];
        try {
            return self::buatInti($opt);
        } finally {
            self::$keringTanpaTulis = false;
        }
    }

    private static function buatInti(array $opt): array
    {
        $paksa = ! empty($opt['paksa']);
        $coba  = ! empty($opt['coba']);

        $rr = null;
        if (! empty($opt['riset'])) {
            $rr = self::riset(['coba' => $coba]);
        }
        self::segarkanSumber();
        $k    = self::kumpulFakta();
        $hash = $k['hash'];
        $tambah = $rr ? ['riset' => $rr] : [];

        if (! $paksa && ! $coba) {
            $last = self::barisTerakhirHariIni();
            if ($last && $last['fakta_hash'] === $hash) {
                return ['status' => 'sesuai', 'pesan' => 'Analisa hari ini sudah sesuai dengan data terkini; tidak ada panggilan AI.', 'fakta' => $k['fakta']] + $tambah;
            }
        }
        if (! empty($opt['tanpa_ai'])) {
            return ['status' => 'coba', 'pesan' => 'Hanya fakta (tanpa panggilan AI).', 'fakta' => $k['fakta']] + $tambah;
        }
        if (self::penyedia() === null) {
            return ['status' => 'tanpa_kunci', 'pesan' => 'Kunci AI belum diatur di server; tampilan memakai aturan cadangan.', 'fakta' => $k['fakta']] + $tambah;
        }

        $w = self::tulis($k['fakta']);
        if (! $w['ok']) {
            return ['status' => 'gagal', 'pesan' => $w['galat'], 'fakta' => $k['fakta'], 'token_masuk' => $w['token_masuk'] ?? 0, 'token_keluar' => $w['token_keluar'] ?? 0] + $tambah;
        }
        $hasil = self::lengkapiCatatan($w['hasil'], $k['fakta']);

        if ($coba) {
            return ['status' => 'coba', 'pesan' => 'Uji coba: hasil tidak disimpan.', 'fakta' => $k['fakta'], 'hasil' => $hasil,
                'token_masuk' => $w['token_masuk'], 'token_keluar' => $w['token_keluar'], 'model' => $w['model']] + $tambah;
        }

        $id = self::simpan($k['fakta'], $hash, $hasil, $w, $opt['oleh'] ?? null);
        return ['status' => 'ok', 'pesan' => 'Analisa tersimpan.', 'id' => $id, 'fakta' => $k['fakta'], 'hasil' => $hasil,
            'token_masuk' => $w['token_masuk'], 'token_keluar' => $w['token_keluar'], 'model' => $w['model']] + $tambah;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Sumber data (memakai ulang fungsi privat Dashboard lewat Reflection)
    // ═════════════════════════════════════════════════════════════════════

    private static function dash(): Dashboard
    {
        static $d = null;
        return $d ??= new Dashboard();
    }

    /** @return mixed */
    private static function panggilDash(string $metode, array $args = [])
    {
        $r = new \ReflectionMethod(Dashboard::class, $metode);
        $r->setAccessible(true);
        return $r->invokeArgs(self::dash(), $args);
    }

    /**
     * Ambil ulang sumber langsung (melewati cache). Cache dashboard hanya
     * diganti bila hasil baru sah; bila gagal, nilai cache lama dipulihkan
     * agar halaman Dashboard tidak jatuh ke angka bawaan.
     */
    public static function segarkanSumber(): void
    {
        $cache = cache();

        // BI Rate + Inflasi (satu putaran paralel)
        $lamaBi = $cache->get('eco_bi_rate');
        $lamaIn = $cache->get('eco_inflasi');
        try {
            $html = self::panggilDash('fetchBiPagesParallel');
            $bi   = self::panggilDash('parseBiRate', [$html['bi_rate'] ?? '']);
            $in   = self::panggilDash('parseInflasi', [$html['inflasi'] ?? '']);
            if (empty($bi['live']) && $lamaBi !== null) $cache->save('eco_bi_rate', $lamaBi, 3600);
            if (empty($in['live']) && $lamaIn !== null) $cache->save('eco_inflasi', $lamaIn, 3600);
        } catch (\Throwable $e) {
            log_message('error', '[AiEkonomi] segarkan BI gagal: ' . $e->getMessage());
        }

        // IHSG
        $lamaIh = $cache->get('eco_ihsg');
        try {
            $ih = self::panggilDash('fetchIhsg');
            if (empty($ih['live']) && $lamaIh !== null) $cache->save('eco_ihsg', $lamaIh, 1800);
        } catch (\Throwable $e) {
            log_message('error', '[AiEkonomi] segarkan IHSG gagal: ' . $e->getMessage());
        }

        // Berita (cache berlaku sampai tengah malam → buang dulu agar berita baru ikut)
        foreach (['eco_news' => 'fetchEconomicNews', 'bpn_news' => 'fetchBalikapanNews'] as $kunci => $fn) {
            $lama = $cache->get($kunci);
            $cache->delete($kunci);
            try {
                $baru = self::panggilDash($fn);
            } catch (\Throwable $e) {
                $baru = [];
                log_message('error', "[AiEkonomi] segarkan {$kunci} gagal: " . $e->getMessage());
            }
            if (empty($baru)) {
                $cache->delete($kunci);
                if (! empty($lama)) $cache->save($kunci, $lama, max(300, strtotime('tomorrow') - time()));
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Snapshot
    // ═════════════════════════════════════════════════════════════════════

    public static function simpanSnapshot(string $kunci, array $payload, string $sumber = 'langsung', ?string $diambil = null): void
    {
        if (self::$keringTanpaTulis) return; // mode --coba: tidak menulis apa pun
        try {
            db_connect()->table('eco_snapshots')->replace([
                'kunci'        => $kunci,
                'payload'      => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'sumber'       => $sumber,
                'diambil_pada' => $diambil ?? date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[AiEkonomi] simpan snapshot gagal: ' . $e->getMessage());
        }
    }

    /** @return array{payload:array, sumber:string, diambil_pada:string}|null */
    public static function ambilSnapshot(string $kunci): ?array
    {
        try {
            $r = db_connect()->table('eco_snapshots')->where('kunci', $kunci)->get()->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (! $r) return null;
        $p = json_decode((string) $r['payload'], true);
        return is_array($p) ? ['payload' => $p, 'sumber' => $r['sumber'], 'diambil_pada' => $r['diambil_pada']] : null;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Fakta
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Susun fakta + hash. Tidak ada data tenant/karyawan di dalamnya.
     *
     * @return array{fakta:array, hash:string}
     */
    public static function kumpulFakta(): array
    {
        $ind = [];
        $hilang = [];
        $catatan = [];

        // ── BI Rate, Inflasi, IHSG: dari cache dashboard (sudah disegarkan) ──
        $sumberCache = [
            'bi_rate' => ['cache' => 'eco_bi_rate', 'sat' => '%', 'bawaan' => ['4,75', '20 Mei 2026']],
            'inflasi' => ['cache' => 'eco_inflasi', 'sat' => '% YoY', 'bawaan' => ['2,42', 'Apr 2026 (YoY, BPS)']],
            'ihsg'    => ['cache' => 'eco_ihsg', 'sat' => 'poin', 'bawaan' => null],
        ];
        foreach ($sumberCache as $id => $cfg) {
            $c = cache($cfg['cache']);
            $nilai = is_array($c) ? (string) ($c['pct'] ?? '') : '';
            $per   = is_array($c) ? (string) ($c['per'] ?? '') : '';
            $adaNilai = $nilai !== '' && $nilai !== '—' && empty($c['loading']);
            $bawaan = $cfg['bawaan'] && $nilai === $cfg['bawaan'][0] && $per === $cfg['bawaan'][1];

            $entri = null;
            if ($adaNilai && ! empty($c['live'])) {
                // waktu ambil asli (cache bisa berasal dari pengambilan beberapa jam lalu)
                $tAmbil = ! empty($c['fetched_at']) ? strtotime((string) $c['fetched_at']) : false;
                $entri = ['nilai' => $nilai, 'periode' => $per, 'sumber' => 'langsung',
                    'diambil_pada' => date('Y-m-d H:i:s', $tAmbil ?: time())];
                if ($id === 'ihsg') $entri['perubahan_harian'] = $c['chg'] ?? null;
                self::simpanSnapshot($id, $entri, 'langsung');
            } elseif ($adaNilai && ! $bawaan) {
                // diisi manual oleh admin (tidak ada API)
                $entri = ['nilai' => $nilai, 'periode' => $per, 'sumber' => 'manual', 'diambil_pada' => date('Y-m-d H:i:s')];
                self::simpanSnapshot($id, $entri, 'manual');
            }
            $entri = self::gabungWeb($id, $entri, $catatan);
            $ind[$id] = self::selesaikan($id, $entri, $cfg['sat'], $hilang, $catatan);
        }

        // ── PDB & PDRB: tabel economic_indicators ──
        foreach (['pdb' => 'gdp', 'pdrb_bpn' => 'gdp_bpn'] as $id => $key) {
            $entri = null;
            $row = db_connect()->table('economic_indicators')->where('key', $key)->get()->getRowArray();
            $v = $row ? json_decode((string) $row['value'], true) : null;
            if (is_array($v) && ! empty($v['pct'])) {
                $entri = ['nilai' => (string) $v['pct'], 'periode' => (string) ($v['per'] ?? ''), 'sumber' => 'db', 'diambil_pada' => (string) $row['updated_at']];
                self::simpanSnapshot($id, $entri, 'db');
            }
            $entri = self::gabungWeb($id, $entri, $catatan);
            $ind[$id] = self::selesaikan($id, $entri, '% YoY', $hilang, $catatan);
        }

        // ── BBM ──
        $entri = null;
        $rowP = db_connect()->table('economic_indicators')->where('key', 'bbm_prices')->get()->getRowArray();
        $rowT = db_connect()->table('economic_indicators')->where('key', 'bbm_per')->get()->getRowArray();
        $daftar = $rowP ? json_decode((string) $rowP['value'], true) : null;
        if (is_array($daftar) && $daftar) {
            $rapi = [];
            foreach ($daftar as $b) {
                $rapi[] = ['nama' => (string) ($b['nama'] ?? ''), 'harga_rp_per_liter' => (int) ($b['harga'] ?? 0), 'subsidi' => ! empty($b['subsidi'])];
            }
            $entri = ['daftar' => $rapi, 'periode' => $rowT ? (string) $rowT['value'] : '', 'sumber' => 'db', 'diambil_pada' => (string) $rowP['updated_at']];
            self::simpanSnapshot('bbm', $entri, 'db');
        }
        $entri = self::gabungWeb('bbm', $entri, $catatan);
        $ind['bbm'] = self::selesaikan('bbm', $entri, 'Rp/liter', $hilang, $catatan);

        // ── Berita (judul saja) ──
        $berita = [];
        foreach (['berita_ekonomi' => ['eco_news', 20], 'berita_balikpapan' => ['bpn_news', 10]] as $id => [$kunci, $maks]) {
            $items = cache($kunci);
            $daftarB = [];
            if (is_array($items)) {
                foreach (array_slice($items, 0, $maks) as $it) {
                    $daftarB[] = [
                        'judul'   => mb_substr(trim((string) ($it['title'] ?? '')), 0, 220),
                        'sumber'  => self::namaSumber((string) ($it['link'] ?? '')),
                        'tanggal' => ! empty($it['ts']) ? date('Y-m-d', (int) $it['ts']) : null,
                    ];
                }
            }
            $entri = $daftarB
                ? ['daftar' => $daftarB, 'sumber' => 'langsung', 'diambil_pada' => date('Y-m-d H:i:s')]
                : null;
            if ($entri) self::simpanSnapshot($id, $entri, 'langsung');
            $r = self::selesaikan($id, $entri, '', $hilang, $catatan);
            $berita[$id] = $r;
        }

        // ── Headline hasil riset web (tambahan di luar RSS) ──
        $beritaRiset = null;
        $wb = self::ambilWeb('berita');
        $meta = self::ambilWeb('meta');
        if ($wb && ! empty($wb['payload']['daftar'])) {
            $umurB = max(0, (int) floor((time() - strtotime((string) $wb['diambil_pada'])) / 86400));
            if ($umurB <= self::BASI_HARI) {
                $beritaRiset = ['sumber' => 'riset_web', 'diambil_pada' => $wb['diambil_pada'], 'umur_hari' => $umurB, 'daftar' => $wb['payload']['daftar']];
            }
        }
        $infoRiset = null;
        if ($meta) {
            $infoRiset = ['terakhir' => $meta['payload']['dicek_pada'] ?? $meta['diambil_pada'],
                'umur_hari' => max(0, (int) floor((time() - strtotime((string) $meta['diambil_pada'])) / 86400)),
                'indikator_berhasil' => $meta['payload']['berhasil'] ?? []];
        }

        $fakta = [
            'tanggal_analisis' => date('Y-m-d'),
            'zona_waktu'       => 'WITA (Asia/Makassar)',
            'lokasi'           => 'Balikpapan, Kalimantan Timur (dekat IKN Nusantara)',
            'mal'              => ['E-Walk', 'Pentacity'],
            'indikator'        => $ind,
            'berita_ekonomi_nasional' => $berita['berita_ekonomi'],
            'berita_balikpapan'       => $berita['berita_balikpapan'],
            'berita_riset'            => $beritaRiset,
            'riset_web'               => $infoRiset,
            'data_hilang'      => $hilang,
            'catatan_otomatis' => $catatan,
        ];

        // Hash: hanya isi data (bukan waktu ambil/umur) agar data sama → hash sama.
        $inti = [];
        foreach (array_merge($ind, $berita) as $id => $e) {
            unset($e['diambil_pada'], $e['umur_hari'], $e['basi'], $e['dicek']);
            $inti[$id] = $e;
        }
        $inti['berita_riset'] = $beritaRiset['daftar'] ?? null;
        $inti['hilang'] = $hilang;
        $hash = hash('sha256', json_encode($inti, JSON_UNESCAPED_UNICODE));

        return ['fakta' => $fakta, 'hash' => $hash];
    }

    /**
     * Bila $entri null → coba snapshot; bila tak ada → hilang.
     * Menambahkan umur_hari, basi, dan catatan otomatis.
     */
    private static function selesaikan(string $id, ?array $entri, string $satuan, array &$hilang, array &$catatan): array
    {
        $label = self::LABEL[$id] ?? $id;
        if ($entri === null) {
            $snap = self::ambilSnapshot($id);
            if ($snap === null) {
                $hilang[] = $label;
                $catatan[] = "{$label}: tidak tersedia (pengambilan gagal dan belum ada snapshot).";
                return ['nilai' => null, 'sumber' => 'hilang', 'catatan' => 'tidak tersedia'];
            }
            $entri = $snap['payload'];
            $entri['sumber'] = 'snapshot';
            $entri['diambil_pada'] = $snap['diambil_pada'];
            $entri['asal_snapshot'] = $snap['sumber'];
        }
        if ($satuan !== '') $entri['satuan'] = $satuan;
        $umur = max(0, (int) floor((time() - strtotime((string) $entri['diambil_pada'])) / 86400));
        $entri['umur_hari'] = $umur;
        $basi = $umur > (self::AMBANG_BASI[$id] ?? 3);
        $entri['basi'] = $basi;
        $tgl = self::tglId((string) $entri['diambil_pada']);
        if ($entri['sumber'] === 'riset_web') {
            // Riset mingguan: periode rilis boleh lama (data bulanan/triwulanan); yang dinilai adalah umur CEK riset.
            $entri['dicek'] = $tgl;
            $entri['rilis_terbaru'] = (string) ($entri['periode'] ?? '');
            $basi = $umur > self::BASI_HARI;
            if ($id === 'ihsg') {
                $akhir = self::akhirPeriodeLabel((string) ($entri['periode'] ?? ''));
                if ($akhir !== null && time() - $akhir > 10 * 86400) $basi = true;
            }
            $entri['basi'] = $basi;
            if ($basi) {
                $catatan[] = "{$label}: riset web terakhir {$umur} hari lalu ({$tgl}), tergolong basi.";
            } elseif ($umur > self::SEGAR_HARI) {
                $catatan[] = "{$label}: riset terakhir {$umur} hari lalu ({$tgl}).";
            }
            if ($id === 'pdrb_bpn' && ($entri['lingkup'] ?? '') === 'kaltim') {
                $catatan[] = "{$label}: riset hanya menemukan angka tingkat Kalimantan Timur, bukan Kota Balikpapan.";
            }
            return $entri;
        }
        if ($entri['sumber'] === 'snapshot') {
            $catatan[] = "{$label}: memakai snapshot terakhir (data per {$tgl}, {$umur} hari lalu) karena pengambilan terbaru gagal.";
        } elseif ($basi) {
            $catatan[] = "{$label}: data per {$tgl} ({$umur} hari lalu), tergolong basi.";
        }
        return $entri;
    }

    private static function namaSumber(string $link): string
    {
        $h = strtolower((string) parse_url($link, PHP_URL_HOST));
        $peta = ['cnbcindonesia' => 'CNBC Indonesia', 'cnnindonesia' => 'CNN Indonesia', 'detik' => 'Detik', 'antaranews' => 'ANTARA',
            'inibalikpapan' => 'IniBalikpapan', 'tribunnews' => 'Tribun Kaltim'];
        foreach ($peta as $k => $n) {
            if (str_contains($h, $k)) return $n;
        }
        return $h ?: 'tidak diketahui';
    }

    public static function tglId(string $waktu, bool $denganJam = false): string
    {
        $t = strtotime($waktu);
        if (! $t) return $waktu;
        $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $s = date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
        return $denganJam ? $s . ' ' . date('H:i', $t) : $s;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  RISET WEB (Anthropic web search) — jadwal mingguan, Senin 08.00 WITA
    // ═════════════════════════════════════════════════════════════════════

    private const BULAN = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
        'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];
    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    /** Umur maksimum periode (hari) yang masih masuk akal per indikator. */
    private const UMUR_MAKS_PERIODE = ['bi_rate' => 400, 'inflasi' => 200, 'pdb' => 400, 'pdrb_bpn' => 800, 'ihsg' => 20, 'bbm' => 120];
    private const JENIS_PERIODE = ['bi_rate' => 'tanggal', 'inflasi' => 'bulan', 'pdb' => 'triwulan', 'pdrb_bpn' => 'triwulan', 'ihsg' => 'tanggal', 'bbm' => 'tanggal'];

    /** Waktu riset Senin 08.00 berikutnya (zona waktu aplikasi = WITA). Senin sebelum 08.00 → hari ini. */
    public static function risetBerikutnya(?int $dari = null): int
    {
        $dari ??= time();
        $n = (int) date('N', $dari);
        $senin = mktime(8, 0, 0, (int) date('n', $dari), (int) date('j', $dari) - ($n - 1), (int) date('Y', $dari));
        while ($senin <= $dari) $senin = (int) strtotime('+7 days', $senin);
        return $senin;
    }

    /** "Senin, 13 Okt 2026 08:00" */
    public static function hariTgl(int $ts, bool $jam = true): string
    {
        return self::HARI[(int) date('w', $ts)] . ', ' . self::tglId(date('Y-m-d H:i:s', $ts), $jam);
    }

    public static function semuaDomain(): array
    {
        return array_values(array_unique(array_merge(self::DOMAIN_RESMI, self::DOMAIN_BERITA)));
    }

    /** Host cocok dengan daftar domain tepercaya (domain itu sendiri atau subdomainnya). */
    public static function hostTepercaya(string $host): bool
    {
        $host = strtolower(trim($host, '.'));
        if ($host === '') return false;
        foreach (self::semuaDomain() as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) return true;
        }
        return false;
    }

    /** URL http(s) dengan host tepercaya (dipakai validasi dan tampilan tautan). */
    public static function urlTepercaya($url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 600) return false;
        if (preg_match('/[\s\x00-\x1F"<>\\\\]/', $url)) return false;
        $u = parse_url($url);
        if (! $u || ! in_array(strtolower((string) ($u['scheme'] ?? '')), ['http', 'https'], true) || isset($u['user']) || isset($u['pass'])) return false;
        return self::hostTepercaya((string) ($u['host'] ?? ''));
    }

    /** Kunci pembanding URL: host (tanpa www) + path (tanpa garis miring akhir); query/fragmen diabaikan. */
    public static function kunciUrl(string $url): string
    {
        $u = parse_url($url);
        if (! $u) return '';
        $h = preg_replace('/^www\./', '', strtolower((string) ($u['host'] ?? '')));
        return $h . rtrim((string) ($u['path'] ?? ''), '/');
    }

    /** Kode periode ketat dari riset: 2026-09-17 | 2026-09 | 2026-Q2 → [jenis, awal, akhir] atau null. */
    public static function parseKodePeriode(string $kode): ?array
    {
        $kode = trim($kode);
        if (preg_match('/^(20\d\d)-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $kode, $m)) {
            if (! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) return null;
            $a = mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);
            return ['jenis' => 'tanggal', 'awal' => $a, 'akhir' => $a + 86399];
        }
        if (preg_match('/^(20\d\d)-(0[1-9]|1[0-2])$/', $kode, $m)) {
            return ['jenis' => 'bulan', 'awal' => mktime(0, 0, 0, (int) $m[2], 1, (int) $m[1]),
                'akhir' => mktime(23, 59, 59, (int) $m[2] + 1, 0, (int) $m[1])];
        }
        if (preg_match('/^(20\d\d)-Q([1-4])$/', $kode, $m)) {
            $b = ((int) $m[2] - 1) * 3 + 1;
            return ['jenis' => 'triwulan', 'awal' => mktime(0, 0, 0, $b, 1, (int) $m[1]),
                'akhir' => mktime(23, 59, 59, $b + 3, 0, (int) $m[1])];
        }
        return null;
    }

    /** Akhir periode dari label bebas ("20 Mei 2026", "Apr 2026 (YoY, BPS)", "Q1 2026", "IDX • 16 Oct 2026 17:00 WIB"). */
    public static function akhirPeriodeLabel(string $label): ?int
    {
        $bln = implode('|', array_keys(self::BULAN));
        if (preg_match('/(\d{1,2})\s+(' . $bln . ')[a-z]*\.?\s+(20\d\d)/i', $label, $m)) {
            $b = self::BULAN[strtolower($m[2])];
            return checkdate($b, (int) $m[1], (int) $m[3]) ? mktime(23, 59, 59, $b, (int) $m[1], (int) $m[3]) : null;
        }
        if (preg_match('/(?:\bQ|triwulan\s+)([1-4]|I{1,3}|IV)\s*[-\/ ]?\s*(20\d\d)/i', $label, $m)) {
            $q = ctype_digit($m[1]) ? (int) $m[1] : ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4][strtoupper($m[1])];
            return mktime(23, 59, 59, $q * 3 + 1, 0, (int) $m[2]);
        }
        if (preg_match('/\b(' . $bln . ')[a-z]*\.?\s+(20\d\d)/i', $label, $m)) {
            return mktime(23, 59, 59, self::BULAN[strtolower($m[1])] + 1, 0, (int) $m[2]);
        }
        return null;
    }

    private static function labelKode(string $kode, array $per): string
    {
        $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        if ($per['jenis'] === 'tanggal') return self::tglId(date('Y-m-d', $per['awal']));
        if ($per['jenis'] === 'bulan') return $b[(int) date('n', $per['awal'])] . ' ' . date('Y', $per['awal']);
        return 'Q' . substr($kode, -1) . ' ' . substr($kode, 0, 4);
    }

    /** Angka dari JSON: int/float atau string numerik polos; selain itu null. */
    public static function angkaValid($v): ?float
    {
        if (is_int($v) || is_float($v)) return is_finite((float) $v) ? (float) $v : null;
        if (is_string($v) && preg_match('/^-?\d+(?:[.,]\d+)?$/', trim($v))) return (float) str_replace(',', '.', trim($v));
        return null;
    }

    /** Format Indonesia: 4,75 | 5,1 | 7.241,89 (pecahan tanpa nol berlebih, min. 1 desimal untuk persen). */
    private static function fmtAngka(float $v, bool $ribuan = false): string
    {
        $s = number_format($v, 2, ',', $ribuan ? '.' : '');
        if (! $ribuan) {
            $s = rtrim($s, '0');
            if (str_ends_with($s, ',')) $s .= '0';
        }
        return $s;
    }

    /** Apakah $v (± $tol) tertulis di $teks (bentuk Indonesia 7.241,89 maupun Inggris 7,241.89). */
    public static function angkaAda(float $v, string $teks, float $tol): bool
    {
        if (! preg_match_all('/\d[\d.,]*\d|\d/', $teks, $mm)) return false;
        foreach ($mm[0] as $tok) {
            foreach ([str_replace(',', '.', str_replace('.', '', $tok)), str_replace(',', '', $tok)] as $c) {
                if (is_numeric($c) && abs((float) $c - $v) <= $tol) return true;
            }
        }
        return false;
    }

    // ── Prompt ────────────────────────────────────────────────────────────

    public static function promptRiset(): string
    {
        return <<<TXT
Kamu adalah peneliti data ekonomi untuk tim manajemen dua mal di Balikpapan. Tugasmu: MENCARI di web angka TERBARU indikator ekonomi di bawah, dari sumber resmi atau berita tepercaya, lalu melaporkannya sebagai JSON.

ATURAN KETAT
1. Laporkan HANYA angka yang BENAR-BENAR kamu temukan pada halaman sumber hasil pencarian. Jangan mengisi dari ingatan, jangan menebak, jangan menghitung turunan.
2. Setiap indikator wajib punya: nilai, periode_kode (format mesin, lihat bawah), tanggal_rilis (YYYY-MM-DD, tanggal rilis/publikasi/pengumuman), sumber_nama, url (alamat halaman yang kamu baca; salin persis), dan kutipan (maks 150 karakter, SALINAN kata demi kata dari halaman yang memuat angkanya).
3. Utamakan sumber resmi (bi.go.id, bps.go.id, pertamina.com, idx.co.id, dst.) di atas berita. Bila berita dan sumber resmi berbeda, laporkan angka sumber resmi dan tulis perbedaannya di catatan_riset.
4. Bila sebuah indikator tidak ketemu, atau sumber-sumber saling bertentangan tanpa kejelasan, isi null dan jelaskan di catatan_riset. null jauh lebih baik daripada angka yang salah.
5. Ambil rilis TERBARU yang tersedia, bukan yang lama. Jangan melaporkan periode yang belum terjadi.
6. Hemat biaya: maks 8 pencarian, jalankan pencarian secara paralel dalam SATU langkah kode di awal, lalu pakai paling banyak 3 langkah tambahan; saat memeriksa isi halaman cetak hanya kalimat yang memuat angka (maks 300 karakter per hasil), jangan mencetak halaman panjang; satu pencarian boleh melayani beberapa indikator (mis. berita ekonomi terkini memuat BI-Rate, inflasi, IHSG). Berhenti mencari begitu semua indikator terisi atau terbukti tak ada. Setelah selesai, balas dengan SATU objek JSON valid saja di teks akhir, tanpa markdown, tanpa narasi.

INDIKATOR DAN FORMAT periode_kode
- bi_rate: BI-Rate (suku bunga acuan BI, persen) terbaru; periode_kode = tanggal pengumuman hasil RDG, "YYYY-MM-DD".
- inflasi: inflasi nasional tahunan (year-on-year, persen) rilis BPS terbaru; periode_kode = bulan rujukan, "YYYY-MM".
- pdb: pertumbuhan PDB nasional year-on-year (persen) triwulan terbaru (BPS); periode_kode = "YYYY-Qn" (n 1-4).
- pdrb: pertumbuhan ekonomi (PDRB, persen YoY) Kota Balikpapan triwulan terbaru; bila tidak ada, Kalimantan Timur. "lingkup" wajib: "balikpapan" atau "kaltim". periode_kode = "YYYY-Qn".
- ihsg: IHSG penutupan terakhir (poin, angka saja); periode_kode = tanggal penutupan "YYYY-MM-DD".
- bbm: harga BBM Pertamina per liter yang berlaku sekarang: Pertalite (subsidi), Pertamax, Pertamax Turbo, Dexlite, Pertamina Dex, dan produk lain bila tertera. Pakai harga wilayah Balikpapan/Kalimantan Timur bila ada. Bila sumber memuat beberapa blok wilayah, pilih blok yang menyebut Kalimantan Timur/Balikpapan dan tulis wilayahnya persis di "wilayah"; bila tidak ada, pakai blok wilayah yang tertera paling jelas dan sebutkan apa adanya. Jangan mengisi null hanya karena ada beberapa wilayah. periode_kode = tanggal harga berlaku "YYYY-MM-DD".
- berita: 5 sampai 8 headline berita ekonomi/ritel terbaru (maks 30 hari terakhir) yang relevan bagi mal di Balikpapan atau Kalimantan Timur: judul, sumber, tanggal (YYYY-MM-DD), url.

BENTUK JSON
{
  "indikator": {
    "bi_rate": {"nilai": 0.0, "periode_kode": "", "tanggal_rilis": "", "sumber_nama": "", "url": "", "kutipan": ""} atau null,
    "inflasi": {... sama ...} atau null,
    "pdb": {... sama ...} atau null,
    "pdrb": {... sama ..., "lingkup": "balikpapan|kaltim"} atau null,
    "ihsg": {... sama ...} atau null,
    "bbm": {"periode_kode": "", "tanggal_rilis": "", "wilayah": "", "produk": [{"nama": "", "harga": 0, "subsidi": false}], "sumber_nama": "", "url": "", "kutipan": ""} atau null
  },
  "berita": [{"judul": "", "sumber": "", "tanggal": "", "url": ""}],
  "catatan_riset": ["hal yang tidak ketemu, berbeda antar sumber, atau perlu diwaspadai"]
}
nilai berupa angka JSON (titik desimal), tanpa satuan. harga BBM bilangan bulat rupiah per liter.
TXT;
    }

    private static function pesanRiset(): string
    {
        return 'Hari ini ' . self::HARI[(int) date('w')] . ', ' . date('Y-m-d') . ' (WITA). Cari data terbaru sesuai aturan dan balas dengan JSON.';
    }

    // ── Panggilan API ─────────────────────────────────────────────────────

    /**
     * Satu POST ke /v1/messages dengan satu kali ulang untuk galat sementara.
     *
     * @return array{code:int, data:?array, body:string}
     */
    private static function kirim(array $p, array $body, int $timeout): array
    {
        $coba = 0;
        while (true) {
            try {
                if (self::$transport !== null) {
                    $r = (self::$transport)($body);
                    $code = (int) $r['code'];
                    $raw = (string) $r['body'];
                } else {
                    $client = \Config\Services::curlrequest(['timeout' => $timeout, 'connect_timeout' => 10, 'http_errors' => false]);
                    $resp = $client->post($p['base'] . '/v1/messages', [
                        'headers' => ['x-api-key' => $p['key'], 'anthropic-version' => '2023-06-01', 'Content-Type' => 'application/json'],
                        'json'    => $body,
                    ]);
                    $code = $resp->getStatusCode();
                    $raw = (string) $resp->getBody();
                }
            } catch (\Throwable $e) {
                self::$galatJaringan = get_class($e) . ': ' . mb_substr(preg_replace('/sk-[A-Za-z0-9_-]+/', '<kunci>', $e->getMessage()), 0, 160);
                log_message('error', '[AiEkonomi] riset jaringan: ' . self::$galatJaringan);
                $code = 0;
                $raw = '';
            }
            if (($code === 0 || $code === 429 || $code >= 500) && $coba === 0) {
                $coba++;
                if (self::$jedaUlang > 0) sleep(self::$jedaUlang);
                continue;
            }
            $d = json_decode($raw, true);
            return ['code' => $code, 'data' => is_array($d) ? $d : null, 'body' => $raw];
        }
    }

    /**
     * Satu sesi riset dengan satu versi tool: request awal + lanjutan pause_turn (maks RISET_LANJUT).
     *
     * @return array{kode:string, pesan:string, teks:?string, urls:array, pencarian:int, tin:int, tout:int, galat_tool:array}
     *   kode: ok | dinonaktifkan | tool_ditolak | gagal
     */
    private static function putaranRiset(array $p, string $ver, string $sistem, string $user): array
    {
        $r = ['kode' => 'gagal', 'pesan' => '', 'teks' => null, 'urls' => [], 'pencarian' => 0, 'tin' => 0, 'tout' => 0, 'galat_tool' => [], 'putaran' => []];
        $tool = [
            'type' => $ver, 'name' => 'web_search', 'max_uses' => self::RISET_MAX_USES,
            'allowed_domains' => self::semuaDomain(),
            'user_location' => ['type' => 'approximate', 'country' => 'ID', 'timezone' => 'Asia/Makassar'],
        ];
        if ($ver === 'web_search_20260318' && self::HASIL_MENTAH_DIBUANG) $tool['response_inclusion'] = 'excluded';
        $pesan = [['role' => 'user', 'content' => $user]];

        for ($i = 0; $i <= self::RISET_LANJUT; $i++) {
            $x = self::kirim($p, [
                'model' => $p['model'], 'max_tokens' => self::RISET_MAX_TOKEN, 'thinking' => ['type' => 'between_tools'],
                'system' => $sistem, 'tools' => [$tool], 'messages' => $pesan,
            ], self::RISET_TIMEOUT);
            $d = $x['data'];
            $r['tin']  += (int) ($d['usage']['input_tokens'] ?? 0);
            $r['tout'] += (int) ($d['usage']['output_tokens'] ?? 0);
            $r['pencarian'] += (int) ($d['usage']['server_tool_use']['web_search_requests'] ?? 0);
            $r['putaran'][] = ['masuk' => (int) ($d['usage']['input_tokens'] ?? 0), 'keluar' => (int) ($d['usage']['output_tokens'] ?? 0),
                'cache_baca' => (int) ($d['usage']['cache_read_input_tokens'] ?? 0), 'cari' => (int) ($d['usage']['server_tool_use']['web_search_requests'] ?? 0),
                'stop' => (string) ($d['stop_reason'] ?? '?')];

            if ($x['code'] === 200 && $d === null) {
                $r['pesan'] = 'Respons API bukan JSON yang sah.';
                return $r;
            }
            if ($x['code'] !== 200 || $d === null) {
                $msg = (string) ($d['error']['message'] ?? '');
                log_message('error', "[AiEkonomi] riset HTTP {$x['code']} ({$ver}): " . mb_substr($msg !== '' ? $msg : $x['body'], 0, 240));
                if ($x['code'] === 400 && preg_match('/web.?search.*(not|isn.t).*enabled|not enabled.*web.?search/i', $msg)) {
                    $r['kode'] = 'dinonaktifkan';
                    $r['pesan'] = 'Web search dinonaktifkan untuk organisasi pada kunci API ini.';
                } elseif (in_array($x['code'], [400, 404], true)) {
                    $r['kode'] = 'tool_ditolak';
                    $r['pesan'] = "Permintaan ditolak (HTTP {$x['code']}) untuk tool {$ver}.";
                } else {
                    $r['pesan'] = "Layanan AI gagal pada riset (HTTP {$x['code']})." . ($x['code'] === 0 && self::$galatJaringan !== '' ? ' ' . self::$galatJaringan : '');
                }
                return $r;
            }

            $teksAkhir = null;
            foreach ((array) ($d['content'] ?? []) as $b) {
                $tipe = $b['type'] ?? '';
                if ($tipe === 'text') {
                    if (trim((string) ($b['text'] ?? '')) !== '') $teksAkhir = (string) $b['text'];
                    foreach ((array) ($b['citations'] ?? []) as $c) {
                        if (! empty($c['url']) && is_string($c['url'])) $r['urls'][self::kunciUrl($c['url'])] = true;
                    }
                } elseif ($tipe === 'web_search_tool_result') {
                    $isi = $b['content'] ?? null;
                    if (is_array($isi) && isset($isi['error_code'])) {
                        $r['galat_tool'][] = (string) $isi['error_code']; // objek galat, bukan daftar
                    } elseif (is_array($isi)) {
                        foreach ($isi as $it) {
                            if (is_array($it) && ! empty($it['url']) && is_string($it['url'])) $r['urls'][self::kunciUrl($it['url'])] = true;
                        }
                    }
                }
            }
            if (($d['stop_reason'] ?? '') === 'pause_turn') {
                // kirim ulang pesan assistant yang dijeda APA ADANYA (termasuk server_tool_use / hasil terenkripsi)
                $pesan[] = ['role' => 'assistant', 'content' => $d['content']];
                continue;
            }
            unset($r['urls']['']);
            if ($teksAkhir === null) {
                $r['pesan'] = 'Respons riset tanpa teks akhir (stop_reason=' . ($d['stop_reason'] ?? '?') . ').';
                return $r;
            }
            $r['kode'] = 'ok';
            $r['teks'] = $teksAkhir;
            return $r;
        }
        unset($r['urls']['']);
        $r['pesan'] = 'Riset terpotong: terlalu banyak lanjutan (pause_turn).';
        return $r;
    }

    /**
     * Jalankan riset web, validasi, dan simpan sebagai snapshot "web".
     *
     * @param array{coba?:bool} $opt coba = jangan tulis DB (hasil hanya di memori proses ini)
     * @return array{status:string, pesan:string, pencarian:int, token_masuk:int, token_keluar:int, tool:string, model:string,
     *   berhasil:array, gagal:array, berita:int, catatan_riset:array, galat_tool:array, ditulis:array, dipertahankan:array}
     *   status: ok | gagal | dinonaktifkan | tanpa_kunci
     */
    public static function riset(array $opt = []): array
    {
        $coba = ! empty($opt['coba']);
        $h = ['status' => 'gagal', 'pesan' => '', 'pencarian' => 0, 'token_masuk' => 0, 'token_keluar' => 0, 'tool' => '', 'model' => '',
            'berhasil' => [], 'gagal' => [], 'berita' => 0, 'catatan_riset' => [], 'galat_tool' => [], 'ditulis' => [], 'dipertahankan' => []];

        $p = self::penyedia();
        if ($p === null) {
            if (self::$transport === null) {
                $h['status'] = 'tanpa_kunci';
                $h['pesan'] = 'Kunci AI belum diatur; riset web dilewati.';
                return $h;
            }
            $p = ['base' => '', 'key' => 'uji', 'model' => self::MODEL_BAWAAN]; // stub uji
        }
        $h['model'] = $p['model'];

        $r = null;
        foreach (self::VERSI_TOOL as $ver) {
            $h['tool'] = $ver;
            $r = self::putaranRiset($p, $ver, self::promptRiset(), self::pesanRiset());
            $h['pencarian']    += $r['pencarian'];
            $h['token_masuk']  += $r['tin'];
            $h['token_keluar'] += $r['tout'];
            $h['galat_tool']    = array_merge($h['galat_tool'], $r['galat_tool']);
            $h['putaran']       = array_merge($h['putaran'] ?? [], $r['putaran']);
            if ($r['kode'] === 'tool_ditolak') {
                log_message('warning', "[AiEkonomi] tool {$ver} ditolak; mencoba versi berikutnya.");
                continue;
            }
            break;
        }
        if ($r['kode'] === 'dinonaktifkan') {
            $h['status'] = 'dinonaktifkan';
            $h['pesan'] = $r['pesan'] . ' Memakai jalur lama.';
            return $h;
        }
        if ($r['kode'] !== 'ok') {
            $h['pesan'] = $r['pesan'];
            return $h;
        }

        $json = self::parseJson((string) $r['teks']);
        if ($json === null) {
            $h['pesan'] = 'Keluaran riset bukan JSON yang sah.';
            return $h;
        }
        $v = self::validasiRiset($json, $r['urls']);
        $h['gagal'] = $v['gagal'];
        $h['catatan_riset'] = $v['catatan'];
        $h['berita'] = count($v['berita']);
        $h['berhasil'] = array_keys($v['ok']);
        if (! $v['ok'] && ! $v['berita']) {
            $h['pesan'] = 'Tidak ada indikator yang lolos validasi.';
            return $h;
        }

        $sim = self::simpanRiset($v['ok'], $v['berita'], $h, $coba);
        $h['ditulis'] = $sim['ditulis'];
        $h['dipertahankan'] = $sim['dipertahankan'];
        $h['status'] = 'ok';
        $h['pesan'] = 'Riset selesai: ' . count($h['berhasil']) . ' indikator lolos, ' . count($h['gagal']) . ' gagal, ' . $h['berita'] . ' headline.';
        log_message('info', '[AiEkonomi] riset ' . $h['pesan'] . " pencarian={$h['pencarian']} token={$h['token_masuk']}/{$h['token_keluar']} tool={$h['tool']}");
        return $h;
    }

    // ── Validasi ──────────────────────────────────────────────────────────

    /**
     * Validasi ketat keluaran riset (kode, bukan percaya AI).
     *
     * @param array $urlSah himpunan kunciUrl() dari sitasi/hasil pencarian respons
     * @return array{ok:array, gagal:array<string,string>, berita:array, catatan:array}
     */
    public static function validasiRiset(array $j, array $urlSah, ?int $now = null): array
    {
        $now ??= time();
        $ok = [];
        $gagal = [];
        $catatan = self::daftarTeks($j['catatan_riset'] ?? [], 0, 8, 300) ?? [];
        $ind = is_array($j['indikator'] ?? null) ? $j['indikator'] : [];
        $peta = ['bi_rate' => 'bi_rate', 'inflasi' => 'inflasi', 'pdb' => 'pdb', 'pdrb' => 'pdrb_bpn', 'ihsg' => 'ihsg', 'bbm' => 'bbm'];

        foreach ($peta as $kj => $id) {
            $e = $ind[$kj] ?? null;
            if (! is_array($e)) {
                $gagal[$id] = 'tidak ditemukan oleh riset';
                continue;
            }
            $alasan = '';
            $r = $id === 'bbm' ? self::validasiBbm($e, $urlSah, $now, $alasan) : self::validasiAngka($id, $e, $urlSah, $now, $alasan);
            if ($r === null) $gagal[$id] = $alasan; else $ok[$id] = $r;
        }

        $berita = [];
        foreach ((array) ($j['berita'] ?? []) as $b) {
            if (! is_array($b) || count($berita) >= 8) continue;
            $judul = self::bersih($b['judul'] ?? '', 220);
            $url = $b['url'] ?? '';
            $tgl = self::parseKodePeriode((string) ($b['tanggal'] ?? ''));
            if ($judul === '' || ! self::urlTepercaya($url) || ! isset($urlSah[self::kunciUrl($url)])) continue;
            if (! $tgl || $tgl['jenis'] !== 'tanggal' || $tgl['awal'] > $now || $tgl['awal'] < $now - 30 * 86400) continue;
            $berita[] = ['judul' => $judul, 'sumber' => self::bersih($b['sumber'] ?? '', 60) ?: self::namaSumber($url),
                'tanggal' => date('Y-m-d', $tgl['awal']), 'url' => $url];
        }
        if (isset($j['berita']) && is_array($j['berita']) && $j['berita'] && ! $berita) {
            $gagal['berita'] = 'semua headline ditolak (URL/tanggal tidak sah)';
        } elseif (! $berita) {
            $gagal['berita'] = 'tidak ada headline';
        }
        return ['ok' => $ok, 'gagal' => $gagal, 'berita' => $berita, 'catatan' => $catatan];
    }

    /** Cek bersama: url, kutipan, periode, tanggal rilis. Isi $alasan bila gagal. */
    private static function validasiUmum(string $id, array $e, array $urlSah, int $now, ?string &$alasan): ?array
    {
        $url = $e['url'] ?? '';
        if (! self::urlTepercaya($url)) { $alasan = 'URL bukan domain tepercaya'; return null; }
        if (! $urlSah) { $alasan = 'respons tanpa sitasi/hasil pencarian untuk memverifikasi URL'; return null; }
        if (! isset($urlSah[self::kunciUrl($url)])) { $alasan = 'URL tidak muncul di sitasi/hasil pencarian (diduga karangan)'; return null; }
        $kutipan = self::bersih($e['kutipan'] ?? '', 320);
        if ($kutipan === '') { $alasan = 'kutipan kosong'; return null; }

        $kode = (string) ($e['periode_kode'] ?? '');
        $per = self::parseKodePeriode($kode);
        if (! $per || $per['jenis'] !== self::JENIS_PERIODE[$id]) { $alasan = 'periode_kode tidak sah ("' . self::bersih($kode, 20) . '")'; return null; }
        $selesai = $per['jenis'] === 'tanggal' ? $per['awal'] : $per['akhir'];
        if ($selesai > $now) { $alasan = 'periode di masa depan'; return null; }
        if ($now - $per['akhir'] > self::UMUR_MAKS_PERIODE[$id] * 86400) { $alasan = 'periode terlalu lama untuk disebut terbaru'; return null; }

        $rilis = null;
        if (isset($e['tanggal_rilis']) && $e['tanggal_rilis'] !== null && $e['tanggal_rilis'] !== '') {
            $t = self::parseKodePeriode((string) $e['tanggal_rilis']);
            if (! $t || $t['jenis'] !== 'tanggal') { $alasan = 'tanggal_rilis tidak sah'; return null; }
            if ($t['awal'] > $now) { $alasan = 'tanggal_rilis di masa depan'; return null; }
            $rilis = date('Y-m-d', $t['awal']);
        }
        return ['url' => $url, 'kutipan' => $kutipan, 'kode' => $kode, 'per' => $per, 'rilis' => $rilis,
            'sumber_nama' => self::bersih($e['sumber_nama'] ?? '', 60) ?: self::namaSumber($url)];
    }

    private static function validasiAngka(string $id, array $e, array $urlSah, int $now, ?string &$alasan): ?array
    {
        $v = self::angkaValid($e['nilai'] ?? null);
        if ($v === null) { $alasan = 'nilai bukan angka'; return null; }
        [$min, $maks] = self::RENTANG[$id];
        if ($v < $min || $v > $maks) { $alasan = "nilai di luar rentang wajar ({$min}..{$maks})"; return null; }
        $u = self::validasiUmum($id, $e, $urlSah, $now, $alasan);
        if ($u === null) return null;
        if (! self::angkaAda($v, $u['kutipan'], $id === 'ihsg' ? 1.0 : 0.006)) { $alasan = 'kutipan tidak memuat angkanya'; return null; }

        $lingkup = null;
        if ($id === 'pdrb_bpn') {
            $lingkup = strtolower(trim((string) ($e['lingkup'] ?? '')));
            if (! in_array($lingkup, ['balikpapan', 'kaltim'], true)) { $alasan = 'lingkup PDRB tidak sah'; return null; }
        }
        $label = self::labelKode($u['kode'], $u['per']);
        $suffix = [
            'inflasi' => ' (YoY, BPS)', 'pdb' => ' (YoY, BPS)',
            'pdrb_bpn' => $lingkup === 'kaltim' ? ' (YoY, BPS Kalimantan Timur)' : ' (YoY, BPS Balikpapan)',
            'bi_rate' => '', 'ihsg' => ' (penutupan)',
        ][$id];
        $out = [
            'nilai' => self::fmtAngka($v, $id === 'ihsg'), 'nilai_angka' => (string) round($v, 4), 'periode' => $label . $suffix, 'periode_kode' => $u['kode'],
            'tanggal_rilis' => $u['rilis'], 'url' => $u['url'], 'kutipan' => mb_substr($u['kutipan'], 0, 150), 'sumber_nama' => $u['sumber_nama'],
        ];
        if ($lingkup !== null) $out['lingkup'] = $lingkup;
        return $out;
    }

    private static function validasiBbm(array $e, array $urlSah, int $now, ?string &$alasan): ?array
    {
        $u = self::validasiUmum('bbm', $e, $urlSah, $now, $alasan);
        if ($u === null) return null;
        $wilayah = self::bersih($e['wilayah'] ?? '', 80);
        if ($wilayah === '') { $alasan = 'wilayah harga tidak disebut'; return null; }

        $daftar = [];
        $semua = [];
        foreach ((array) ($e['produk'] ?? []) as $b) {
            if (! is_array($b)) continue;
            $nama = self::bersih($b['nama'] ?? '', 40);
            $h = self::angkaValid($b['harga'] ?? null);
            if ($nama === '' || ! preg_match('/pertalite|pertamax|dexlite|dex|solar/i', $nama) || $h === null) continue;
            if ($h != floor($h) || $h < self::RENTANG['bbm'][0] || $h > self::RENTANG['bbm'][1]) continue;
            $kunciNama = mb_strtolower($nama);
            if (isset($semua[$kunciNama])) continue;
            $semua[$kunciNama] = true;
            $sub = isset($b['subsidi']) ? (bool) $b['subsidi'] : (bool) preg_match('/pertalite|solar/i', $nama) && ! preg_match('/non/i', $nama);
            $daftar[] = ['nama' => $nama, 'harga_rp_per_liter' => (int) $h, 'subsidi' => $sub];
        }
        if (count($daftar) < 2) { $alasan = 'kurang dari 2 produk BBM berharga sah'; return null; }
        $ada = false;
        foreach ($daftar as $d) {
            if (self::angkaAda((float) $d['harga_rp_per_liter'], $u['kutipan'], 0.5)) { $ada = true; break; }
        }
        if (! $ada) { $alasan = 'kutipan tidak memuat satu pun harga'; return null; }
        return [
            'daftar' => $daftar, 'periode' => self::labelKode($u['kode'], $u['per']), 'periode_kode' => $u['kode'], 'wilayah' => $wilayah,
            'tanggal_rilis' => $u['rilis'], 'url' => $u['url'], 'kutipan' => mb_substr($u['kutipan'], 0, 150), 'sumber_nama' => $u['sumber_nama'],
        ];
    }

    // ── Penyimpanan snapshot riset ────────────────────────────────────────

    /** Snapshot riset terakhir untuk $id (memori --coba lebih dulu, lalu DB), atau null. */
    public static function ambilWeb(string $id): ?array
    {
        if (isset(self::$memWeb[$id])) return self::$memWeb[$id];
        $s = self::ambilSnapshot('web_' . $id);
        return ($s && $s['sumber'] === 'web') ? ['payload' => $s['payload'], 'diambil_pada' => $s['diambil_pada']] : null;
    }

    /**
     * Tulis hasil lolos: hanya bila periode LEBIH BARU daripada snapshot riset sebelumnya (atau belum ada);
     * periode sama + nilai sama → hanya segarkan waktu cek. Tidak pernah menyentuh economic_indicators.
     *
     * @return array{ditulis:array, dipertahankan:array<string,string>}
     */
    private static function simpanRiset(array $ok, array $berita, array $h, bool $coba): array
    {
        $ditulis = [];
        $tahan = [];
        $sekarang = date('Y-m-d H:i:s');
        $tulis = static function (string $id, array $payload) use ($coba, $sekarang) {
            if ($coba) {
                self::$memWeb[$id] = ['payload' => $payload, 'diambil_pada' => $sekarang];
            } else {
                self::simpanSnapshot('web_' . $id, $payload, 'web', $sekarang);
            }
        };
        foreach ($ok as $id => $payload) {
            $lama = $coba ? null : self::ambilWeb($id);
            if ($lama) {
                $a = self::parseKodePeriode((string) ($lama['payload']['periode_kode'] ?? ''));
                $b = self::parseKodePeriode((string) $payload['periode_kode']);
                if ($a && $b && $b['akhir'] < $a['akhir']) { $tahan[$id] = 'periode lebih lama dari snapshot riset'; continue; }
                if ($a && $b && $b['akhir'] === $a['akhir'] && json_encode(self::inti($lama['payload'])) !== json_encode(self::inti($payload))) {
                    $tahan[$id] = 'periode sama, nilai berbeda dari riset sebelumnya; snapshot lama dipertahankan';
                    continue;
                }
            }
            $tulis($id, $payload);
            $ditulis[] = $id;
        }
        if ($berita) {
            $tulis('berita', ['daftar' => $berita]);
            $ditulis[] = 'berita';
        }
        if ($ditulis) {
            $tulis('meta', [
                'dicek_pada' => $sekarang, 'pencarian' => $h['pencarian'], 'token_masuk' => $h['token_masuk'], 'token_keluar' => $h['token_keluar'],
                'tool' => $h['tool'], 'model' => $h['model'], 'berhasil' => $h['berhasil'], 'gagal' => $h['gagal'], 'catatan' => $h['catatan_riset'],
            ]);
        }
        return ['ditulis' => $ditulis, 'dipertahankan' => $tahan];
    }

    private static function inti(array $p): array
    {
        return ['n' => $p['nilai_angka'] ?? ($p['daftar'] ?? null), 'k' => $p['periode_kode'] ?? null];
    }

    // ── Penggabungan ke fakta ─────────────────────────────────────────────

    /** Entri fakta dari snapshot riset (tanpa kolom mesin). */
    private static function entriWeb(array $w): array
    {
        $e = $w['payload'];
        unset($e['nilai_angka'], $e['periode_kode']);
        $e['sumber'] = 'riset_web';
        $e['diambil_pada'] = $w['diambil_pada'];
        return $e;
    }

    /**
     * Prioritas: (1) pengambilan langsung yang berhasil, (2) riset web bila periodenya sama/lebih baru,
     * (3) nilai DB admin, (4) snapshot lama (ditangani selesaikan()). Perbedaan nilai pada periode yang sama
     * dicatat. Tidak menulis apa pun ke DB.
     */
    private static function gabungWeb(string $id, ?array $entri, array &$catatan): ?array
    {
        $w = self::ambilWeb($id);
        if ($w === null) return $entri;
        $web = self::entriWeb($w);
        if ($entri === null) return $web;

        $label = self::LABEL[$id] ?? $id;
        $akhirWeb = self::akhirPeriodeLabel((string) ($web['periode'] ?? ''));
        $akhirLain = self::akhirPeriodeLabel((string) ($entri['periode'] ?? ''));
        if ($akhirLain === null && isset($entri['diambil_pada'])) $akhirLain = strtotime((string) $entri['diambil_pada']) ?: null;
        $sama = $akhirWeb !== null && $akhirLain !== null && abs($akhirWeb - $akhirLain) < 86400 * 2;

        if ($sama) {
            $beda = false;
            if ($id === 'bbm') {
                $peta = [];
                foreach ((array) ($entri['daftar'] ?? []) as $b) $peta[mb_strtolower((string) $b['nama'])] = (int) $b['harga_rp_per_liter'];
                foreach ((array) ($web['daftar'] ?? []) as $b) {
                    $k = mb_strtolower((string) $b['nama']);
                    if (isset($peta[$k]) && $peta[$k] !== (int) $b['harga_rp_per_liter']) $beda = true;
                }
            } else {
                $a = self::angkaValid(str_replace(['.', ','], ['', '.'], (string) $entri['nilai']));
                $b2 = self::angkaValid(str_replace(['.', ','], ['', '.'], (string) $web['nilai']));
                $beda = $a !== null && $b2 !== null && abs($a - $b2) > ($id === 'ihsg' ? 1.0 : 0.05);
            }
            if ($beda) {
                $catatan[] = "{$label}: dua sumber berbeda untuk periode yang sama — {$entri['sumber']} " . self::ringkasNilai($entri)
                    . " vs riset web " . self::ringkasNilai($web) . " ({$web['sumber_nama']}).";
            }
        }

        if (($entri['sumber'] ?? '') === 'langsung') return $entri;                         // (1)
        if ($akhirWeb !== null && ($akhirLain === null || $akhirWeb >= $akhirLain)) return $web; // (2)
        return $entri;                                                                       // (3)
    }

    private static function ringkasNilai(array $e): string
    {
        if (isset($e['daftar'])) {
            return implode(', ', array_map(fn($b) => $b['nama'] . ' Rp ' . number_format((int) $b['harga_rp_per_liter'], 0, ',', '.'), array_slice($e['daftar'], 0, 4)));
        }
        return (string) ($e['nilai'] ?? '?');
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Penyedia & panggilan Claude
    // ═════════════════════════════════════════════════════════════════════

    /** @return array{base:string, key:string, model:string}|null */
    public static function penyedia(): ?array
    {
        $model = trim((string) env('aiekonomi.model')) ?: self::MODEL_BAWAAN;
        $key   = trim((string) env('aiekonomi.key'));
        if ($key !== '') {
            return ['base' => rtrim((string) (env('aiekonomi.base_url') ?: 'https://api.anthropic.com'), '/'), 'key' => $key, 'model' => $model];
        }
        for ($i = 1; $i <= 9; $i++) {
            $key = trim((string) env("aiklas.p{$i}_key"));
            if ($key === '' || strtolower((string) env("aiklas.p{$i}_tipe")) !== 'anthropic') continue;
            $base = rtrim((string) env("aiklas.p{$i}_base_url"), '/');
            return ['base' => $base !== '' ? $base : 'https://api.anthropic.com', 'key' => $key, 'model' => $model];
        }
        return null;
    }

    public static function promptSistem(): string
    {
        $sektor = implode(' | ', self::SEKTOR);
        $kelas  = implode(' | ', self::KELAS);
        $prosp  = implode(' | ', self::PROSPEKTIF);
        return <<<TXT
Kamu adalah analis ekonomi ritel untuk dua mal di Balikpapan (E-Walk dan Pentacity), kota penyangga IKN Nusantara. Tugasmu: menulis analisis harian singkat bagi tim manajemen mal (leasing, event & promosi) dari data yang diberikan.

ATURAN KETAT
1. Pakai HANYA angka dan berita yang ada pada data JSON pengguna. Jangan mengarang angka, tanggal, persentase, nama berita, atau fakta lain. Bila angka tidak ada di data, jangan menyebut angkanya.
2. Setiap angka yang kamu tulis harus persis seperti pada data (mis. "4,75%", "Rp 12.900"). Jangan menghitung angka turunan baru.
3. Sebut dengan jelas data yang hilang atau basi (lihat "data_hilang", "catatan_otomatis", "sumber", "umur_hari", "basi"). Untuk indikator bersumber "snapshot", tulis umur datanya dalam bentuk "data per <tanggal>". Bila sebuah indikator hilang, jangan menebak nilainya dan turunkan keyakinan kesimpulan yang bergantung padanya.
3b. Indikator bersumber "riset_web" adalah hasil riset mingguan dari sumber resmi/berita: sebut periode rilis terbarunya ("rilis terbaru: <periode>") dan jangan menyebutnya basi hanya karena periodenya bulan/triwulan lalu bila itu rilis terkini. "berita_riset" boleh dipakai sebagai konteks tambahan seperti berita lain.
4. Penyebab hanyalah dugaan ("kemungkinan", "berpotensi"); ini bukan prediksi pasti. Jangan memberi saran investasi atau jual-beli saham.
5. Jangan menyebut nama orang, nama tenant, atau data internal mal. Berita boleh dirujuk lewat topiknya, bukan disalin panjang.
6. Bahasa Indonesia, ringkas, konkret, praktis untuk operasional mal. Maksimal sekitar 220 karakter per kalimat/butir.
7. Balas HANYA dengan satu objek JSON valid, tanpa teks lain, tanpa markdown, tanpa komentar.

BENTUK JSON (semua kunci wajib ada)
{
  "ringkasan": ["2 sampai 4 kalimat ringkasan kondisi ekonomi dan artinya bagi mal"],
  "catatan_data": ["data hilang / basi / ketidakpastian; array kosong bila tidak ada"],
  "sektor": [ {"nama": "<nama>", "arah": "naik|stabil|turun", "alasan": "satu kalimat"} ],
  "kelas": [ {"nama": "<nama>", "status": "membaik|terjaga|waspada|tertekan", "poin": ["tepat 3 butir singkat"], "belanja": "perkiraan pos belanja utama kelas ini"} ],
  "prospektif": [ {"nama": "<nama>", "skor": 0, "alasan": ["2 sampai 3 butir"], "peluang": "satu kalimat peluang bagi tenant/event"} ],
  "implikasi_mal": ["3 sampai 5 butir praktis untuk leasing, event, dan promosi E-Walk & Pentacity"]
}

DAFTAR NAMA (gunakan PERSIS, masing-masing tepat satu kali, urutan bebas)
- sektor: {$sektor}
- kelas: {$kelas}
- prospektif: {$prosp}

PETUNJUK
- "arah" sektor: naik = prospek menguat, stabil = tetap, turun = melemah.
- "status" kelas: membaik / terjaga / waspada / tertekan menurut daya beli kelas itu (BBM yang dipakai kelasnya, inflasi, suku bunga, pertumbuhan, IHSG untuk kelas atas). Kelas Bawah memakai Pertalite, Kelas Menengah Pertamax, Kelas Atas Pertamax Turbo, UMKM & Pelaku Usaha solar non-subsidi (Dexlite/Pertamina Dex) bila datanya ada.
- "skor" prospektif: bilangan bulat 0 sampai 3 (3 = sangat prospektif, 2 = prospektif, 1 = moderat, 0 = perlu perhatian), dinilai dari kemampuan belanja segmen itu berdasarkan data.
- Gunakan berita hanya sebagai konteks tambahan dan sebutkan topiknya secara umum; jangan menyimpulkan peristiwa yang tidak tertulis di judul.
TXT;
    }

    /**
     * Panggil Claude dan validasi keluaran.
     *
     * @return array{ok:bool, hasil?:array, galat?:string, model?:string, token_masuk?:int, token_keluar?:int}
     */
    public static function tulis(array $fakta): array
    {
        $p = self::penyedia();
        if ($p === null) return ['ok' => false, 'galat' => 'Kunci AI belum diatur.'];

        $user = "Data hari ini (JSON). Tulis analisis sesuai aturan.\n\n"
            . json_encode($fakta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $tin = $tout = 0;
        $teks = null;
        $galat = 'Panggilan AI gagal.';
        for ($coba = 0; $coba < 2 && $teks === null; $coba++) {
            if ($coba === 1) sleep(4); // satu kali ulang untuk galat sementara (5xx/429/jaringan)
            $r = self::httpAnthropic($p, self::promptSistem(), $user);
            $tin  += $r['token_masuk'];
            $tout += $r['token_keluar'];
            if ($r['teks'] !== null) {
                $teks = $r['teks'];
            } else {
                $galat = $r['galat'];
                if (! $r['ulang']) break;
            }
        }
        if ($teks === null) {
            return ['ok' => false, 'galat' => $galat, 'token_masuk' => $tin, 'token_keluar' => $tout];
        }

        $json = self::parseJson($teks);
        $hasil = $json !== null ? self::normalisasi($json, $err) : null;
        if ($hasil === null) {
            log_message('error', '[AiEkonomi] keluaran AI tidak valid: ' . ($json === null ? 'bukan JSON' : $err));
            return ['ok' => false, 'galat' => 'Keluaran AI tidak sesuai format; memakai aturan cadangan.', 'token_masuk' => $tin, 'token_keluar' => $tout];
        }
        return ['ok' => true, 'hasil' => $hasil, 'model' => $p['model'], 'token_masuk' => $tin, 'token_keluar' => $tout];
    }

    /**
     * @return array{teks:?string, galat:string, ulang:bool, token_masuk:int, token_keluar:int}
     */
    private static function httpAnthropic(array $p, string $sistem, string $pengguna): array
    {
        $kosong = ['teks' => null, 'galat' => 'Panggilan AI gagal.', 'ulang' => false, 'token_masuk' => 0, 'token_keluar' => 0];
        try {
            $client = \Config\Services::curlrequest([
                'timeout'         => self::TIMEOUT,
                'connect_timeout' => 10,
                'http_errors'     => false,
            ]);
            $resp = $client->post($p['base'] . '/v1/messages', [
                'headers' => [
                    'x-api-key'         => $p['key'],
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ],
                // Sonnet 5.5 menulis blok `thinking` lebih dulu → max_tokens longgar;
                // tanpa `temperature` (ditolak model terbaru).
                'json' => [
                    'model'      => $p['model'],
                    'max_tokens' => self::MAX_TOKENS,
                    'thinking'   => ['type' => 'between_tools'],
                    'system'     => $sistem,
                    'messages'   => [['role' => 'user', 'content' => $pengguna]],
                ],
            ]);
            $code = $resp->getStatusCode();
            $body = (string) $resp->getBody();
            $data = json_decode($body, true);
            $tin  = (int) ($data['usage']['input_tokens'] ?? 0);
            $tout = (int) ($data['usage']['output_tokens'] ?? 0);
            if ($code !== 200) {
                // Isi galat hanya ke log (tanpa kunci), tidak ke layar.
                log_message('error', "[AiEkonomi] Anthropic HTTP {$code}: " . mb_substr($body, 0, 300));
                return ['teks' => null, 'galat' => "Layanan AI menolak permintaan (HTTP {$code}).", 'ulang' => $code === 429 || $code >= 500,
                    'token_masuk' => $tin, 'token_keluar' => $tout];
            }
            foreach (($data['content'] ?? []) as $blok) {
                if (($blok['type'] ?? '') === 'text' && trim((string) ($blok['text'] ?? '')) !== '') {
                    return ['teks' => (string) $blok['text'], 'galat' => '', 'ulang' => false, 'token_masuk' => $tin, 'token_keluar' => $tout];
                }
            }
            log_message('error', '[AiEkonomi] respons tanpa blok teks (stop_reason=' . ($data['stop_reason'] ?? '?') . ')');
            return ['teks' => null, 'galat' => 'Respons AI kosong.', 'ulang' => false, 'token_masuk' => $tin, 'token_keluar' => $tout];
        } catch (\Throwable $e) {
            log_message('error', '[AiEkonomi] panggilan AI error: ' . get_class($e) . ' ' . mb_substr($e->getMessage(), 0, 200));
            return ['teks' => null, 'galat' => 'Layanan AI tidak dapat dihubungi.', 'ulang' => true, 'token_masuk' => 0, 'token_keluar' => 0] + $kosong;
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Parsing & validasi
    // ═════════════════════════════════════════════════════════════════════

    public static function parseJson(string $teks): ?array
    {
        $t = trim($teks);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```$/', '', (string) $t);
        $j = json_decode((string) $t, true);
        if (is_array($j)) return $j;
        $a = strpos((string) $t, '{');
        $b = strrpos((string) $t, '}');
        if ($a !== false && $b !== false && $b > $a) {
            $j = json_decode(substr((string) $t, $a, $b - $a + 1), true);
            if (is_array($j)) return $j;
        }
        return null;
    }

    private static function bersih($v, int $maks = 400): string
    {
        $s = is_scalar($v) ? (string) $v : '';
        $s = strip_tags($s);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? '';
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        return mb_strlen($s) > $maks ? rtrim(mb_substr($s, 0, $maks - 1)) . '…' : $s;
    }

    /** @return string[] */
    private static function daftarTeks($v, int $min, int $maks, int $panjang = 400): ?array
    {
        if (! is_array($v)) return null;
        $o = [];
        foreach ($v as $x) {
            $s = self::bersih($x, $panjang);
            if ($s !== '') $o[] = $s;
        }
        if (count($o) < $min) return null;
        return array_slice($o, 0, $maks);
    }

    /**
     * Validasi ketat; kembalikan null (dan isi $err) bila tidak sah.
     */
    public static function normalisasi(array $j, ?string &$err = null): ?array
    {
        $err = '';
        $ring = self::daftarTeks($j['ringkasan'] ?? null, 2, 4, 500);
        if ($ring === null) { $err = 'ringkasan'; return null; }
        $cat = self::daftarTeks($j['catatan_data'] ?? [], 0, 8, 400);
        if ($cat === null) { $err = 'catatan_data'; return null; }
        $imp = self::daftarTeks($j['implikasi_mal'] ?? null, 3, 5, 400);
        if ($imp === null) { $err = 'implikasi_mal'; return null; }

        $peta = static function ($daftar) {
            $m = [];
            foreach ((array) $daftar as $n) $m[mb_strtolower($n)] = $n;
            return $m;
        };

        // sektor
        $sek = [];
        $pm = $peta(self::SEKTOR);
        foreach ((array) ($j['sektor'] ?? []) as $x) {
            $k = mb_strtolower(trim((string) ($x['nama'] ?? '')));
            $arah = strtolower(trim((string) ($x['arah'] ?? '')));
            if (! isset($pm[$k]) || ! in_array($arah, self::ARAH, true)) { $err = 'sektor:' . $k; return null; }
            $al = self::bersih($x['alasan'] ?? '', 350);
            if ($al === '') { $err = 'sektor alasan'; return null; }
            $sek[$pm[$k]] = ['nama' => $pm[$k], 'arah' => $arah, 'alasan' => $al];
        }
        if (count($sek) !== count(self::SEKTOR)) { $err = 'sektor tidak lengkap'; return null; }

        // kelas
        $kel = [];
        $pm = $peta(self::KELAS);
        foreach ((array) ($j['kelas'] ?? []) as $x) {
            $k = mb_strtolower(trim((string) ($x['nama'] ?? '')));
            $st = strtolower(trim((string) ($x['status'] ?? '')));
            if (! isset($pm[$k]) || ! in_array($st, self::STATUS, true)) { $err = 'kelas:' . $k; return null; }
            $poin = self::daftarTeks($x['poin'] ?? null, 2, 4, 300);
            $bel  = self::bersih($x['belanja'] ?? '', 250);
            if ($poin === null || $bel === '') { $err = 'kelas isi'; return null; }
            $kel[$pm[$k]] = ['nama' => $pm[$k], 'status' => $st, 'poin' => $poin, 'belanja' => $bel];
        }
        if (count($kel) !== count(self::KELAS)) { $err = 'kelas tidak lengkap'; return null; }

        // prospektif
        $pro = [];
        $pm = $peta(self::PROSPEKTIF);
        foreach ((array) ($j['prospektif'] ?? []) as $x) {
            $k = mb_strtolower(trim((string) ($x['nama'] ?? '')));
            $sk = $x['skor'] ?? null;
            if (is_string($sk) && preg_match('/^\d+$/', trim($sk))) $sk = (int) $sk;
            if (is_float($sk) && floor($sk) === $sk) $sk = (int) $sk;
            if (! isset($pm[$k]) || ! is_int($sk) || $sk < 0 || $sk > 3) { $err = 'prospektif:' . $k; return null; }
            $al = self::daftarTeks($x['alasan'] ?? null, 1, 4, 300);
            $pe = self::bersih($x['peluang'] ?? '', 300);
            if ($al === null || $pe === '') { $err = 'prospektif isi'; return null; }
            $pro[$pm[$k]] = ['nama' => $pm[$k], 'skor' => $sk, 'alasan' => $al, 'peluang' => $pe];
        }
        if (count($pro) !== count(self::PROSPEKTIF)) { $err = 'prospektif tidak lengkap'; return null; }

        // urutan kanonik
        return [
            'ringkasan'     => $ring,
            'catatan_data'  => $cat,
            'sektor'        => array_values(array_map(fn($n) => $sek[$n], self::SEKTOR)),
            'kelas'         => array_values(array_map(fn($n) => $kel[$n], self::KELAS)),
            'prospektif'    => array_values(array_map(fn($n) => $pro[$n], self::PROSPEKTIF)),
            'implikasi_mal' => $imp,
        ];
    }

    /**
     * Pastikan setiap data hilang/snapshot/basi tercatat di catatan_data,
     * walau AI lupa menyebutnya (catatan otomatis ditambahkan).
     */
    public static function lengkapiCatatan(array $hasil, array $fakta): array
    {
        $gabung = mb_strtolower(implode(' | ', $hasil['catatan_data']));
        foreach ((array) ($fakta['catatan_otomatis'] ?? []) as $c) {
            $label = mb_strtolower(trim(explode(':', $c, 2)[0]));
            if ($label !== '' && ! str_contains($gabung, $label)) {
                $hasil['catatan_data'][] = $c;
            }
        }
        return $hasil;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  Penyimpanan
    // ═════════════════════════════════════════════════════════════════════

    public static function simpan(array $fakta, string $hash, array $hasil, array $w, ?int $oleh): int
    {
        $db = db_connect();
        $db->table('ai_ekonomi')->insert([
            'tanggal'      => date('Y-m-d'),
            'penyedia'     => 'anthropic',
            'model'        => (string) ($w['model'] ?? ''),
            'fakta_hash'   => $hash,
            'hasil'        => json_encode($hasil, JSON_UNESCAPED_UNICODE),
            'fakta'        => json_encode($fakta, JSON_UNESCAPED_UNICODE),
            'token_masuk'  => (int) ($w['token_masuk'] ?? 0),
            'token_keluar' => (int) ($w['token_keluar'] ?? 0),
            'dibuat_oleh'  => $oleh,
            'dibuat_pada'  => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    private static function barisTerakhirHariIni(): ?array
    {
        try {
            return db_connect()->table('ai_ekonomi')->where('tanggal', date('Y-m-d'))->orderBy('id', 'DESC')->get(1)->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Baris terbaru untuk tampilan (hasil & fakta sudah di-decode), atau null.
     *
     * @return array{id:int, model:string, dibuat_pada:string, umur_jam:int, hasil:array, fakta:array}|null
     */
    public static function terbaru(): ?array
    {
        try {
            $r = db_connect()->table('ai_ekonomi')->orderBy('id', 'DESC')->get(1)->getRowArray();
        } catch (\Throwable $e) {
            return null; // tabel belum dimigrasi → aturan cadangan
        }
        if (! $r) return null;
        $hasil = json_decode((string) $r['hasil'], true);
        $fakta = json_decode((string) $r['fakta'], true);
        if (! is_array($hasil) || self::normalisasi($hasil) === null) return null;
        return [
            'id'          => (int) $r['id'],
            'model'       => (string) $r['model'],
            'dibuat_pada' => (string) $r['dibuat_pada'],
            'umur_jam'    => (int) floor((time() - strtotime((string) $r['dibuat_pada'])) / 3600),
            'hasil'       => $hasil,
            'fakta'       => is_array($fakta) ? $fakta : [],
            'riset'       => self::metaRiset(),
        ];
    }

    /**
     * Info riset web terakhir untuk tampilan: waktu cek, jadwal berikutnya, hasil.
     *
     * @return array{dicek_pada:string, dicek_ts:int, berikutnya_ts:int, berhasil:array, gagal:array, pencarian:int}|null
     */
    public static function metaRiset(): ?array
    {
        $m = self::ambilWeb('meta');
        if (! $m) return null;
        $ts = strtotime((string) $m['diambil_pada']) ?: 0;
        return [
            'dicek_pada' => (string) $m['diambil_pada'], 'dicek_ts' => $ts, 'berikutnya_ts' => self::risetBerikutnya(),
            'berhasil' => (array) ($m['payload']['berhasil'] ?? []), 'gagal' => (array) ($m['payload']['gagal'] ?? []),
            'pencarian' => (int) ($m['payload']['pencarian'] ?? 0),
        ];
    }

    /** Nama engine untuk lencana, mis. "claude-sonnet-5-5" → "Claude Sonnet 5.5". */
    public static function namaEngine(string $model): string
    {
        if (preg_match('/^claude-([a-z]+)-(\d+)-(\d+)/i', $model, $m)) {
            return 'Claude ' . ucfirst(strtolower($m[1])) . ' ' . $m[2] . '.' . $m[3];
        }
        return $model !== '' ? $model : 'Claude';
    }
}
