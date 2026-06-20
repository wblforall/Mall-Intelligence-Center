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

        // Payment method history (arsip lokal, diisi maju oleh mic:spi-sync) untuk rentang
        $db = \Config\Database::connect();
        $payRows = [];
        if ($db->tableExists('spi_payment_daily')) {
            $payRows = $db->table('spi_payment_daily')
                ->select('method, SUM(amount) AS total')
                ->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->groupBy('method')->orderBy('total', 'DESC')
                ->get()->getResultArray();
        }
        $payDays = 0;
        if ($db->tableExists('spi_payment_daily')) {
            $payDays = (int) $db->table('spi_payment_daily')
                ->where('tanggal >=', $start)->where('tanggal <=', $end)
                ->select('tanggal')->distinct()->countAllResults();
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
            'payRows'   => $payRows,
            'payDays'   => $payDays,
            'yearly'    => $yearly,
            'stats'     => $stats,
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
