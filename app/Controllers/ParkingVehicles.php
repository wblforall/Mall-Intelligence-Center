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
}
