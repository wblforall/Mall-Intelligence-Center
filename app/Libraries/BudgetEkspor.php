<?php

namespace App\Libraries;

use App\Libraries\BudgetXlsx as X;

/**
 * Susun lembar-lembar Excel Budget Departemen (murni, tanpa DB) untuk BudgetXlsx.
 *
 * Lembar: Rekap · Per Bulan · Rincian Budget · Realisasi. Angka per pos/bulan
 * ditulis sebagai nilai; kolom turunan (kenaikan, sisa, persen) dan baris
 * Total ditulis sebagai RUMUS Excel (dengan nilai tersimpan), supaya file
 * tetap benar bila pengguna mengubah angkanya di Excel.
 */
class BudgetEkspor
{
    const BLN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @param array      $info      ['dept' => nama, 'tahun' => int, 'oleh' => nama pengunduh, 'waktu' => 'd/m/Y H:i']
     * @param array      $rekap     BudgetRekap::susun(..., 'semua', 12, $budgetLalu)
     * @param array|null $perMal    ['ewalk' => susun(...), 'pentacity' => susun(...)] bila dept memisah mal
     * @param array      $rencana   baris budget_rencana tahun ini (pos_id, bulan, mall, keterangan, jumlah)
     * @param array      $realisasi baris budget_realisasi tahun ini (+ pos_nama, pembuat), urut tanggal naik
     */
    public static function lembar(array $info, array $rekap, ?array $perMal, array $rencana, array $realisasi): array
    {
        return [
            self::rekap($info, $rekap, $perMal),
            self::perBulan($info, $rekap, $perMal),
            self::rincian($info, $rekap, $rencana),
            self::realisasi($info, $realisasi),
        ];
    }

    private static function kepalaLembar(array $info, string $judul): array
    {
        return [
            [['v' => $judul . ' — ' . $info['dept'] . ' ' . $info['tahun'], 's' => X::JUDUL]],
            [['v' => 'Diunduh dari MIC ' . $info['waktu'] . ' oleh ' . $info['oleh'] . ' · angka dalam Rupiah', 's' => X::CATAT]],
            [],
        ];
    }

    private static function n(float $v, int $s = X::ANGKA): array
    {
        return ['v' => round($v, 2), 's' => $s];
    }

    /** Sel rumus dengan nilai tersimpan. */
    private static function f(string $rumus, ?float $nilai, int $s): array
    {
        return ['f' => $rumus, 'v' => $nilai === null ? null : round($nilai, 6), 's' => $s];
    }

    // ── Rekap ────────────────────────────────────────────────────────

