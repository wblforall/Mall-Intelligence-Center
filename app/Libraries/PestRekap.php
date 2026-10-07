<?php

namespace App\Libraries;

use App\Models\PestVisitModel;

/**
 * Rekap Pest Control untuk RENTANG TANGGAL BEBAS — dipakai bersama oleh layar
 * rekap, versi cetak, ekspor Excel, dan halaman Compare, supaya keempatnya tak
 * mungkin menghitung berbeda (pelajaran dari Traffic, yang menyalin hitungan
 * yang sama tiga kali di summary/printSummary/exportSummary).
 *
 * Masalah utamanya adalah baris 'rekap_legacy': satu angka untuk SATU BULAN,
 * bertanggal akhir bulan, tanpa tanggal kunjungan. Aturannya (PESTCARE_DESIGN §6:
 * angka bulanan tidak boleh dikarang menjadi harian):
 *
 *  - Bulan-mall yang sudah punya kunjungan harian → rekap impor DIABAIKAN
 *    (tergeser), kalau tidak bulan itu terhitung dua kali.
 *  - Bulan yang SELURUHNYA berada di dalam rentang → rekap impor DIHITUNG,
 *    tetapi tidak pernah dipecah ke hari/minggu: di rincian ia tampil sebagai
 *    baris tersendiri (atau di ember bulannya bila rinciannya per bulan).
 *  - Bulan yang TERPOTONG rentang → rekap impor TIDAK dihitung, dan halaman
 *    wajib menyebutnya. Memasukkan sebulan penuh ke rentang 10 hari sama
 *    salahnya dengan membaginya rata.
 *
 * Bagian `susun()` murni (tanpa DB) supaya bisa diuji unit.
 */
class PestRekap
{
    /** Panjang rentang maksimum (hari) — 3 tahun; cukup untuk pembanding tahunan. */
    public const MAKS_HARI = 1096;

    /** Batas mode rincian: ≤31 hari per hari, ≤92 hari per minggu, selebihnya per bulan. */
    public const BATAS_HARIAN  = 31;
    public const BATAS_MINGGUAN = 92;

    /** Ambil data lalu susun. $rinci=false melewati rincian per hari/minggu/bulan (untuk Compare). */
    public static function bangun(string $dari, string $sampai, ?string $mall = null, bool $rinci = true): array
    {
        $m = new PestVisitModel();
        $r = self::susun(
            $m->harianPerMallItem($dari, $sampai, $mall),
            $m->kunjunganDalamRentang($dari, $sampai, $mall),
            $m->legacyDalamRentang($dari, $sampai, $mall),
            $dari, $sampai, $mall
        );
        if ($rinci) $r['rincian'] = self::rincian($r);
        return $r;
    }

