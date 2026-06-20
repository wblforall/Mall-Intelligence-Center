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

        $daily = $spi->fetchDailyQty($start, $end);
        $stat  = $spi->fetchStatistik($start, $end); // duration + paid/free per jenis

        $types  = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $byType = array_fill_keys($types, 0);
        $grand  = 0;
        foreach ($daily as $row) {
            foreach ($types as $t) { $byType[$t] += $row[$t]; }
            $grand += $row['total'];
        }

        return view('parking/vehicles_summary', [
            'title'      => 'Summary — Traffic Kendaraan',
            'start'      => $start,
            'end'        => $end,
            'daily'      => $daily,
            'duration'   => $stat['duration'],
            'paid'       => $stat['paid'],
            'free'       => $stat['free'],
            'byType'     => $byType,
            'grandTotal' => $grand,
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
