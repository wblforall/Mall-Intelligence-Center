<?php

namespace App\Libraries;

/**
 * Indeks Standar Pencemar Udara (ISPU) Balikpapan — data RESMI KLHK/BPLH.
 *
 * Sumber: https://ispu.kemenlh.go.id (SISPU), endpoint publik
 * /apimobile/v1/getStations — daftar semua stasiun dengan nilai sub-indeks ISPU
 * per parameter (t_*), nilai ISPU stasiun (`val`), parameter kritis (`param`),
 * dan kategori resmi (`cat`/`kategori`). Tidak ada perhitungan sendiri di sini:
 * semua angka berasal dari sumber resmi; kelas ini hanya memilih stasiun
 * Balikpapan, merapikan bentuknya, menyesuaikan zona waktu ke WITA, dan cache.
 *
 * Kategori ISPU (Permen LHK P.14/2020): 1–50 Baik, 51–100 Sedang,
 * 101–200 Tidak Sehat, 201–300 Sangat Tidak Sehat, ≥301 Berbahaya.
 */
final class Ispu
{
    private const URL        = 'https://ispu.kemenlh.go.id/apimobile/v1/getStations';
    private const TTL_SEGAR  = 600;      // 10 menit
    private const TTL_CADANGAN = 86400;  // salinan terakhir yang berhasil, dipakai bila sumber gagal
    private const KUNCI_SEGAR  = 'ispu_balikpapan';
    private const KUNCI_CADANGAN = 'ispu_balikpapan_terakhir';

    /** Rekaman riwayat MIC (sumber resmi hanya memberi nilai terbaru, tanpa riwayat). */
    private const KUNCI_RIWAYAT = 'ispu_riwayat';
    private const TTL_RIWAYAT   = 172800; // 48 jam
    private const MAKS_TITIK    = 96;
    private const TREN_AMBANG   = 5;      // selisih sub-indeks (poin) yang dianggap naik/turun

    /** ID stasiun Balikpapan di SISPU (urutan = urutan tampil). */
    public const STASIUN = ['BALIKPAPAN', 'BALIKPAPAN_BB'];

    /**
     * Titik Kawasan BSB (Balikpapan Superblock: E-Walk & Pentacity) untuk ESTIMASI.
     * SISPU tidak punya stasiun di BSB, jadi ubin BSB dihitung (bukan diukur) dari
     * stasiun resmi terdekat. Ganti koordinat di sini bila titik acuan berubah.
     */
    public const BSB_LAT  = -1.272937;   // Plus Code PVG5+RQ Gn. Bahagia, Balikpapan (diberikan pemilik 18 Okt 2026)
    public const BSB_LNG  = 116.859437;
    public const BSB_ID   = 'BSB_ESTIMASI';
    public const BSB_NAMA = 'Kawasan BSB · estimasi';

    /** Koordinat cadangan stasiun (dipakai bila respons/cache lama tak memuat lat/lon). */
    private const KOORD_CADANGAN = [
        'BALIKPAPAN'    => [-1.24273, 116.89200],
        'BALIKPAPAN_BB' => [-1.24229, 116.85800],
    ];

    private const PARAM = [
        'pm10' => 'PM10', 'pm25' => 'PM2.5', 'so2' => 'SO₂', 'co' => 'CO',
        'o3' => 'O₃', 'no2' => 'NO₂', 'hc' => 'HC',
    ];

    /** Batas dan warna kategori resmi + saran kesehatan umum (ringkas). */
    public const KATEGORI = [
        ['maks' => 50,  'kode' => 'BAIK',               'label' => 'Baik',               'warna' => '#16a34a', 'saran' => 'Mutu udara sangat baik. Aman untuk semua aktivitas, termasuk di luar ruangan.'],
        ['maks' => 100, 'kode' => 'SEDANG',             'label' => 'Sedang',             'warna' => '#2563eb', 'saran' => 'Masih dapat diterima. Kelompok sensitif (asma, anak, lansia) sebaiknya membatasi aktivitas berat di luar ruangan.'],
        ['maks' => 200, 'kode' => 'TIDAK SEHAT',        'label' => 'Tidak Sehat',        'warna' => '#d97706', 'saran' => 'Dapat merugikan sebagian orang. Kurangi aktivitas berat di luar ruangan; kelompok sensitif lebih baik di dalam ruangan.'],
        ['maks' => 300, 'kode' => 'SANGAT TIDAK SEHAT', 'label' => 'Sangat Tidak Sehat', 'warna' => '#dc2626', 'saran' => 'Meningkatkan risiko kesehatan. Hindari aktivitas luar ruangan; pertimbangkan menunda kegiatan di area terbuka.'],
        ['maks' => PHP_INT_MAX, 'kode' => 'BERBAHAYA',  'label' => 'Berbahaya',          'warna' => '#111827', 'saran' => 'Berbahaya bagi kesehatan. Tetap di dalam ruangan dan batasi paparan.'],
    ];

