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
            'title'  => 'Compare Periode — Parkir',
            'mode'   => $mode,
            'canVeh' => $canVeh,
            'canRev' => $canRev,
            'prev'   => $Prev, // lalu
            'cur'    => $Cur,  // kini
        ]);
    }

    /** Hitung agregat traffic & (opsional) revenue untuk satu periode. */
    private function aggregate(SpiReportingService $spi, array $p, bool $canVeh, bool $canRev): array
    {
        $types = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
        $out = ['label' => $p['label'], 'start' => $p['start'], 'end' => $p['end'],
            'traffic' => null, 'revenue' => null];

        if ($canVeh) {
            $byType = array_fill_keys($types, 0); $tot = 0;
            foreach ($spi->fetchDailyQty($p['start'], $p['end']) as $r) {
                foreach ($types as $t) { $byType[$t] += $r[$t]; }
                $tot += $r['total'];
            }
            $out['traffic'] = ['total' => $tot, 'byType' => $byType];
        }
        if ($canRev) {
            $byType = array_fill_keys($types, 0); $tot = 0;
            foreach ($spi->fetchDailyIncome($p['start'], $p['end']) as $r) {
                foreach ($types as $t) { $byType[$t] += $r[$t]; }
                $tot += $r['total'];
            }
            $out['revenue'] = ['total' => $tot, 'byType' => $byType];
        }
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