    private static function rekap(array $info, array $rekap, ?array $perMal): array
    {
        $t = (int) $info['tahun'];
        $rows = self::kepalaLembar($info, 'Rekap Budget');
        $kepala = ['No', 'Pos', "Budget $t", 'Budget ' . ($t - 1), 'Kenaikan', 'Kenaikan %', 'Realisasi', 'Terpakai %', "Sisa $t", 'Realisasi ' . ($t - 1)];
        if ($perMal) array_push($kepala, 'Budget eWalk', 'Budget Pentacity', 'Realisasi eWalk', 'Realisasi Pentacity');
        $kepala[] = 'Keterangan Pos';
        $rows[] = array_map(fn ($k) => ['v' => $k, 's' => X::KEPALA], $kepala);

        $awal = count($rows) + 1;               // baris Excel pertama data
        $no = 0;
        foreach ($rekap['baris'] as $id => $b) {
            $r = count($rows) + 1;
            $nama = $b['pos']['nama'] . (empty($b['pos']['aktif']) ? ' (nonaktif)' : '');
            $baris = [
                ['v' => ++$no, 's' => X::TEKS], ['v' => $nama, 's' => X::TEKS],
                self::n($b['budget']), self::n($b['budgetLalu']),
                self::f("C$r-D$r", $b['naik'], X::ANGKA),
                self::f("IF(D$r=0,\"\",E$r/D$r)", $b['naikPct'] === null ? null : $b['naikPct'] / 100, X::PERSEN),
                self::n($b['realisasi'], $b['realisasi'] > $b['budget'] ? X::LEWAT : X::ANGKA),
                self::f("IF(C$r=0,\"\",G$r/C$r)", $b['pct'] === null ? null : $b['pct'] / 100, X::PERSEN),
                self::f("C$r-G$r", $b['sisa'], $b['sisa'] < 0 ? X::LEWAT : X::ANGKA),
                self::n($b['lalu']),
            ];
            if ($perMal) {
                $e = $perMal['ewalk']['baris'][$id] ?? null; $p = $perMal['pentacity']['baris'][$id] ?? null;
                array_push($baris, self::n($e['budget'] ?? 0), self::n($p['budget'] ?? 0), self::n($e['realisasi'] ?? 0), self::n($p['realisasi'] ?? 0));
            }
            $baris[] = ['v' => (string) ($b['pos']['keterangan'] ?? ''), 's' => X::TEKS];
            $rows[] = $baris;
        }
        $akhir = count($rows);
        $r = $akhir + 1;
        $tt = $rekap['total'];
        $sum = fn (string $k, float $v) => self::f("SUM({$k}{$awal}:{$k}{$akhir})", $v, X::TOTALN);
        $total = [
            ['v' => '', 's' => X::TOTAL], ['v' => 'Total', 's' => X::TOTAL],
            $sum('C', $tt['budget']), $sum('D', $tt['budgetLalu']),
            self::f("C$r-D$r", $tt['naik'], X::TOTALN),
            self::f("IF(D$r=0,\"\",E$r/D$r)", $tt['naikPct'] === null ? null : $tt['naikPct'] / 100, X::TOTALP),
            $sum('G', $tt['realisasi']),
            self::f("IF(C$r=0,\"\",G$r/C$r)", $tt['pct'] === null ? null : $tt['pct'] / 100, X::TOTALP),
            self::f("C$r-G$r", $tt['sisa'], X::TOTALN),
            $sum('J', $tt['lalu']),
        ];
        if ($perMal) {
            array_push($total, $sum('K', $perMal['ewalk']['total']['budget']), $sum('L', $perMal['pentacity']['total']['budget']),
                $sum('M', $perMal['ewalk']['total']['realisasi']), $sum('N', $perMal['pentacity']['total']['realisasi']));
        }
        $total[] = ['v' => '', 's' => X::TOTAL];
        $rows[] = $total;
        $rows[] = [];
        $rows[] = [['v' => 'Realisasi dihitung menurut bulan pembebanan' . ($perMal ? '; transaksi "Keduanya" dibagi 50:50 ke eWalk dan Pentacity.' : '.'), 's' => X::CATAT]];

        return ['name' => 'Rekap', 'rows' => $rows, 'freeze' => 'C5',
                'widths' => array_merge([5, 38], array_fill(0, count($kepala) - 3, 16), [50])];
    }

    // ── Per Bulan ────────────────────────────────────────────────────

