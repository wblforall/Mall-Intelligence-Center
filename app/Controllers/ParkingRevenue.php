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

        $monStart = env('SPI_DATA_START', '2023-01-01');
        $monEnd   = date('Y-m-t');
        $casual   = $spi->fetchMonthlyIncome($monStart, $monEnd, 0); // income=0
        $member   = $spi->fetchMonthlyIncome($monStart, $monEnd, 1); // income=1
        $total    = $spi->fetchMonthlyIncome($monStart, $monEnd, 2); // income=2 (casual+member)

        // Total periode terpilih per kategori (semua kemungkinan)
        $sumCasual = array_sum(array_column($casual, 'value'));
        $sumMember = array_sum(array_column($member, 'value'));
        $sumTotal  = array_sum(array_column($total,  'value'));

        $daily  = $spi->fetchDailyIncome($start, $end);
        $types  = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $byType = array_fill_keys($types, 0);
        $sum    = 0;
        foreach ($daily as $row) {
            foreach ($types as $t) { $byType[$t] += $row[$t]; }
            $sum += $row['total'];
        }

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
            'daily'     => $daily,
            'byType'    => $byType,
            'sumPeriod' => $sum,
        ]);
    }

    private function range(): array
    {
        $start = $this->request->getGet('start') ?: date('Y-m-01');
        $end   = $this->request->getGet('end')   ?: date('Y-m-d');
        if ($start > $end) { [$start, $end] = [$end, $start]; }
        return [$start, $end];
    }
}