    /**
     * @return array{ok:bool, stasiun:array, diambil_pada:?string, umur_menit:?int, basi:bool, galat:?string}
     */
    public static function ambil(bool $paksa = false): array
    {
        $segar = $paksa ? null : cache(self::KUNCI_SEGAR);
        if (is_array($segar)) {
            return self::bungkus($segar, false);
        }

        $data = self::tarik();
        if ($data !== null) {
            $data['diambil_ts'] = time();
            cache()->save(self::KUNCI_SEGAR, $data, self::TTL_SEGAR);
            cache()->save(self::KUNCI_CADANGAN, $data, self::TTL_CADANGAN);
            try { self::rekam($data); } catch (\Throwable $e) { log_message('warning', '[ISPU] gagal merekam riwayat: ' . $e->getMessage()); }
            return self::bungkus($data, false);
        }

        $lama = cache(self::KUNCI_CADANGAN);
        if (is_array($lama)) {
            return self::bungkus($lama, true);
        }
        return ['ok' => false, 'stasiun' => [], 'diambil_pada' => null, 'umur_menit' => null, 'basi' => false,
                'galat' => 'Data ISPU belum bisa diambil dari sumber resmi.'];
    }

    public static function kategori(?float $nilai): ?array
    {
        if ($nilai === null) return null;
        foreach (self::KATEGORI as $k) {
            if ($nilai <= $k['maks']) return $k;
        }
        return end(self::KATEGORI);
    }

    private static function bungkus(array $data, bool $basi): array
    {
        $ts = (int) ($data['diambil_ts'] ?? time());
        // Estimasi BSB dihitung saat dibaca (dari data stasiun di cache), jadi ikut
        // cache 600 dtk + cadangan 24 jam tanpa mengubah isi cache.
        $stasiun = $data['stasiun'];
        $bsb = self::estimasiBsb($stasiun);
        if ($bsb !== null) array_unshift($stasiun, $bsb);

        // Indikator asap (karhutla) + tren dari rekaman MIC — aditif, dihitung saat dibaca.
        $asap = null;
        try {
            $asap = self::analisaAsap($stasiun, self::bacaRiwayat());
        } catch (\Throwable $e) {
            log_message('warning', '[ISPU] gagal analisa asap: ' . $e->getMessage());
        }
        $riwayat = null;
        if ($asap !== null) {
            foreach ($stasiun as $i => $s) {
                if (isset($asap['per'][$s['id'] ?? ''])) $stasiun[$i]['asap'] = $asap['per'][$s['id']];
            }
            $riwayat = $asap['riwayat'];
            $asap    = $asap['keseluruhan'];
        }
        return [
            'ok'           => $data['stasiun'] !== [],
            'stasiun'      => $stasiun,
            'asap'         => $asap,
            'riwayat'      => $riwayat,
            'diambil_pada' => date('c', $ts),
            'umur_menit'   => (int) floor((time() - $ts) / 60),
            'basi'         => $basi,
            'galat'        => $data['stasiun'] === [] ? 'Stasiun Balikpapan tidak ditemukan di sumber.' : null,
        ];
    }

    /** Jarak lingkaran-besar (km), rumus haversine. */
    public static function jarakKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0088;
        $a = deg2rad($lat2 - $lat1);
        $b = deg2rad($lng2 - $lng1);
        $h = sin($a / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($b / 2) ** 2;
        return 2 * $r * asin(min(1.0, sqrt($h)));
    }