    /**
     * @param array $harian    [tanggal => [mall => [item_id => n]]] — hanya kunjungan
     * @param array $kunjungan list ['mall','tanggal','total'] — termasuk yang nihil
     * @param array $legacy    keluaran PestVisitModel::legacyDalamRentang()
     */
    public static function susun(array $harian, array $kunjungan, array $legacy, string $dari, string $sampai, ?string $mall): array
    {
        $malls = $mall ? [$mall] : array_keys(PestVisitModel::MALLS);

        $perMall = array_fill_keys($malls, []);
        foreach ($harian as $perM) {
            foreach ($perM as $mk => $perItem) {
                foreach ($perItem as $iid => $n) $perMall[$mk][$iid] = ($perMall[$mk][$iid] ?? 0) + $n;
            }
        }
        $grandKunjungan = 0;
        foreach ($perMall as $perItem) $grandKunjungan += array_sum($perItem);

        // Nasib tiap baris rekap impor.
        $dipakai = $tergeser = $terpotong = [];
        foreach ($legacy as $l) {
            $awalBulan  = $l['bulan'] . '-01';
            $akhirBulan = date('Y-m-t', strtotime($awalBulan));
            $l['dari_bulan']  = $awalBulan;
            $l['akhir_bulan'] = $akhirBulan;
            if ($l['tergeser']) {
                $tergeser[] = $l;
            } elseif ($dari <= $awalBulan && $sampai >= $akhirBulan) {
                $dipakai[] = $l;
                foreach ($l['items'] as $iid => $n) $perMall[$l['mall']][$iid] = ($perMall[$l['mall']][$iid] ?? 0) + $n;
            } else {
                // Hari bulan itu yang tercakup rentang — untuk kalimat catatan.
                $l['hari_tercakup'] = (int) ((strtotime(min($sampai, $akhirBulan)) - strtotime(max($dari, $awalBulan))) / 86400) + 1;
                $l['hari_bulan']    = (int) date('t', strtotime($awalBulan));
                $terpotong[] = $l;
            }
        }

        $perItem = [];
        $totalMall = [];
        foreach ($perMall as $mk => $items) {
            $totalMall[$mk] = array_sum($items);
            foreach ($items as $iid => $n) $perItem[$iid] = ($perItem[$iid] ?? 0) + $n;
        }

        $kunj = array_fill_keys($malls, ['n' => 0, 'nihil' => 0]);
        $puncak = null;
        foreach ($kunjungan as $k) {
            $t = (int) $k['total'];
            $kunj[$k['mall']]['n']++;
            if ($t === 0) $kunj[$k['mall']]['nihil']++;
            if ($t > 0 && (! $puncak || $t > $puncak['total'])) {
                $puncak = ['tanggal' => $k['tanggal'], 'mall' => $k['mall'], 'total' => $t];
            }
        }

        return [
            'dari'           => $dari,
            'sampai'         => $sampai,
            'hari'           => (int) ((strtotime($sampai) - strtotime($dari)) / 86400) + 1,
            'mall'           => $mall,
            'malls'          => $malls,
            'perMall'        => $perMall,
            'totalMall'      => $totalMall,
            'perItem'        => $perItem,
            'grand'          => array_sum($totalMall),
            'grandKunjungan' => $grandKunjungan,
            'grandLegacy'    => array_sum(array_column($dipakai, 'total')),
            'kunjungan'      => $kunj,
            'jmlKunjungan'   => array_sum(array_column($kunj, 'n')),
            'jmlNihil'       => array_sum(array_column($kunj, 'nihil')),
            'puncakHarian'   => $puncak,
            'legacyDipakai'  => $dipakai,
            'legacyTergeser' => $tergeser,
            'legacyTerpotong'=> $terpotong,
            'harian'         => $harian,
            'daftarKunjungan'=> $kunjungan,
        ];
    }

    /** Mode rincian menurut panjang rentang. */
    public static function mode(int $hari): string
    {
        if ($hari <= self::BATAS_HARIAN)   return 'harian';
        if ($hari <= self::BATAS_MINGGUAN) return 'mingguan';
        return 'bulanan';
    }