    private static function perBulan(array $info, array $rekap, ?array $perMal): array
    {
        $rows  = self::kepalaLembar($info, 'Budget & Realisasi per Bulan');
        $merge = [];
        $k1 = count($rows) + 1;                 // baris kepala pertama (Excel)
        // Kolom per bulan: tanpa pisah mal = Budget, Realisasi; pisah mal = eWalk B/R, Penta B/R.
        $sub = $perMal ? [['ewalk', 'b'], ['ewalk', 'r'], ['pentacity', 'b'], ['pentacity', 'r']] : [[null, 'b'], [null, 'r']];
        $lebar = count($sub);

        $h1 = [['v' => 'No', 's' => X::KEPALA], ['v' => 'Pos', 's' => X::KEPALA]];
        $h2 = [['v' => '', 's' => X::KEPALA], ['v' => '', 's' => X::KEPALA]];
        $h3 = $perMal ? $h2 : null;
        $c = 2;
        foreach (self::BLN as $nm) {
            $h1[] = ['v' => $nm, 's' => X::KEPALA];
            for ($i = 1; $i < $lebar; $i++) $h1[] = ['v' => '', 's' => X::KEPALA];
            $merge[] = X::kolom($c) . $k1 . ':' . X::kolom($c + $lebar - 1) . $k1;
            foreach ($sub as $i => [$mk, $jenis]) {
                $lbl = $jenis === 'b' ? 'Budget' : 'Realisasi';
                if ($perMal) {
                    if ($i % 2 === 0) { $h2[] = ['v' => $mk === 'ewalk' ? 'eWalk' : 'Pentacity', 's' => X::KEPALA]; $h2[] = ['v' => '', 's' => X::KEPALA];
                        $merge[] = X::kolom($c + $i) . ($k1 + 1) . ':' . X::kolom($c + $i + 1) . ($k1 + 1); }
                    $h3[] = ['v' => $lbl, 's' => X::KEPALA];
                } else {
                    $h2[] = ['v' => $lbl, 's' => X::KEPALA];
                }
            }
            $c += $lebar;
        }
        $tot = $c;                               // kolom Total Budget
        foreach (['Total Budget', 'Total Realisasi', 'Sisa'] as $i => $lbl) {
            $h1[] = ['v' => $lbl, 's' => X::KEPALA];
            $h2[] = ['v' => '', 's' => X::KEPALA];
            if ($h3) $h3[] = ['v' => '', 's' => X::KEPALA];
            $merge[] = X::kolom($tot + $i) . $k1 . ':' . X::kolom($tot + $i) . ($k1 + ($perMal ? 2 : 1));
        }
        $merge[] = 'A' . $k1 . ':A' . ($k1 + ($perMal ? 2 : 1));
        $merge[] = 'B' . $k1 . ':B' . ($k1 + ($perMal ? 2 : 1));
        $rows[] = $h1; $rows[] = $h2; if ($h3) $rows[] = $h3;

        // Kolom budget & realisasi (untuk rumus total baris)
        $kolB = $kolR = [];
        foreach (range(0, 11) as $m) foreach ($sub as $i => [, $jenis]) {
            $col = X::kolom(2 + $m * $lebar + $i);
            if ($jenis === 'b') $kolB[] = $col; else $kolR[] = $col;
        }
        $sel = fn (array $s, string $jenis) => self::n($jenis === 'b' ? $s['b'] : $s['r'], $jenis === 'r' && $s['r'] > $s['b'] ? X::LEWAT : X::ANGKA);

        $awal = count($rows) + 1; $no = 0; $komentar = [];
        foreach ($rekap['baris'] as $id => $b) {
            $r = count($rows) + 1;
            $baris = [['v' => ++$no, 's' => X::TEKS], ['v' => $b['pos']['nama'], 's' => X::TEKS]];
            foreach (range(1, 12) as $m) foreach ($sub as [$mk, $jenis]) {
                $s = $mk ? ($perMal[$mk]['baris'][$id]['bulan'][$m] ?? ['b' => 0, 'r' => 0, 'ket' => []]) : $b['bulan'][$m];
                // Rincian berketerangan (mis. "Imlek: 50.000.000") jadi komentar sel budget — seperti tooltip di layar.
                if ($jenis === 'b' && ! empty($s['ket'])) {
                    $komentar[X::kolom(count($baris)) . $r] = implode("\n", array_map(fn ($x) => $x['k'] . ': ' . number_format($x['j'], 0, ',', '.')
                        . (! $mk && isset(BudgetRekap::MAL[$x['mall']]) ? ' (' . BudgetRekap::MAL[$x['mall']] . ')' : ''), $s['ket']));
                }
                $baris[] = $sel($s, $jenis);
            }
            // Pisah mal: total dari gabungan (termasuk baris tanpa mal), bukan jumlah kolom mal.
            $baris[] = $perMal ? self::n($b['budget']) : self::f(implode('+', array_map(fn ($k) => $k . $r, $kolB)), $b['budget'], X::ANGKA);
            $baris[] = $perMal ? self::n($b['realisasi']) : self::f(implode('+', array_map(fn ($k) => $k . $r, $kolR)), $b['realisasi'], X::ANGKA);
            $baris[] = self::f(X::kolom($tot) . $r . '-' . X::kolom($tot + 1) . $r, $b['sisa'], $b['sisa'] < 0 ? X::LEWAT : X::ANGKA);
            $rows[] = $baris;
        }
        $akhir = count($rows); $r = $akhir + 1;
        $total = [['v' => '', 's' => X::TOTAL], ['v' => 'Total', 's' => X::TOTAL]];
        $tt = $rekap['total'];
        foreach (range(1, 12) as $m) foreach ($sub as $i => [$mk, $jenis]) {
            $s = $mk ? $perMal[$mk]['total']['bulan'][$m] : $tt['bulan'][$m];
            $col = X::kolom(2 + ($m - 1) * $lebar + $i);
            $total[] = self::f("SUM({$col}{$awal}:{$col}{$akhir})", $jenis === 'b' ? $s['b'] : $s['r'], X::TOTALN);
        }
        foreach ([[$tot, $tt['budget']], [$tot + 1, $tt['realisasi']]] as [$ci, $v]) {
            $col = X::kolom($ci);
            $total[] = self::f("SUM({$col}{$awal}:{$col}{$akhir})", $v, X::TOTALN);
        }
        $total[] = self::f(X::kolom($tot) . $r . '-' . X::kolom($tot + 1) . $r, $tt['sisa'], X::TOTALN);
        $rows[] = $total;

        return ['name' => 'Per Bulan', 'rows' => $rows, 'merge' => $merge, 'freeze' => 'C' . $awal, 'komentar' => $komentar,
                'widths' => array_merge([5, 34], array_fill(0, 12 * $lebar + 3, 14))];
    }

    // ── Rincian Budget ───────────────────────────────────────────────