    /**
     * ESTIMASI (bukan pengukuran) ISPU Kawasan BSB: interpolasi invers jarak kuadrat
     * dari stasiun resmi yang layak (tidak pemeliharaan, nilai ada). Sub-indeks
     * parameter hanya diinterpolasi bila tersedia di SEMUA stasiun yang dipakai.
     * Null bila tak ada stasiun layak.
     */
    public static function estimasiBsb(array $stasiun): ?array
    {
        $pakai = [];
        foreach ($stasiun as $s) {
            if (! is_array($s) || ! empty($s['pemeliharaan']) || ! isset($s['nilai']) || ! is_numeric($s['nilai'])) continue;
            $id  = (string) ($s['id'] ?? '');
            $lat = $s['lat'] ?? (self::KOORD_CADANGAN[$id][0] ?? null);
            $lng = $s['lng'] ?? (self::KOORD_CADANGAN[$id][1] ?? null);
            if (! is_numeric($lat) || ! is_numeric($lng)) continue;
            $d = max(self::jarakKm(self::BSB_LAT, self::BSB_LNG, (float) $lat, (float) $lng), 0.001);
            $pakai[] = ['s' => $s, 'd' => $d, 'w' => 1 / ($d * $d)];
        }
        if ($pakai === []) return null;

        $sumW = array_sum(array_column($pakai, 'w'));
        foreach ($pakai as &$p) $p['persen'] = $p['w'] / $sumW * 100;
        unset($p);

        $nilai = 0.0;
        foreach ($pakai as $p) $nilai += $p['persen'] / 100 * (float) $p['s']['nilai'];
        $nilai = round($nilai, 1);
        $kat   = self::kategori((float) round($nilai));

        $parameter = [];
        foreach (self::PARAM as $k => $label) {
            $v = 0.0; $semua = true;
            foreach ($pakai as $p) {
                $idx = null;
                foreach ($p['s']['parameter'] ?? [] as $pp) {
                    if (($pp['kode'] ?? '') === $k) { $idx = (float) $pp['indeks']; break; }
                }
                if ($idx === null) { $semua = false; break; }
                $v += $p['persen'] / 100 * $idx;
            }
            if (! $semua) continue;
            $v  = round($v, 1);
            $kp = self::kategori((float) round($v));
            $parameter[] = ['kode' => $k, 'label' => $label, 'indeks' => $v, 'kategori' => $kp['label'],
                            'warna' => $kp['warna'], 'konsentrasi' => null];
        }
        $kritis = '';
        $maks = -1.0;
        foreach ($parameter as $pp) {
            if ($pp['indeks'] > $maks) { $maks = $pp['indeks']; $kritis = $pp['label']; }
        }
        if ($kritis === '' && count($pakai) === 1) $kritis = (string) ($pakai[0]['s']['kritis'] ?? '');

        // Waktu = data paling lama di antara stasiun yang dipakai (string ISO +08:00, urut leksikografis).
        $waktu = null;
        foreach ($pakai as $p) {
            $w = $p['s']['waktu_wita'] ?? null;
            if ($w && ($waktu === null || strcmp($w, $waktu) < 0)) $waktu = $w;
        }

        $n = count($pakai);
        $sumber = []; $jarak = [];
        foreach ($pakai as $p) {
            $km = round($p['d'], 1);
            $jarak[(string) $p['s']['id']] = $km;
            $sumber[] = ['id' => (string) $p['s']['id'], 'nama' => (string) ($p['s']['nama'] ?? ''), 'jarak_km' => $km,
                         'bobot_persen' => round($p['persen'], 1), 'nilai' => (float) $p['s']['nilai'],
                         'waktu_wita' => $p['s']['waktu_wita'] ?? null];
        }
        $dipakaiId = array_column($sumber, 'id');
        $tak = [];
        foreach ($stasiun as $s) {
            if (is_array($s) && ! in_array((string) ($s['id'] ?? ''), $dipakaiId, true)) {
                $tak[] = ($s['nama'] ?? $s['id'] ?? '?') . (! empty($s['pemeliharaan']) ? ' (pemeliharaan)' : ' (tanpa nilai)');
            }
        }
        $metode = 'Bukan pengukuran langsung: perkiraan dari ' . $n . ' stasiun resmi terdekat (interpolasi berbobot jarak), '
                . 'untuk kawasan BSB (E-Walk & Pentacity).';
        if ($n === 1) $metode .= ' Hanya satu stasiun yang datanya layak' . ($tak ? ' — ' . implode(', ', $tak) . ' tidak dipakai' : '') . '.';

        return [
            'id' => self::BSB_ID, 'nama' => self::BSB_NAMA,
            'alamat' => 'Balikpapan Superblock (E-Walk & Pentacity)', 'tipe' => 'Estimasi',
            'estimasi' => true, 'jumlah_stasiun' => $n, 'sumber_stasiun' => $sumber, 'jarak_km' => $jarak,
            'metode' => $metode,
            'lat' => self::BSB_LAT, 'lng' => self::BSB_LNG,
            'nilai' => $nilai, 'kategori' => $kat['label'], 'warna' => $kat['warna'], 'saran' => $kat['saran'],
            'keterangan' => 'Perkiraan, bukan pengukuran resmi.', 'kritis' => $kritis,
            'waktu_wita' => $waktu, 'pemeliharaan' => false, 'parameter' => $parameter,
        ];
    }

