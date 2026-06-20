<?php

namespace App\Controllers;

use App\Services\SpiReportingService;

/**
 * Banding periode parkir — traffic + revenue sekaligus. Urutan tampil: LALU → KINI.
 * Mode:
 *  - 'mom'    : bulan lalu vs bulan ini
 *  - 'yoy'    : bulan ini tahun lalu vs tahun ini
 *  - 'custom' : dua periode bebas (start1/end1 = lalu, start2/end2 = kini)
 * Revenue hanya dihitung & ditampilkan bila user punya 'parking_revenue'.
 * Traffic ditampilkan bila punya 'parking_vehicles'.
 */
class ParkingCompare extends BaseController
{
    public function index()
    {
        $canVeh = $this->canViewMenu('parking_vehicles');
        $canRev = $this->canViewMenu('parking_revenue');
        if (! $canVeh && ! $canRev) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $mode = $this->request->getGet('mode') ?: 'mom';
        [$prev, $cur] = $this->periods($mode); // LALU, KINI

        $spi  = new SpiReportingService();
        $Prev = $this->aggregate($spi, $prev, $canVeh, $canRev);
        $Cur  = $this->aggregate($spi, $cur,  $canVeh, $canRev);

        return view('parking/compare', [
            'title'     => 'Compare Periode — Parkir',
            'mode'      => $mode,
            'canVeh'    => $canVeh,
            'canRev'    => $canRev,
            'prev'      => $Prev, // lalu
            'cur'       => $Cur,  // kini
            'dataUntil' => $spi->latestDataDate(),
        ]);
    }

    /** Hitung agregat traffic & (opsional) revenue untuk satu periode. */
    private function aggregate(SpiReportingService $spi, array $p, bool $canVeh, bool $canRev): array
    {
        $types = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $out = ['label' => $p['label'], 'start' => $p['start'], 'end' => $p['end'],
            'calDays' => (int) floor((strtotime($p['end']) - strtotime($p['start'])) / 86400) + 1,
            'traffic' => null, 'revenue' => null, 'payments' => []];

        if ($canVeh) {
            $rows = $this->dailyRows('spi_vehicle_daily', $p['start'], $p['end']); // DB-only (cepat)
            $byType = array_fill_keys($types, 0); $tot = 0; $free = 0;
            foreach ($rows as $r) {
                foreach ($types as $t) { $byType[$t] += (int) ($r[$t] ?? 0); }
                $tot  += (int) ($r['total'] ?? 0);
                $free += (int) ($r['mobil_free'] ?? 0) + (int) ($r['motor_free'] ?? 0);
            }
            $out['traffic'] = ['total' => $tot, 'byType' => $byType,
                'free' => $free, 'paid' => max(0, $tot - $free)] + $this->dayStats($rows);
        }
        if ($canRev) {
            $rows = $this->dailyRows('spi_income_daily', $p['start'], $p['end'])
                ?: $spi->fetchDailyIncome($p['start'], $p['end']); // fallback LIVE bila DB kosong
            $byType = array_fill_keys($types, 0); $tot = 0;
            foreach ($rows as $r) {
                foreach ($types as $t) { $byType[$t] += (int) ($r[$t] ?? 0); }
                $tot += (int) ($r['total'] ?? 0);
            }
            $split = $this->monthlySplit($p['start'], $p['end']);
            $out['revenue'] = ['total' => $tot, 'byType' => $byType,
                'casual' => $split['casual'], 'member' => $split['member']] + $this->dayStats($rows);
            $out['payments'] = $this->paymentMix($p['start'], $p['end']);
        }
        return $out;
    }