    private static function rincian(array $info, array $rekap, array $rencana): array
    {
        $rows = self::kepalaLembar($info, 'Rincian Budget');
        $rows[] = array_map(fn ($k) => ['v' => $k, 's' => X::KEPALA], ['No', 'Pos', 'Bulan', 'Mal', 'Keterangan', 'Jumlah']);

        $urut = array_flip(array_keys($rekap['baris']));
        $rencana = array_values(array_filter($rencana, fn ($x) => isset($urut[(int) $x['pos_id']]) && (float) $x['jumlah'] != 0));
        usort($rencana, fn ($a, $b) => [$urut[(int) $a['pos_id']], (int) $a['bulan'], (string) $a['mall']]
                                   <=> [$urut[(int) $b['pos_id']], (int) $b['bulan'], (string) $b['mall']]);
        $awal = count($rows) + 1; $no = 0;
        foreach ($rencana as $x) {
            $rows[] = [
                ['v' => ++$no, 's' => X::TEKS],
                ['v' => $rekap['baris'][(int) $x['pos_id']]['pos']['nama'], 's' => X::TEKS],
                ['v' => self::BLN[(int) $x['bulan']], 's' => X::TEKS],
                ['v' => BudgetRekap::MAL[$x['mall']] ?? '-', 's' => X::TEKS],
                ['v' => (string) ($x['keterangan'] ?? ''), 's' => X::TEKS],
                self::n((float) $x['jumlah']),
            ];
        }
        $akhir = count($rows);
        $rows[] = [['v' => '', 's' => X::TOTAL], ['v' => 'Total', 's' => X::TOTAL], ['v' => '', 's' => X::TOTAL], ['v' => '', 's' => X::TOTAL], ['v' => '', 's' => X::TOTAL],
                   self::f($akhir >= $awal ? "SUM(F{$awal}:F{$akhir})" : '0', array_sum(array_column($rencana, 'jumlah')), X::TOTALN)];

        return ['name' => 'Rincian Budget', 'rows' => $rows, 'freeze' => 'A' . $awal, 'widths' => [5, 34, 8, 11, 50, 16]];
    }

    // ── Realisasi ────────────────────────────────────────────────────

    private static function realisasi(array $info, array $realisasi): array
    {
        $rows = self::kepalaLembar($info, 'Realisasi');
        $rows[] = array_map(fn ($k) => ['v' => $k, 's' => X::KEPALA],
            ['No', 'Tanggal', 'Periode Asal', 'Pos', 'Uraian', 'Keterangan', 'Mal', 'Nilai', 'Referensi', 'Dicatat oleh']);
        $awal = count($rows) + 1; $no = 0;
        foreach ($realisasi as $x) {
            $asal = (string) ($x['periode_asal'] ?? '');
            if (preg_match('/^(\d{4})-(\d{2})/', $asal, $m)) $asal = self::BLN[(int) $m[2]] . ' ' . $m[1];
            $rows[] = [
                ['v' => ++$no, 's' => X::TEKS],
                ['v' => self::serialTanggal((string) $x['tanggal']), 's' => X::TGL],
                ['v' => $asal, 's' => X::TEKS],
                ['v' => (string) $x['pos_nama'], 's' => X::TEKS],
                ['v' => (string) ($x['uraian'] ?? ''), 's' => X::TEKS],
                ['v' => (string) ($x['keterangan'] ?? ''), 's' => X::TEKS],
                ['v' => BudgetRekap::MAL_REALISASI[$x['mall'] ?? ''] ?? '-', 's' => X::TEKS],
                self::n((float) $x['nilai']),
                ['v' => (string) ($x['referensi'] ?? ''), 's' => X::TEKS],
                ['v' => (string) ($x['pembuat'] ?? ''), 's' => X::TEKS],
            ];
        }
        $akhir = count($rows);
        $kosong = ['v' => '', 's' => X::TOTAL];
        $rows[] = [$kosong, ['v' => 'Total', 's' => X::TOTAL], $kosong, $kosong, $kosong, $kosong, $kosong,
                   self::f($akhir >= $awal ? "SUM(H{$awal}:H{$akhir})" : '0', array_sum(array_map(fn ($x) => (float) $x['nilai'], $realisasi)), X::TOTALN),
                   $kosong, $kosong];

        return ['name' => 'Realisasi', 'rows' => $rows, 'freeze' => 'A' . $awal,
                'widths' => [5, 11, 11, 30, 42, 36, 16, 15, 18, 16]];
    }

    /** 'YYYY-MM-DD' → nomor seri tanggal Excel (1900-system). */
    public static function serialTanggal(string $tgl): int|string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tgl, $m)) return $tgl;
        return (int) (gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]) / 86400) + 25569;
    }
}