    // ───────────────────────── Indikator asap (karhutla) + riwayat ─────────────────────────

    /** @return list<array{ts:int,w:array,s:array}> */
    private static function bacaRiwayat(): array
    {
        $r = cache(self::KUNCI_RIWAYAT);
        return is_array($r) ? array_values($r) : [];
    }

    /**
     * Rekam titik riwayat {ts, w: waktu sumber per stasiun, s: sub-indeks pm25/pm10/co per stasiun}.
     * Tidak menyimpan duplikat bila waktu sumber semua stasiun sama dengan titik terakhir.
     * Cache CI4 saja (tanpa tabel DB). Mengembalikan true bila titik baru disimpan.
     */
    public static function rekam(array $data, ?int $sekarang = null): bool
    {
        $sekarang ??= time();
        $w = []; $v = [];
        foreach ($data['stasiun'] ?? [] as $s) {
            if (! is_array($s) || ! empty($s['pemeliharaan']) || empty($s['id'])) continue;
            $id = (string) $s['id'];
            $w[$id] = $s['waktu_wita'] ?? null;
            $baris = ['pm25' => null, 'pm10' => null, 'co' => null];
            foreach ($s['parameter'] ?? [] as $p) {
                if (array_key_exists($p['kode'] ?? '', $baris) && isset($p['indeks'])) $baris[$p['kode']] = (float) $p['indeks'];
            }
            $v[$id] = $baris;
        }
        if ($v === []) return false;

        $titik = self::bacaRiwayat();
        $akhir = end($titik);
        if (is_array($akhir) && ($akhir['w'] ?? null) === $w && ! in_array(null, $w, true)) return false;
        if (is_array($akhir) && ($akhir['w'] ?? null) === $w && ($akhir['s'] ?? null) === $v) return false;

        $titik[] = ['ts' => $sekarang, 'w' => $w, 's' => $v];
        $batas = $sekarang - self::TTL_RIWAYAT;
        $titik = array_values(array_filter($titik, static fn ($t) => ($t['ts'] ?? 0) >= $batas));
        if (count($titik) > self::MAKS_TITIK) $titik = array_slice($titik, -self::MAKS_TITIK);
        cache()->save(self::KUNCI_RIWAYAT, $titik, self::TTL_RIWAYAT);
        return true;
    }

    private const ASAP_PARAM = [
        'pm25' => ['PM2.5', 'utama'],
        'pm10' => ['PM10', 'pendamping'],
        'co'   => ['CO', 'penguat'],
    ];

    /** Seri [ts, nilai] sub-indeks satu parameter untuk satu stasiun (BSB = interpolasi berbobot dari stasiun sumbernya). */
    private static function seriParam(array $s, string $kode, array $titik): array
    {
        $out = [];
        foreach ($titik as $t) {
            if (! empty($s['estimasi'])) {
                $sum = 0.0; $ok = ($s['sumber_stasiun'] ?? []) !== [];
                foreach ($s['sumber_stasiun'] ?? [] as $q) {
                    $x = $t['s'][$q['id']][$kode] ?? null;
                    if ($x === null) { $ok = false; break; }
                    $sum += $q['bobot_persen'] / 100 * $x;
                }
                if ($ok) $out[] = [(int) $t['ts'], round($sum, 1)];
            } else {
                $x = $t['s'][$s['id'] ?? ''][$kode] ?? null;
                if ($x !== null) $out[] = [(int) $t['ts'], (float) $x];
            }
        }
        return $out;
    }