    /**
     * Rincian per hari / minggu ISO / bulan, dipotong di batas rentang.
     *
     * Rekap impor yang dihitung: pada mode bulanan masuk ke ember bulannya
     * (satuannya cocok); pada mode harian/mingguan dikumpulkan di `legacy` —
     * satu baris tersendiri — supaya jumlah seluruh baris tetap sama dengan
     * total rentang tanpa satu pun angka harian yang dikarang.
     */
    public static function rincian(array $r): array
    {
        $mode = self::mode($r['hari']);
        $ember = [];

        $kunciDari = function (string $tgl) use ($mode): string {
            return match ($mode) {
                'harian'   => $tgl,
                'mingguan' => date('oW', strtotime($tgl)),
                default    => substr($tgl, 0, 7),
            };
        };

        // Siapkan ember berurutan untuk seluruh rentang — periode tanpa data
        // tetap tampil, supaya celah input terlihat.
        $cursor = strtotime($r['dari']);
        $batas  = strtotime($r['sampai']);
        while ($cursor <= $batas) {
            $tgl = date('Y-m-d', $cursor);
            $k   = $kunciDari($tgl);
            if (! isset($ember[$k])) {
                [$awal, $akhir, $label] = match ($mode) {
                    'harian'   => [$tgl, $tgl, $tgl],
                    'mingguan' => [date('Y-m-d', strtotime('monday this week', $cursor)),
                                   date('Y-m-d', strtotime('sunday this week', $cursor)),
                                   'W' . date('W', $cursor)],
                    default    => [date('Y-m-01', $cursor), date('Y-m-t', $cursor), substr($tgl, 0, 7)],
                };
                $ember[$k] = [
                    'kunci'     => $k,
                    'label'     => $label,
                    'dari'      => max($awal, $r['dari']),
                    'sampai'    => min($akhir, $r['sampai']),
                    'sebagian'  => $awal < $r['dari'] || $akhir > $r['sampai'],
                    'items'     => [],
                    'mall'      => array_fill_keys($r['malls'], 0),
                    'total'     => 0,
                    'kunjungan' => 0,
                    'legacy'    => false,
                ];
            }
            $cursor = strtotime('+1 day', $cursor);
        }

        foreach ($r['harian'] as $tgl => $perM) {
            $k = $kunciDari($tgl);
            if (! isset($ember[$k])) continue;
            foreach ($perM as $mk => $perItem) {
                foreach ($perItem as $iid => $n) {
                    $ember[$k]['items'][$iid] = ($ember[$k]['items'][$iid] ?? 0) + $n;
                    $ember[$k]['mall'][$mk]   = ($ember[$k]['mall'][$mk] ?? 0) + $n;
                    $ember[$k]['total']      += $n;
                }
            }
        }
        foreach ($r['daftarKunjungan'] as $kj) {
            $k = $kunciDari($kj['tanggal']);
            if (isset($ember[$k])) $ember[$k]['kunjungan']++;
        }

        $legacy = ['items' => [], 'mall' => array_fill_keys($r['malls'], 0), 'total' => 0, 'baris' => count($r['legacyDipakai'])];
        foreach ($r['legacyDipakai'] as $l) {
            if ($mode === 'bulanan' && isset($ember[$l['bulan']])) {
                $e = &$ember[$l['bulan']];
                foreach ($l['items'] as $iid => $n) $e['items'][$iid] = ($e['items'][$iid] ?? 0) + $n;
                $e['mall'][$l['mall']] = ($e['mall'][$l['mall']] ?? 0) + $l['total'];
                $e['total'] += $l['total'];
                $e['legacy'] = true;
                unset($e);
                continue;
            }
            foreach ($l['items'] as $iid => $n) $legacy['items'][$iid] = ($legacy['items'][$iid] ?? 0) + $n;
            $legacy['mall'][$l['mall']] = ($legacy['mall'][$l['mall']] ?? 0) + $l['total'];
            $legacy['total'] += $l['total'];
        }

        return [
            'mode'   => $mode,
            'ember'  => array_values($ember),
            'legacy' => $legacy,   // total 0 = tidak ada baris terpisah
        ];
    }

