<?php

namespace App\Controllers;

use App\Services\SpiReportingService;

/**
 * Dashboard Traffic Kendaraan parkir (read-only dari SPI).
 * TIDAK menampilkan angka rupiah sama sekali — murni jumlah kendaraan.
 * Akses: menu key 'parking_vehicles'.
 *
 * Dua halaman terpisah:
 *  - live()    : okupansi real-time
 *  - summary() : tren/historis (filter tanggal)
 */
class ParkingVehicles extends BaseController
{
    private function guard()
    {
        if (! $this->canViewMenu('parking_vehicles')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    /** Default → arahkan ke Summary (live kini di halaman gabungan /parking/live). */
    public function index()
    {
        return redirect()->to('parking/vehicles/summary');
    }

    /** Halaman SUMMARY — tren/historis kendaraan. */
    public function summary()
    {
        if ($r = $this->guard()) { return $r; }

        [$start, $end] = $this->range();
        $spi = new SpiReportingService();
        $db  = \Config\Database::connect();

        $types  = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];

        // Daily dari salinan lokal (cepat); fallback LIVE bila DB kosong.
        $daily = ($db->tableExists('spi_vehicle_daily')
            ? $db->table('spi_vehicle_daily')->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->orderBy('tanggal', 'ASC')->get()->getResultArray()
            : []) ?: $spi->fetchDailyQty($start, $end);

        // Bayar vs Langganan per jenis + ringkasan harian — 100% dari DB (free per jenis kolom *_free).
        $byType = array_fill_keys($types, 0);
        $paid   = array_fill_keys($types, 0);
        $free   = array_fill_keys($types, 0);
        $grand  = 0;
        $vals = []; $weekday = 0; $weekend = 0; $peakDay = null; $peakVal = 0; $freeTot = 0;
        foreach ($daily as $row) {
            $tot = (int) ($row['total'] ?? 0);
            $grand += $tot;
            foreach ($types as $t) {
                $qty = (int) ($row[$t] ?? 0);
                // langganan/pass per jenis (statistik.php); cap ≤ qty agar Bayar+Langganan = total komposisi
                $ft  = min((int) ($row[$t . '_free'] ?? 0), $qty);
                $byType[$t] += $qty;
                $free[$t]   += $ft;
                $paid[$t]   += $qty - $ft;
                $freeTot    += $ft;
            }
            $n = ! empty($row['tanggal']) ? (int) date('N', strtotime($row['tanggal'])) : 0;
            if ($n >= 1 && $n <= 4)     { $weekday += $tot; }
            elseif ($n >= 5 && $n <= 7) { $weekend += $tot; }
            if ($tot > 0) {
                $vals[] = $tot;
                if ($tot > $peakVal) { $peakVal = $tot; $peakDay = $row['tanggal']; }
            }
        }
        $stats = [
            'avg'     => $vals ? (int) round(array_sum($vals) / count($vals)) : 0,
            'days'    => count($vals),
            'peakDay' => $peakDay, 'peakVal' => $peakVal,
            'weekday' => $weekday, 'weekend' => $weekend,
            'free'    => $freeTot, 'paid' => max(0, $grand - $freeTot),
        ];

        // Distribusi lama parkir per HARI dari DB (spi_duration_daily) — dijumlah eksak utk rentang.
        $durKeys  = ['le1', 'h1_2', 'h2_3', 'h3_4', 'h4_5', 'h5_6', 'h6_7', 'gt7'];
        $duration = array_fill_keys($durKeys, 0);
        if ($db->tableExists('spi_duration_daily')) {
            foreach ($db->table('spi_duration_daily')
                ->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->get()->getResultArray() as $dr) {
                foreach ($durKeys as $k) { $duration[$k] += (int) ($dr[$k] ?? 0); }
            }
        }
        $durationOk = array_sum($duration) > 0;

        return view('parking/vehicles_summary', [
            'title'      => 'Summary — Traffic Kendaraan',
            'start'      => $start,
            'end'        => $end,
            'daily'      => $daily,
            'duration'   => $duration,
            'paid'       => $paid,
            'free'       => $free,
            'byType'     => $byType,
            'grandTotal' => $grand,
            'stats'      => $stats,
            'durationOk' => $durationOk,
            'dataUntil'  => $spi->latestDataDate(),
        ]);
    }

    /** Rentang tanggal dari query (?start=&end=), default: awal bulan → hari ini. */
    private function range(): array
    {
        $start = $this->request->getGet('start') ?: date('Y-m-01');
        $end   = $this->request->getGet('end')   ?: date('Y-m-d');
        if ($start > $end) { [$start, $end] = [$end, $start]; }
        return [$start, $end];
    }

    // ── Laporan Bulanan Traffic Kendaraan Parkir (print, format formal) ───
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

        // Harian bulan terpilih + agregat per jenis (bayar vs langganan)
        $daily = $db->table('spi_vehicle_daily')
            ->where('tanggal >=', $from)->where('tanggal <=', $to)
            ->orderBy('tanggal')->get()->getResultArray();
        $dailyMap = array_column($daily, null, 'tanggal');

        $byType = array_fill_keys($types, 0);
        $paid   = array_fill_keys($types, 0);
        $free   = array_fill_keys($types, 0);
        $grand  = 0; $freeTot = 0;
        $wd = ['total' => 0, 'days' => 0]; $we = ['total' => 0, 'days' => 0];
        $peakDay = null; $peakVal = 0; $activeDays = 0;
        foreach ($daily as $row) {
            $tot = (int) ($row['total'] ?? 0);
            $grand += $tot;
            foreach ($types as $t) {
                $qty = (int) ($row[$t] ?? 0);
                $ft  = min((int) ($row[$t . '_free'] ?? 0), $qty);
                $byType[$t] += $qty;
                $free[$t]   += $ft;
                $paid[$t]   += $qty - $ft;
                $freeTot    += $ft;
            }
            $n = (int) date('N', strtotime($row['tanggal']));
            $b = $n <= 4 ? 'wd' : 'we';
            if ($b === 'wd') { $wd['total'] += $tot; if ($tot > 0) $wd['days']++; }
            else             { $we['total'] += $tot; if ($tot > 0) $we['days']++; }
            if ($tot > 0) {
                $activeDays++;
                if ($tot > $peakVal) { $peakVal = $tot; $peakDay = $row['tanggal']; }
            }
        }
        $avgDaily = $activeDays > 0 ? (int) round($grand / $activeDays) : 0;
        $wd['avg'] = $wd['days'] > 0 ? (int) round($wd['total'] / $wd['days']) : 0;
        $we['avg'] = $we['days'] > 0 ? (int) round($we['total'] / $we['days']) : 0;

        // Pembanding bulan lalu (per jenis + total + rata2)
        $sumMonth = function (string $b) use ($db, $types): array {
            $row = $db->table('spi_vehicle_daily')
                ->select(implode(', ', array_map(fn($t) => "SUM($t) AS $t", $types)) . ', SUM(total) AS total, SUM(total > 0) AS days')
                ->where("DATE_FORMAT(tanggal, '%Y-%m')", $b)
                ->get()->getRowArray() ?: [];
            return array_map('intval', array_merge(array_fill_keys($types, 0), ['total' => 0, 'days' => 0], array_filter($row, fn($v) => $v !== null)));
        };
        $prevByType = $sumMonth($prevBulan);
        $prevTotal  = $prevByType['total'];
        $prevAvg    = $prevByType['days'] > 0 ? (int) round($prevTotal / $prevByType['days']) : 0;
        $changePct    = $prevTotal > 0 ? round(($grand - $prevTotal) / $prevTotal * 100, 1) : null;
        $avgChangePct = $prevAvg > 0 ? round(($avgDaily - $prevAvg) / $prevAvg * 100, 1) : null;

        // Tren 6 bulan (mobil, motor, total)
        $trendFrom = date('Y-m', strtotime($from . ' -5 month'));
        $trendRows = $db->table('spi_vehicle_daily')
            ->select("DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(mobil) AS mobil, SUM(motor) AS motor, SUM(total) AS total")
            ->where("DATE_FORMAT(tanggal, '%Y-%m') >=", $trendFrom)
            ->where("DATE_FORMAT(tanggal, '%Y-%m') <=", $bulan)
            ->groupBy('bulan')->orderBy('bulan')->get()->getResultArray();
        $trendMap = array_column($trendRows, null, 'bulan');
        $trendMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime($from . ' -' . $i . ' month'));
            $trendMonths[] = [
                'bulan' => $m,
                'mobil' => (int) ($trendMap[$m]['mobil'] ?? 0),
                'motor' => (int) ($trendMap[$m]['motor'] ?? 0),
                'total' => (int) ($trendMap[$m]['total'] ?? 0),
            ];
        }

        // Distribusi lama parkir
        $durKeys  = ['le1', 'h1_2', 'h2_3', 'h3_4', 'h4_5', 'h5_6', 'h6_7', 'gt7'];
        $duration = array_fill_keys($durKeys, 0);
        foreach ($db->table('spi_duration_daily')
            ->where('tanggal >=', $from)->where('tanggal <=', $to)
            ->get()->getResultArray() as $dr) {
            foreach ($durKeys as $k) { $duration[$k] += (int) ($dr[$k] ?? 0); }
        }

        // Gate tersibuk (masuk & keluar) bulan terpilih
        $gateQuery = fn(string $arah) => $db->table('spi_gate_daily')
            ->select('gate, SUM(jumlah) AS total')
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->where('arah', $arah)
            ->groupBy('gate')->orderBy('total', 'DESC')
            ->get(8)->getResultArray();
        $gateMasuk  = $gateQuery('masuk');
        $gateKeluar = $gateQuery('keluar');

        // Hari tanpa data (s/d kemarin) — indikator sync SPI bolong
        $lastCheck   = min($to, date('Y-m-d', strtotime('-1 day')));
        $missingDays = 0;
        for ($d = $from; $d <= $lastCheck; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            if ((int)($dailyMap[$d]['total'] ?? 0) === 0) $missingDays++;
        }

        // Insight otomatis
        $insights = [];
        $insights[] = 'Total kendaraan masuk bulan ini ' . number_format($grand)
            . ($changePct !== null ? ' — ' . ($changePct >= 0 ? 'naik ' : 'turun ') . abs($changePct) . '% dari bulan lalu (' . number_format($prevTotal) . ').' : '.');
        $insights[] = 'Rata-rata harian ' . number_format($avgDaily) . ' kendaraan'
            . ($avgChangePct !== null ? ' (' . ($avgChangePct >= 0 ? 'naik ' : 'turun ') . abs($avgChangePct) . '% vs bulan lalu).' : '.');
        if ($grand > 0) {
            $insights[] = 'Komposisi: motor ' . round($byType['motor'] / $grand * 100) . '% · mobil ' . round($byType['mobil'] / $grand * 100)
                . '% · langganan/pass ' . round($freeTot / $grand * 100) . '% dari total.';
        }
        if ($peakDay) $insights[] = 'Hari teramai: ' . date('d M Y', strtotime($peakDay)) . ' (' . number_format($peakVal) . ' kendaraan).';
        if ($we['avg'] > 0 && $wd['avg'] > 0) {
            $insights[] = 'Rata-rata weekend ' . number_format($we['avg']) . '/hari vs weekday ' . number_format($wd['avg'])
                . '/hari (' . round($we['avg'] / max(1, $wd['avg']) * 100) . '%).';
        }
        if ($gateMasuk)  $insights[] = 'Gate masuk tersibuk: ' . $gateMasuk[0]['gate'] . ' (' . number_format((int)$gateMasuk[0]['total']) . ' kendaraan).';
        if ($missingDays > 0) $insights[] = '⚠ ' . $missingDays . ' hari belum ada data dari SPI — jalankan backfill mic:spi-sync agar laporan lengkap.';

        return view('parking/laporan_vehicles', [
            'bulan'        => $bulan,
            'prevBulan'    => $prevBulan,
            'types'        => $types,
            'byType'       => $byType,
            'paid'         => $paid,
            'free'         => $free,
            'freeTot'      => $freeTot,
            'grand'        => $grand,
            'prevByType'   => $prevByType,
            'prevTotal'    => $prevTotal,
            'changePct'    => $changePct,
            'avgDaily'     => $avgDaily,
            'avgChangePct' => $avgChangePct,
            'peakDay'      => $peakDay,
            'peakVal'      => $peakVal,
            'wd'           => $wd,
            'we'           => $we,
            'daily'        => $daily,
            'trendMonths'  => $trendMonths,
            'duration'     => $duration,
            'gateMasuk'    => $gateMasuk,
            'gateKeluar'   => $gateKeluar,
            'missingDays'  => $missingDays,
            'insights'     => $insights,
            // TTD = rantai dept Operational (pemilik modul parkir; dipetakan via menu 'traffic')
            'signatories'  => \App\Libraries\ReportSignatories::resolve('traffic'),
            'printedBy'    => $this->currentUser()['name'] ?? '',
            'printedAt'    => date('d M Y H:i'),
        ]);
    }
}