    /** Tren dibanding titik ±3 jam lalu (diterima 1,5–4,5 jam). Null bila riwayat kurang. */
    private static function hitungTren(float $kini, array $seri, int $sekarang): ?array
    {
        $pilih = null; $selisihWaktu = PHP_INT_MAX;
        foreach ($seri as [$ts, $v]) {
            $umur = $sekarang - $ts;
            if ($umur < 5400 || $umur > 16200) continue;
            $d = abs($umur - 10800);
            if ($d < $selisihWaktu) { $selisihWaktu = $d; $pilih = [$umur, $v]; }
        }
        if ($pilih === null) return null;
        $sel = round($kini - $pilih[1], 1);
        $arah = $sel >= self::TREN_AMBANG ? 'naik' : ($sel <= -self::TREN_AMBANG ? 'turun' : 'stabil');
        return ['arah' => $arah, 'selisih' => $sel, 'jam' => round($pilih[0] / 3600, 1)];
    }

    private static function kecilkan(array $seri, int $maks = 36): array
    {
        $n = count($seri);
        if ($n <= $maks) return $seri;
        $out = [];
        for ($i = 0; $i < $maks; $i++) $out[] = $seri[(int) round($i * ($n - 1) / ($maks - 1))];
        return $out;
    }

    private static function kritisAsap(string $kritis): bool
    {
        $k = strtoupper(strtr($kritis, ['₂' => '2', '₃' => '3', ' ' => '']));
        return strpos($k, 'PM') !== false || (bool) preg_match('/(^|[^A-Z])CO($|[^A-Z0-9])/', $k);
    }

    private const ASAP_STATUS = [
        'BAIK'                => ['aman', 'Tidak ada tanda asap', 'Tanpa tanda asap', 0,
            'PM2.5 dan PM10 pada level Baik: tidak ada tanda asap karhutla. Tetap pantau bila musim kemarau atau titik panas meningkat.'],
        'SEDANG'              => ['waspada', 'Partikel meningkat (Sedang)', 'Waspada partikel', 1,
            'Partikel halus mulai meningkat (Sedang). Kelompok sensitif batasi aktivitas berat di luar ruangan; siapkan masker bila terus naik.'],
        'TIDAK SEHAT'         => ['tidak_sehat', 'Asap/partikel Tidak Sehat', 'Asap Tdk Sehat', 2,
            'Gunakan masker N95/KN95 saat di luar ruangan, tutup pintu dan jendela serta kurangi masuknya udara luar (ventilasi/AHU udara segar), dan kurangi aktivitas luar ruangan, terutama kelompok sensitif.'],
        'SANGAT TIDAK SEHAT'  => ['sangat_tidak_sehat', 'Asap Sangat Tidak Sehat', 'Asap Sgt Tdk Sehat', 3,
            'Hindari aktivitas luar ruangan (parkir terbuka, teras, acara outdoor). Pakai masker N95/KN95 bila terpaksa keluar, tutup ventilasi udara luar, dan nyalakan penyaring/resirkulasi udara di dalam gedung.'],
        'BERBAHAYA'           => ['berbahaya', 'Asap Berbahaya', 'Asap Berbahaya', 4,
            'Tetap di dalam ruangan dengan ventilasi udara luar tertutup, pakai masker N95/KN95 bila harus keluar, hentikan seluruh kegiatan luar ruangan, dan ikuti arahan otoritas setempat.'],
    ];