    /** Ambil baris harian dari salinan lokal SPI; [] bila tabel/baris kosong (→ trigger fallback LIVE). */
    private function dailyRows(string $table, string $start, string $end): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists($table)) { return []; }
        return $db->table($table)
            ->where('tanggal >=', $start)->where('tanggal <=', $end)
            ->orderBy('tanggal', 'ASC')->get()->getResultArray();
    }

    /**
     * Statistik harian dari kolom 'total': rata2/hari (hari berdata), hari puncak/terendah,
     * dan split weekday (Sen–Kam) vs weekend (Jum–Min).
     */
    private function dayStats(array $rows): array
    {
        $vals = []; $wd = 0; $we = 0;
        $peakDay = null; $peakVal = 0; $lowDay = null; $lowVal = 0;
        foreach ($rows as $r) {
            $v   = (int) ($r['total'] ?? 0);
            $tgl = $r['tanggal'] ?? '';
            $n   = $tgl ? (int) date('N', strtotime($tgl)) : 0; // 1=Sen .. 7=Min
            if ($n >= 1 && $n <= 4)      { $wd += $v; }
            elseif ($n >= 5 && $n <= 7)  { $we += $v; }
            if ($v > 0) {
                $vals[] = $v;
                if ($v > $peakVal)                    { $peakVal = $v; $peakDay = $tgl; }
                if ($lowVal === 0 || $v < $lowVal)    { $lowVal  = $v; $lowDay  = $tgl; }
            }
        }
        $days = count($vals);
        return [
            'avg'     => $days ? (int) round(array_sum($vals) / $days) : 0,
            'days'    => $days,
            'peakDay' => $peakDay, 'peakVal' => $peakVal,
            'lowDay'  => $lowDay,  'lowVal'  => $lowVal,
            'weekday' => $wd, 'weekend' => $we,
        ];
    }

    /** Casual & Member (rupiah) untuk periode — basis bulanan (SPI hanya sediakan split bulanan). */
    private function monthlySplit(string $start, string $end): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('spi_income_monthly')) { return ['casual' => 0, 'member' => 0]; }
        $rows = $db->table('spi_income_monthly')
            ->where('bulan >=', substr($start, 0, 7))->where('bulan <=', substr($end, 0, 7))
            ->get()->getResultArray();
        $c = 0; $m = 0;
        foreach ($rows as $r) { $c += (int) $r['casual']; $m += (int) $r['member']; }
        return ['casual' => $c, 'member' => $m];
    }

    /** Mix metode pembayaran (rupiah) untuk periode. method => total, urut desc. */
    private function paymentMix(string $start, string $end): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('spi_payment_daily')) { return []; }
        $rows = $db->table('spi_payment_daily')->select('method, SUM(amount) AS total')
            ->where('tanggal >=', $start)->where('tanggal <=', $end)
            ->groupBy('method')->having('total >', 0)->orderBy('total', 'DESC')
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) { $out[$r['method']] = (int) $r['total']; }
        return $out;
    }

    /** Tentukan dua rentang [LALU, KINI] berdasarkan mode. */
    private function periods(string $mode): array
    {
        $today = date('Y-m-d');
        $day   = (int) date('j');

        // KINI = bulan berjalan (1..hari ini) — default utk mom/yoy
        $cur = ['label' => date('M Y'), 'start' => date('Y-m-01'), 'end' => $today];

        if ($mode === 'custom') {
            $g = fn($k, $d) => $this->request->getGet($k) ?: $d;
            $prev = $this->mkPeriod($g('start1', date('Y-m-01', strtotime('-1 month'))),
                                    $g('end1',   date('Y-m-t',  strtotime('-1 month'))));
            $cur  = $this->mkPeriod($g('start2', date('Y-m-01')), $g('end2', $today));
            return [$prev, $cur];
        }

        if ($mode === 'yoy') {
            $ly = (int) date('Y') - 1;
            $m  = (int) date('n');
            $endDay = min($day, (int) date('t', mktime(0, 0, 0, $m, 1, $ly)));
            $prev = [
                'label' => date('M Y', mktime(0, 0, 0, $m, 1, $ly)),
                'start' => sprintf('%04d-%02d-01', $ly, $m),
                'end'   => sprintf('%04d-%02d-%02d', $ly, $m, $endDay),
            ];
        } else { // mom
            $pm = mktime(0, 0, 0, (int) date('n') - 1, 1, (int) date('Y'));
            $endDay = min($day, (int) date('t', $pm));
            $prev = [
                'label' => date('M Y', $pm),
                'start' => date('Y-m-01', $pm),
                'end'   => date('Y-m-', $pm) . sprintf('%02d', $endDay),
            ];
        }
        return [$prev, $cur];
    }

    private function mkPeriod(string $start, string $end): array
    {
        if ($start > $end) { [$start, $end] = [$end, $start]; }
        $label = date('d M Y', strtotime($start)) . ' – ' . date('d M Y', strtotime($end));
        return ['label' => $label, 'start' => $start, 'end' => $end];
    }
}