    /** Periode pembanding: sama panjang, tepat sebelum $dari. */
    public static function periodeSebelumnya(string $dari, string $sampai): array
    {
        $hari     = (int) ((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
        $prevTo   = date('Y-m-d', strtotime($dari . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($hari - 1) . ' days'));
        return [$prevFrom, $prevTo];
    }

    /**
     * Rentang dari query string yang sah: format Y-m-d, terbalik → ditukar,
     * lebih dari MAKS_HARI → awal dipotong. Return [dari, sampai].
     */
    public static function rentangSah(?string $dari, ?string $sampai, string $defDari, string $defSampai): array
    {
        $sah = function (?string $t, string $def): string {
            $d = $t ? \DateTime::createFromFormat('!Y-m-d', $t) : false;
            return ($d && $d->format('Y-m-d') === $t) ? $t : $def;
        };
        $a = $sah($dari, $defDari);
        $b = $sah($sampai, $defSampai);
        if ($a > $b) [$a, $b] = [$b, $a];
        $min = date('Y-m-d', strtotime($b . ' -' . (self::MAKS_HARI - 1) . ' days'));
        if ($a < $min) $a = $min;
        return [$a, $b];
    }

    // ── Teks ─────────────────────────────────────────────────────────────

    public static function angka(int|float $v): string
    {
        return number_format((float) $v, 0, ',', '.');
    }

    public static function persen(?float $p, bool $tanda = false): string
    {
        if ($p === null) return '—';
        $s = str_replace('.', ',', (string) abs(round($p, 1)));
        return ($tanda ? ($p > 0 ? '+' : ($p < 0 ? '−' : '')) : '') . $s . '%';
    }

    public static function pct(int $sekarang, int $lalu): ?float
    {
        return $lalu > 0 ? round(($sekarang - $lalu) / $lalu * 100, 1) : null;
    }

    /** "7 Okt 2026" */
    public static function tgl(string $d): string
    {
        $t = strtotime($d);
        return date('j', $t) . ' ' . substr(bulan_indo((int) date('n', $t)), 0, 3) . ' ' . date('Y', $t);
    }

    /** "1–30 Sep 2026", "28 Sep–4 Okt 2026", "15 Des 2025–14 Jan 2026" */
    public static function rentang(string $a, string $b): string
    {
        if ($a === $b) return self::tgl($a);
        $ta = strtotime($a); $tb = strtotime($b);
        $bln = fn($t) => substr(bulan_indo((int) date('n', $t)), 0, 3);
        if (date('Y', $ta) !== date('Y', $tb)) return self::tgl($a) . '–' . self::tgl($b);
        if (date('n', $ta) !== date('n', $tb)) return date('j', $ta) . ' ' . $bln($ta) . '–' . self::tgl($b);
        return date('j', $ta) . '–' . self::tgl($b);
    }

    /** "September 2026" dari "2026-09" */
    public static function bulan(string $ym): string
    {
        return bulan_indo((int) substr($ym, 5, 2)) . ' ' . substr($ym, 0, 4);
    }

    /** "Sep 2026" */
    public static function bulanSingkat(string $ym): string
    {
        return substr(bulan_indo((int) substr($ym, 5, 2)), 0, 3) . ' ' . substr($ym, 0, 4);
    }

    /** Label satu ember rincian, sesuai modenya. */
    public static function labelEmber(array $e, string $mode): string
    {
        return match ($mode) {
            'harian'   => self::tgl($e['dari']),
            'mingguan' => $e['label'] . ' · ' . self::rentang($e['dari'], $e['sampai']),
            default    => self::bulanSingkat($e['kunci']) . ($e['sebagian'] ? ' (' . self::rentang($e['dari'], $e['sampai']) . ')' : ''),
        };
    }

    /** Label pendek untuk sumbu grafik. */
    public static function labelSumbu(array $e, string $mode): string
    {
        return match ($mode) {
            'harian'   => date('j', strtotime($e['dari'])) . '/' . date('n', strtotime($e['dari'])),
            'mingguan' => $e['label'],
            default    => self::bulanSingkat($e['kunci']),
        };
    }

    /**
     * Catatan kejujuran tentang rekap impor dalam satu periode — dipakai di
     * layar, cetak, Excel, dan Compare dengan kalimat yang sama.
     */
    public static function catatanLegacy(array $r): array
    {
        $out = [];
        $nm = fn($l) => PestVisitModel::MALLS[$l['mall']] . ' ' . self::bulan($l['bulan']);
        if ($r['legacyDipakai']) {
            $out[] = 'Memuat rekap bulanan impor Excel (' . implode(', ', array_map($nm, $r['legacyDipakai']))
                . ', total ' . self::angka($r['grandLegacy']) . ' temuan) — angka sebulan tanpa tanggal kunjungan, '
                . 'jadi tidak dirinci per hari/minggu.';
        }
        if ($r['legacyTerpotong']) {
            $bag = array_map(fn($l) => $nm($l) . ' (' . self::angka($l['total']) . ' temuan; rentang mencakup '
                . $l['hari_tercakup'] . ' dari ' . $l['hari_bulan'] . ' hari)', $r['legacyTerpotong']);
            $out[] = 'TIDAK dihitung: rekap impor ' . implode(', ', $bag)
                . ' — rentang memotong bulan itu dan angka bulanan tidak bisa dipecah menjadi harian. '
                . 'Perluas rentang ke bulan penuh untuk memasukkannya.';
        }
        if ($r['legacyTergeser']) {
            $out[] = 'Rekap impor ' . implode(', ', array_map($nm, $r['legacyTergeser']))
                . ' diabaikan karena bulan itu sudah punya catatan kunjungan harian (supaya tidak terhitung dua kali).';
        }
        return $out;
    }

    /**
     * Ringkasan analisa rule-based untuk rekap rentang.
     * $items = PestItemModel::aktif(); $prev = bangun() periode pembanding.
     */
    public static function analisa(array $r, array $prev, array $items): array
    {
        $nf = [self::class, 'angka'];
        $nama = array_column($items, 'nama', 'id');
        $out = [];

        $periodeLalu = self::rentang($prev['dari'], $prev['sampai']);
        if ($r['grand'] === 0) {
            $out[] = $r['jmlKunjungan'] > 0
                ? 'Tidak ada temuan sepanjang periode ini dari ' . $nf($r['jmlKunjungan']) . ' kunjungan tercatat.'
                : 'Belum ada kunjungan tercatat pada periode ini — angka nol berarti belum diinput, bukan nihil temuan.';
        } else {
            $asal = $r['grandLegacy'] > 0
                ? ' (' . $nf($r['grandKunjungan']) . ' dari ' . $nf($r['jmlKunjungan']) . ' kunjungan tercatat, '
                    . $nf($r['grandLegacy']) . ' dari rekap impor)'
                : ' dari ' . $nf($r['jmlKunjungan']) . ' kunjungan';
            $d = self::pct($r['grand'], $prev['grand']);
            $out[] = 'Total ' . $nf($r['grand']) . ' temuan' . $asal
                . ($d === null
                    ? '; periode pembanding (' . $periodeLalu . ') tidak punya temuan untuk dibandingkan.'
                    : ', ' . ($d > 0 ? 'naik ' : ($d < 0 ? 'turun ' : 'setara ')) . self::persen($d)
                        . ' dibanding ' . $periodeLalu . ' (' . $nf($prev['grand']) . ').');

            if (count($r['malls']) > 1) {
                $out[] = 'Komposisi: ' . implode(' · ', array_map(fn($mk) => PestVisitModel::MALLS[$mk] . ' '
                    . $nf($r['totalMall'][$mk] ?? 0) . ' (' . round(($r['totalMall'][$mk] ?? 0) / $r['grand'] * 100) . '%)', $r['malls'])) . '.';
            }

            arsort($r['perItem']);
            $topId = array_key_first($r['perItem']);
            if ($topId !== null && $r['perItem'][$topId] > 0) {
                $out[] = ($nama[$topId] ?? 'Item #' . $topId) . ' menjadi temuan terbanyak ('
                    . $nf($r['perItem'][$topId]) . ', ' . round($r['perItem'][$topId] / $r['grand'] * 100) . '% dari seluruh temuan).';
            }

            if ($r['puncakHarian'] && $r['puncakHarian']['total'] >= 10) {
                $p = $r['puncakHarian'];
                $out[] = 'Temuan harian tertinggi: ' . $nf($p['total']) . ' di ' . PestVisitModel::MALLS[$p['mall']]
                    . ' pada ' . self::tgl($p['tanggal']) . ($r['grandKunjungan'] > 0
                        ? ' (' . round($p['total'] / $r['grandKunjungan'] * 100) . '% dari temuan kunjungan periode ini).' : '.');
            }

            foreach ($items as $it) {
                $id = (int) $it['id'];
                $a = $r['perItem'][$id] ?? 0; $b = $prev['perItem'][$id] ?? 0;
                if ($b > 0 && $a > $b * 2 && $a >= 10) {
                    $out[] = $it['nama'] . ' melonjak lebih dari dua kali lipat (' . $nf($b) . ' → ' . $nf($a)
                        . ') — perlu penelusuran titik sumber.';
                    break;
                }
            }
        }

        if ($r['jmlKunjungan'] > 0) {
            $out[] = $nf($r['jmlNihil']) . ' dari ' . $nf($r['jmlKunjungan']) . ' kunjungan tercatat nihil temuan ('
                . round($r['jmlNihil'] / $r['jmlKunjungan'] * 100) . '%).';
        }

        // Pembanding yang tidak setara harus disebut, bukan disembunyikan.
        if (($r['grandLegacy'] > 0) !== ($prev['grandLegacy'] > 0) || $prev['legacyTerpotong']) {
            $out[] = 'Perhatian: sumber data periode ini dan pembandingnya tidak setara (rekap impor bulanan vs kunjungan harian'
                . ($prev['legacyTerpotong'] ? ', sebagian rekap impor pembanding tidak dihitung karena terpotong' : '')
                . ') — baca persentase perubahan dengan hati-hati.';
        }
        return $out;
    }
}