    /**
     * Status asap karhutla per stasiun + keseluruhan, tren, dan banner.
     * Parameter utama PM2.5, pendamping PM10, penguat CO; SO2/NO2/O3 bukan indikator asap.
     * Murni (tanpa cache): $titik = hasil rekam(); $sekarang bisa diatur untuk uji.
     *
     * @return array{per:array, keseluruhan:array, riwayat:array}
     */
    public static function analisaAsap(array $stasiun, array $titik, ?int $sekarang = null): array
    {
        $sekarang ??= time();

        $pemilikPm10 = [];
        foreach ($stasiun as $s) {
            if (! empty($s['estimasi'])) continue;
            foreach ($s['parameter'] ?? [] as $p) {
                if (($p['kode'] ?? '') === 'pm10') $pemilikPm10[] = trim(preg_replace('/^Balikpapan\s+/i', '', (string) ($s['nama'] ?? '')));
            }
        }
        $hanyaPm10 = $pemilikPm10 ? 'hanya ' . implode(' & ', $pemilikPm10) : 'tidak tersedia';

        $per = [];
        foreach ($stasiun as $s) {
            if (! is_array($s) || ! isset($s['id'])) continue;
            $ind = []; $idx = [];
            foreach (self::ASAP_PARAM as $kode => [$label, $peran]) {
                $p = null;
                foreach ($s['parameter'] ?? [] as $pp) if (($pp['kode'] ?? '') === $kode) { $p = $pp; break; }
                $baris = ['kode' => $kode, 'label' => $label, 'peran' => $peran, 'tersedia' => $p !== null,
                          'indeks' => null, 'kategori' => null, 'warna' => '#64748b', 'konsentrasi' => null, 'satuan' => 'µg/m³',
                          'catatan' => null, 'tren' => null, 'seri' => []];
                if ($p === null) {
                    $baris['catatan'] = $kode === 'pm10' ? $label . ': ' . $hanyaPm10 : $label . ' tidak tersedia';
                } else {
                    $idx[$kode] = (float) $p['indeks'];
                    $baris['indeks'] = (float) $p['indeks'];
                    $baris['kategori'] = $p['kategori'] ?? null;
                    $baris['warna'] = $p['warna'] ?? '#64748b';
                    $baris['konsentrasi'] = $p['konsentrasi'] ?? null;
                    $seri = self::seriParam($s, $kode, $titik);
                    $baris['tren'] = self::hitungTren((float) $p['indeks'], $seri, $sekarang);
                    if ($kode === 'pm25') $baris['seri'] = self::kecilkan($seri);
                }
                $ind[] = $baris;
            }

            $pm = array_filter([$idx['pm25'] ?? null, $idx['pm10'] ?? null], static fn ($x) => $x !== null);
            $lv = $pm ? max($pm) : null;
            $co = $idx['co'] ?? null;
            $catatan = [];
            if ($lv === null) {
                $st = ['tanpa_data', 'Data PM belum tersedia', 'Data PM belum ada', -1, 'Parameter asap (PM2.5/PM10) belum tersedia dari stasiun ini, sehingga status asap tidak bisa dinilai.'];
                $warna = '#64748b';
            } else {
                $kat = self::kategori($lv);
                $st = self::ASAP_STATUS[$kat['kode']];
                $warna = $kat['warna'];
            }
            $trenNaik = false;
            foreach ($ind as $b) {
                if (in_array($b['kode'], ['pm25', 'pm10'], true) && ($b['tren']['arah'] ?? '') === 'naik') $trenNaik = true;
            }
            $saran = $st[4];
            if ($lv !== null && $lv > 50 && $lv <= 100 && $trenNaik) $saran .= ' Tren PM sedang naik: pantau berkala.';
            if ($lv !== null && count($pm) === 1) {
                $catatan[] = 'Status dinilai dari ' . (isset($idx['pm25']) ? 'PM2.5' : 'PM10') . ' saja.';
            }
            $pengutat = false;
            if ($co !== null && $lv !== null) {
                if ($co > 50 && $lv > 50) { $pengutat = true; $catatan[] = 'CO ikut meningkat (sub-indeks ' . round($co) . '): memperkuat indikasi pembakaran.'; }
                elseif ($co > 100 && $lv <= 50) $catatan[] = 'CO tinggi tanpa kenaikan PM: bukan pola asap karhutla (mis. knalpot atau pembakaran lokal).';
            }
            $kritis = (string) ($s['kritis'] ?? '');
            $bukanAsap = $kritis !== '' && isset($s['nilai']) && $s['nilai'] > 50 && ! self::kritisAsap($kritis);
            if ($bukanAsap) $catatan[] = 'Parameter kritis stasiun ini ' . $kritis . ': kenaikan ISPU bukan dari asap.';

            $per[(string) $s['id']] = [
                'status' => $st[0], 'label' => $st[1], 'singkat' => $st[2], 'level' => $st[3], 'warna' => $warna,
                'indeks_pm' => $lv, 'saran' => $saran, 'bukan_asap' => $bukanAsap, 'penguat_co' => $pengutat,
                'tren_naik' => $trenNaik, 'catatan' => $catatan, 'indikator' => $ind,
                'estimasi' => ! empty($s['estimasi']), 'pemeliharaan' => ! empty($s['pemeliharaan']),
            ];
        }

        // Keseluruhan = terburuk di antara stasiun RESMI (BSB turunan dari keduanya); bila tak ada, pakai semuanya.
        $nyata = []; $semua = [];
        foreach ($stasiun as $s) {
            $id = (string) ($s['id'] ?? '');
            if (! isset($per[$id])) continue;
            $semua[$id] = $s;
            if (empty($s['estimasi'])) $nyata[$id] = $s;
        }
        $kandidat = $nyata ?: $semua;
        $maks = -2; $pilihId = null;
        foreach ($kandidat as $id => $s) {
            if ($per[$id]['level'] > $maks) { $maks = $per[$id]['level']; $pilihId = $id; }
        }
        $kes = null;
        if ($pilihId !== null) {
            $b = $per[$pilihId];
            $pemicu = [];
            foreach ($kandidat as $id => $s) if ($per[$id]['level'] === $maks) $pemicu[] = (string) ($s['nama'] ?? $id);
            $kes = ['status' => $b['status'], 'label' => $b['label'], 'singkat' => $b['singkat'], 'level' => $b['level'],
                    'warna' => $b['warna'], 'saran' => $b['saran'], 'stasiun_pemicu' => $pemicu,
                    'bukan_asap' => count(array_filter($kandidat, static fn ($s, $id) => $per[$id]['bukan_asap'], ARRAY_FILTER_USE_BOTH)) === count($kandidat),
                    'banner' => self::banner($kandidat, $per)];
        }

        $mulai = $titik ? (int) $titik[0]['ts'] : null;
        $akhir = $titik ? (int) end($titik)['ts'] : null;
        $rentang = $mulai !== null ? round(($sekarang - $mulai) / 3600, 1) : 0;
        $cukup = false;
        foreach ($per as $b) foreach ($b['indikator'] as $i) if ($i['tren'] !== null) $cukup = true;
        $riwayat = [
            'jumlah' => count($titik),
            'mulai_wita' => $mulai !== null ? substr(gmdate('c', $mulai + 8 * 3600), 0, 19) . '+08:00' : null,
            'terakhir_wita' => $akhir !== null ? substr(gmdate('c', $akhir + 8 * 3600), 0, 19) . '+08:00' : null,
            'rentang_jam' => $rentang, 'cukup' => $cukup,
            'sumber' => 'Rekaman MIC (bukan dari sumber resmi)',
            'keterangan' => $mulai !== null
                ? 'Tren dihitung dari rekaman MIC sejak ' . substr(gmdate('c', $mulai + 8 * 3600), 0, 16) . ' WITA (bukan dari sumber resmi).'
                : 'Tren dihitung dari rekaman MIC (belum ada rekaman).',
        ];
        return ['per' => $per, 'keseluruhan' => $kes ?? ['status' => 'tanpa_data', 'label' => 'Data PM belum tersedia', 'singkat' => 'Data PM belum ada',
                'level' => -1, 'warna' => '#64748b', 'saran' => '', 'stasiun_pemicu' => [], 'bukan_asap' => false, 'banner' => null], 'riwayat' => $riwayat];
    }

