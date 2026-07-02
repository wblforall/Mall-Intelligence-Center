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
}
