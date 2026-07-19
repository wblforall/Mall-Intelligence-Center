<?php

namespace App\Controllers;

use App\Services\SpiReportingService;

/**
 * Dashboard Revenue Parkir (read-only dari SPI).
 * Sensitif — digate menu key 'parking_revenue' yang TERPISAH dari parking_vehicles.
 * Angka resmi memakai income-summary (basis tanggal bayar).
 *
 * Dua halaman terpisah:
 *  - live()    : income hari ini berjalan (real-time)
 *  - summary() : tren income historis (filter tanggal)
 */
class ParkingRevenue extends BaseController
{
    private function guard()
    {
        if (! $this->canViewMenu('parking_revenue')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    /** Default → arahkan ke Summary (live kini di halaman gabungan /parking/live). */
    public function index()
    {
        return redirect()->to('parking/revenue/summary');
    }

    /** Halaman SUMMARY — semua kemungkinan tampilan income historis. */
    public function summary()
    {
        if ($r = $this->guard()) { return $r; }

        [$start, $end] = $this->range();
        $spi = new SpiReportingService();
        $db  = \Config\Database::connect();
        $types = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $monStart = env('SPI_DATA_START', '2023-01-01');

        // ── Tren bulanan: dari DB (spi_income_monthly), fallback LIVE bila kosong ──
        // Sekaligus akumulasi KPI untuk PERIODE terpilih (bulan yang masuk rentang start..end).
        $casual = $member = $total = [];
        $pStartMon = substr($start, 0, 7);
        $pEndMon   = substr($end, 0, 7);
        $perCasual = $perMember = 0;
        $monRows = $db->tableExists('spi_income_monthly')
            ? $db->table('spi_income_monthly')->where('bulan >=', substr($monStart, 0, 7))
                ->orderBy('bulan', 'ASC')->get()->getResultArray()
            : [];
        // DB-only (read-only dashboard). Data diisi cron mic:spi-sync; tak ada fetch live
        // di halaman interaktif agar tidak pernah hang saat SPI lambat/proxy mati.
        foreach ($monRows as $r) {
            $label = date('M Y', strtotime($r['bulan'] . '-01'));
            $c = (int) $r['casual']; $m = (int) $r['member'];
            $casual[] = ['label' => $label, 'value' => $c];
            $member[] = ['label' => $label, 'value' => $m];
            $total[]  = ['label' => $label, 'value' => $c + $m];
            if ($r['bulan'] >= $pStartMon && $r['bulan'] <= $pEndMon) {
                $perCasual += $c; $perMember += $m;
            }
        }
        $perTotal  = $perCasual + $perMember;            // KPI periode terpilih
        $sumCasual = array_sum(array_column($casual, 'value')); // akumulasi all-time (utk tahunan)
        $sumMember = array_sum(array_column($member, 'value'));
        $sumTotal  = array_sum(array_column($total,  'value'));

        // ── Income harian + per jenis: dari DB (spi_income_daily), fallback LIVE ──
        $dayRows = $db->tableExists('spi_income_daily')
            ? $db->table('spi_income_daily')->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->orderBy('tanggal', 'ASC')->get()->getResultArray()
            : [];
        $daily = array_map(fn($r) => [
            'tanggal' => $r['tanggal'],
            'mobil' => (int) $r['mobil'], 'motor' => (int) $r['motor'], 'box' => (int) $r['box'],
            'truck' => (int) $r['truck'], 'taxi' => (int) $r['taxi'], 'bus' => (int) $r['bus'],
            'total' => (int) $r['total'],
        ], $dayRows);
        $byType = array_fill_keys($types, 0);
        $sum    = 0;
        foreach ($daily as $row) {
            foreach ($types as $t) { $byType[$t] += $row[$t]; }
            $sum += $row['total'];
        }

        // Income tahunan (agregasi dari tren bulanan Total)
        $yearly = [];
        foreach ($total as $p) {
            $ts = strtotime('1 ' . $p['label']);
            if (! $ts) { continue; }
            $y = date('Y', $ts);
            $yearly[$y] = ($yearly[$y] ?? 0) + $p['value'];
        }

        // Statistik harian untuk rentang terpilih
        $stats = ['avg' => 0, 'maxDay' => null, 'maxVal' => 0, 'minDay' => null, 'minVal' => 0, 'days' => 0];
        $vals = array_values(array_filter(array_map(fn($r) => $r['total'], $daily), fn($v) => $v > 0));
        if ($vals) {
            $stats['days']   = count($vals);
            $stats['avg']    = (int) round(array_sum($vals) / count($vals));
            $stats['maxVal'] = max($vals);
            $stats['minVal'] = min($vals);
            foreach ($daily as $r) {
                if ($r['total'] === $stats['maxVal'] && ! $stats['maxDay']) { $stats['maxDay'] = $r['tanggal']; }
                if ($r['total'] === $stats['minVal'] && $r['total'] > 0 && ! $stats['minDay']) { $stats['minDay'] = $r['tanggal']; }
            }
        }

        // Rincian metode pembayaran HISTORIS dari DB (spi_payment_daily, diisi mic:spi-sync)
        $payRows = $db->tableExists('spi_payment_daily')
            ? $db->table('spi_payment_daily')->select('method, SUM(amount) AS total')
                ->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->groupBy('method')->having('total >', 0)->orderBy('total', 'DESC')
                ->get()->getResultArray()
            : [];

        return view('parking/revenue_summary', [
            'title'     => 'Summary — Revenue Parkir',
            'start'     => $start,
            'end'       => $end,
            'casual'    => $casual,
            'member'    => $member,
            'total'     => $total,
            'sumCasual' => $sumCasual,
            'sumMember' => $sumMember,
            'sumTotal'  => $sumTotal,
            'perCasual' => $perCasual,
            'perMember' => $perMember,
            'perTotal'  => $perTotal,
            'daily'     => $daily,
            'byType'    => $byType,
            'sumPeriod' => $sum,
            'payRows'   => $payRows,
            'yearly'    => $yearly,
            'stats'     => $stats,
            'dataUntil' => $spi->latestDataDate(),
        ]);
    }

    private function range(): array
    {
        $start = $this->request->getGet('start') ?: date('Y-m-01');
        $end   = $this->request->getGet('end')   ?: date('Y-m-d');
        if ($start > $end) { [$start, $end] = [$end, $start]; }
        return [$start, $end];
    }

    // ── Laporan Bulanan Pendapatan Parkir (print, format formal) ──────────
    public function laporanBulanan()
    {
        if ($r = $this->guard()) { return $r; }

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');
        $from      = $bulan . '-01';
        $to        = date('Y-m-t', strtotime($from));
        $prevBulan = date('Y-m', strtotime($from . ' -1 month'));

        $db    = \Config\Database::connect();
        $types = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];

        $sumMonth = function (string $b) use ($db, $types): array {
            $row = $db->table('spi_income_daily')
                ->select(implode(', ', array_map(fn($t) => "SUM($t) AS $t", $types)) . ', SUM(total) AS total')
                ->where("DATE_FORMAT(tanggal, '%Y-%m')", $b)
                ->get()->getRowArray() ?: [];
            return array_map('intval', array_merge(array_fill_keys($types, 0), ['total' => 0], array_filter($row, fn($v) => $v !== null)));
        };
        $byType     = $sumMonth($bulan);
        $prevByType = $sumMonth($prevBulan);
        $total      = $byType['total'];
        $prevTotal  = $prevByType['total'];
        $changePct  = $prevTotal > 0 ? round(($total - $prevTotal) / $prevTotal * 100, 1) : null;

        // Harian bulan terpilih
        $daily = $db->table('spi_income_daily')
            ->where('tanggal >=', $from)->where('tanggal <=', $to)
            ->orderBy('tanggal')->get()->getResultArray();
        $dailyMap = array_column($daily, null, 'tanggal');

        $vals = array_values(array_filter(array_map(fn($r) => (int)$r['total'], $daily), fn($v) => $v > 0));
        $avgDaily = $vals ? (int) round(array_sum($vals) / count($vals)) : 0;
        $maxVal = $vals ? max($vals) : 0;
        $maxDay = null;
        foreach ($daily as $r) { if ((int)$r['total'] === $maxVal && $maxVal > 0) { $maxDay = $r['tanggal']; break; } }

        $prevDaily  = $db->table('spi_income_daily')->select('total')
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $prevBulan)->where('total >', 0)->get()->getResultArray();
        $prevAvg    = $prevDaily ? (int) round(array_sum(array_column($prevDaily, 'total')) / count($prevDaily)) : 0;
        $avgChangePct = $prevAvg > 0 ? round(($avgDaily - $prevAvg) / $prevAvg * 100, 1) : null;

        // Hari tanpa data (s/d kemarin utk bulan berjalan) — indikator sync SPI bolong
        $lastCheck   = min($to, date('Y-m-d', strtotime('-1 day')));
        $missingDays = 0;
        for ($d = $from; $d <= $lastCheck; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            if ((int)($dailyMap[$d]['total'] ?? 0) === 0) $missingDays++;
        }

        // Tren 6 bulan + casual vs member (spi_income_monthly)
        $trendFrom = date('Y-m', strtotime($from . ' -5 month'));
        $monRows   = $db->table('spi_income_monthly')
            ->where('bulan >=', $trendFrom)->where('bulan <=', $bulan)
            ->orderBy('bulan')->get()->getResultArray();
        $monMap = array_column($monRows, null, 'bulan');
        $trendMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime($from . ' -' . $i . ' month'));
            $trendMonths[] = [
                'bulan'  => $m,
                'casual' => (int) ($monMap[$m]['casual'] ?? 0),
                'member' => (int) ($monMap[$m]['member'] ?? 0),
            ];
        }
        $kpiCasual = (int) ($monMap[$bulan]['casual'] ?? 0);
        $kpiMember = (int) ($monMap[$bulan]['member'] ?? 0);

        // Metode pembayaran bulan terpilih
        $payments = $db->table('spi_payment_daily')
            ->select('method, SUM(amount) AS total')
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->groupBy('method')->having('total >', 0)->orderBy('total', 'DESC')
            ->get()->getResultArray();
        $payTotal = array_sum(array_map(fn($p) => (int)$p['total'], $payments));

        // Insight otomatis
        $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $insights = [];
        $insights[] = 'Pendapatan parkir bulan ini ' . $rp($total)
            . ($changePct !== null ? ' — ' . ($changePct >= 0 ? 'naik ' : 'turun ') . abs($changePct) . '% dari bulan lalu (' . $rp($prevTotal) . ').' : '.');
        $insights[] = 'Rata-rata harian ' . $rp($avgDaily)
            . ($avgChangePct !== null ? ' (' . ($avgChangePct >= 0 ? 'naik ' : 'turun ') . abs($avgChangePct) . '% vs bulan lalu).' : '.');
        if ($total > 0) {
            $insights[] = 'Kontributor utama: mobil ' . round($byType['mobil'] / $total * 100) . '% · motor ' . round($byType['motor'] / $total * 100) . '%.';
        }
        if ($kpiCasual + $kpiMember > 0) {
            $insights[] = 'Casual ' . $rp($kpiCasual) . ' (' . round($kpiCasual / max(1, $kpiCasual + $kpiMember) * 100) . '%) · member/langganan ' . $rp($kpiMember) . '.';
        }
        if ($payments) {
            $top = $payments[0];
            $insights[] = 'Metode pembayaran terbesar: ' . $top['method'] . ' (' . ($payTotal > 0 ? round((int)$top['total'] / $payTotal * 100) : 0) . '% dari total).';
        }
        if ($maxDay) $insights[] = 'Pendapatan tertinggi: ' . date('d M Y', strtotime($maxDay)) . ' (' . $rp($maxVal) . ').';
        if ($missingDays > 0) $insights[] = '⚠ ' . $missingDays . ' hari belum ada data dari SPI — jalankan backfill mic:spi-sync agar laporan lengkap.';

        return view('parking/laporan_revenue', [
            'bulan'        => $bulan,
            'prevBulan'    => $prevBulan,
            'types'        => $types,
            'byType'       => $byType,
            'prevByType'   => $prevByType,
            'total'        => $total,
            'prevTotal'    => $prevTotal,
            'changePct'    => $changePct,
            'avgDaily'     => $avgDaily,
            'avgChangePct' => $avgChangePct,
            'maxDay'       => $maxDay,
            'maxVal'       => $maxVal,
            'kpiCasual'    => $kpiCasual,
            'kpiMember'    => $kpiMember,
            'payments'     => $payments,
            'payTotal'     => $payTotal,
            'daily'        => $daily,
            'trendMonths'  => $trendMonths,
            'missingDays'  => $missingDays,
            'insights'     => $insights,
            // TTD = rantai dept Operational (pemilik modul parkir; dipetakan via menu 'traffic')
            'signatories'  => \App\Libraries\ReportSignatories::resolve('traffic'),
            'printedBy'    => $this->currentUser()['name'] ?? '',
            'printedAt'    => date('d M Y H:i'),
        ]);
    }
}