    /** Merah bila PM2.5/PM10 ≥ 101 di stasiun mana pun; kuning bila 51–100 dengan tren naik; null bila aman. */
    private static function banner(array $stasiun, array $per): ?array
    {
        $merah = []; $kuning = []; $puncak = 0.0;
        foreach ($stasiun as $id => $s) {
            $b = $per[$id];
            if ($b['pemeliharaan'] || $b['indeks_pm'] === null) continue;
            foreach ($b['indikator'] as $i) {
                if (! in_array($i['kode'], ['pm25', 'pm10'], true) || $i['indeks'] === null) continue;
                $item = ['stasiun' => (string) ($s['nama'] ?? $id), 'parameter' => $i['label'], 'indeks' => round($i['indeks']), 'kategori' => $i['kategori']];
                if ($i['indeks'] >= 101) { $merah[] = $item; $puncak = max($puncak, $i['indeks']); }
                elseif ($i['indeks'] > 50 && ($i['tren']['arah'] ?? '') === 'naik') $kuning[] = $item + ['selisih' => $i['tren']['selisih'], 'jam' => $i['tren']['jam']];
            }
        }
        $daftar = static function (array $a): string {
            $g = [];
            foreach ($a as $x) $g[$x['stasiun']][] = $x['parameter'] . ' ' . $x['indeks'] . ' (' . $x['kategori'] . ')';
            $o = [];
            foreach ($g as $nama => $l) $o[] = $nama . ': ' . implode(', ', $l);
            return implode('; ', $o);
        };
        if ($merah) {
            $kat = self::kategori($puncak);
            return ['tingkat' => 'merah', 'judul' => 'Peringatan asap/partikel: ' . $kat['label'],
                    'teks' => $daftar($merah) . '.', 'saran' => self::ASAP_STATUS[$kat['kode']][4],
                    'boleh_tutup' => $puncak < 201, 'rincian' => $merah];
        }
        if ($kuning) {
            return ['tingkat' => 'kuning', 'judul' => 'Waspada: partikel (asap) sedang naik',
                    'teks' => $daftar($kuning) . ', naik dibanding ±3 jam lalu.', 'saran' => self::ASAP_STATUS['SEDANG'][4],
                    'boleh_tutup' => true, 'rincian' => $kuning];
        }
        return null;
    }

    /** @return array{stasiun:array}|null null bila sumber tak terjangkau/format berubah */
    private static function tarik(): ?array
    {
        if (! function_exists('curl_init')) return null;
        $ch = curl_init(self::URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'MallIC/1.2 (+dashboard ISPU)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw  = curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false || $kode !== 200) {
            log_message('warning', '[ISPU] gagal ambil: HTTP ' . $kode . ' ' . curl_error($ch));
            return null;
        }
        $j = json_decode((string) $raw, true);
        if (! is_array($j) || ! isset($j['rows']) || ! is_array($j['rows'])) {
            log_message('warning', '[ISPU] format respons tidak dikenal');
            return null;
        }

        $per = [];
        foreach ($j['rows'] as $r) {
            if (in_array($r['id_stasiun'] ?? '', self::STASIUN, true)) {
                $per[$r['id_stasiun']] = self::rapikan($r);
            }
        }
        $stasiun = [];
        foreach (self::STASIUN as $id) {
            if (isset($per[$id])) $stasiun[] = $per[$id];
        }
        return ['stasiun' => $stasiun];
    }

    private static function rapikan(array $r): array
    {
        $nilai = isset($r['val']) && is_numeric($r['val']) ? (float) $r['val'] : null;
        $kat   = self::kategori($nilai);

        // Waktu sumber memuat zona (WIB/WITA/WIT) — samakan ke WITA (Balikpapan).
        $offset = ['WIB' => 7, 'WITA' => 8, 'WIT' => 9][strtoupper((string) ($r['time_z'] ?? 'WITA'))] ?? 8;
        $waktuWita = null;
        if (! empty($r['waktu'])) {
            $ts = strtotime($r['waktu'] . ' UTC');
            if ($ts !== false) {
                $waktuWita = gmdate('c', $ts - $offset * 3600 + 8 * 3600);
                $waktuWita = substr($waktuWita, 0, 19) . '+08:00';
            }
        }

        $parameter = [];
        foreach (self::PARAM as $k => $label) {
            $t = $r['t_' . $k] ?? null;
            if ($t === null || $t === '' || ! is_numeric($t)) continue;
            $kp = self::kategori((float) $t);
            $parameter[] = [
                'kode' => $k, 'label' => $label, 'indeks' => (float) $t,
                'kategori' => $kp['label'], 'warna' => $kp['warna'],
                'konsentrasi' => isset($r['a_' . $k]) && is_numeric($r['a_' . $k]) ? (float) $r['a_' . $k] : null,
            ];
        }

        return [
            'id'          => (string) $r['id_stasiun'],
            'nama'        => trim((string) ($r['nama'] ?? $r['id_stasiun'])),
            'alamat'      => trim((string) ($r['alamat'] ?? '')),
            'tipe'        => trim((string) ($r['tipe_text'] ?? '')),
            'lat'         => isset($r['lat']) && is_numeric($r['lat']) ? (float) $r['lat'] : null,
            'lng'         => isset($r['lon']) && is_numeric($r['lon']) ? (float) $r['lon'] : null,
            'nilai'       => $nilai,
            'kategori'    => $kat['label'] ?? null,
            'warna'       => $kat['warna'] ?? '#64748b',
            'saran'       => $kat['saran'] ?? null,
            'keterangan'  => trim((string) ($r['kategori']['keterangan'] ?? '')),
            'kritis'      => trim(html_entity_decode(strip_tags((string) ($r['param'] ?? '')))),
            'waktu_wita'  => $waktuWita,
            'pemeliharaan' => (string) ($r['is_maintenance'] ?? '0') === '1',
            'parameter'   => $parameter,
        ];
    }
}
