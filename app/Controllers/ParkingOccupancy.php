<?php

namespace App\Controllers;

/**
 * Okupansi Intraday & Rekonsiliasi — dari rekaman snapshot live (spi_live_snapshot).
 * Tren kepadatan per jam (mobil/motor/total), heatmap hari×jam, dan banding angka
 * rekaman EOD vs data final SPI (spi_income_daily) saat sudah masuk.
 * Akses: parking_vehicles (okupansi) / parking_revenue (income & rekonsiliasi).
 */
class ParkingOccupancy extends BaseController
{
    public function index()
    {
        $canVeh = $this->canViewMenu('parking_vehicles');
        $canRev = $this->canViewMenu('parking_revenue');
        if (! $canVeh && ! $canRev) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('spi_live_snapshot')) {
            return view('parking/occupancy', ['empty' => true, 'canVeh' => $canVeh, 'canRev' => $canRev,
                'title' => 'Okupansi Intraday — Parkir', 'date' => date('Y-m-d'),
                'points' => [], 'peak' => ['total' => 0, 't' => null], 'days' => [], 'heat' => [],
                'heatM' => [], 'flowDay' => [], 'gates' => ['masuk' => []], 'recon' => []]);
        }

        // Hari yang punya rekaman (untuk dropdown + ringkasan)
        $days = $db->table('spi_live_snapshot')
            ->select('tanggal, COUNT(*) AS n, MAX(total_in) AS peak')
            ->groupBy('tanggal')->orderBy('tanggal', 'DESC')->limit(60)->get()->getResultArray();

        $date = $this->request->getGet('date') ?: ($days[0]['tanggal'] ?? date('Y-m-d'));

        // Seri intraday untuk tanggal terpilih
        $rows = $db->table('spi_live_snapshot')->where('tanggal', $date)
            ->orderBy('captured_at', 'ASC')->get()->getResultArray();
        $points = array_map(fn($r) => [
            't'         => date('H:i', strtotime($r['captured_at'])),
            'mobil'     => (int) $r['mobil_in'],
            'motor'     => (int) $r['motor_in'],
            'other'     => (int) ($r['other_in'] ?? 0),
            'total'     => (int) $r['total_in'],
            'income'    => (int) $r['total_income'],
            'lotMobil'  => (int) $r['lot_mobil_avail'],
            'lotMotor'  => (int) $r['lot_motor_avail'],
        ], $rows);

        $peak = ['total' => 0, 't' => null];
        $peakInc = 0;
        foreach ($points as $p) {
            if ($p['total'] > $peak['total']) { $peak = ['total' => $p['total'], 't' => $p['t']]; }
            if ($p['income'] > $peakInc) { $peakInc = $p['income']; }
        }

        // Heatmap rata-rata okupansi: hari(1=Min..7=Sab) × jam(0..23)
        $heatRows = $db->query('SELECT DAYOFWEEK(tanggal) dow, HOUR(captured_at) hr, ROUND(AVG(total_in)) v '
            . 'FROM spi_live_snapshot GROUP BY dow, hr')->getResultArray();
        $heat = []; // [dow][hr] = v
        foreach ($heatRows as $h) { $heat[(int) $h['dow']][(int) $h['hr']] = (int) $h['v']; }

        // Okupansi awal tiap jam (snapshot pertama per jam) utk tanggal terpilih — basis derivasi keluar.
        // Counter KELUAR mentah SPI tidak reliable (dobel-scan gerbang) → kita turunkan sendiri:
        //   keluar = masuk − Δokupansi   (kekekalan kendaraan). Masuk & okupansi reliable.
        $occByHour = []; $lastOcc = null;
        foreach ($db->query(
            'SELECT HOUR(captured_at) hr, total_in FROM spi_live_snapshot s WHERE tanggal = ? '
            . 'AND captured_at = (SELECT MIN(captured_at) FROM spi_live_snapshot WHERE tanggal = ? AND HOUR(captured_at) = HOUR(s.captured_at)) '
            . 'ORDER BY hr', [$date, $date]
        )->getResultArray() as $r) {
            $occByHour[(int) $r['hr']] = (int) $r['total_in'];
        }
        $lastRow = $db->query('SELECT total_in FROM spi_live_snapshot WHERE tanggal = ? ORDER BY captured_at DESC LIMIT 1', [$date])->getRowArray();
        $lastOcc = $lastRow ? (int) $lastRow['total_in'] : null;

        // Arus masuk per jam (tanggal terpilih) + keluar-estimasi (masuk − Δokupansi) + heatmap masuk (dow × jam)
        $flowDay = []; $heatM = [];
        if ($db->tableExists('spi_hourly_flow')) {
            foreach ($db->table('spi_hourly_flow')->where('tanggal', $date)->orderBy('jam', 'ASC')->get()->getResultArray() as $r) {
                $h = (int) $r['jam']; $masuk = (int) $r['masuk'];
                $occH    = $occByHour[$h] ?? null;
                $occNext = $occByHour[$h + 1] ?? $lastOcc;   // awal jam berikut, atau snapshot terakhir utk jam berjalan
                $keluarEst = ($occH !== null && $occNext !== null) ? max(0, $masuk - ($occNext - $occH)) : null;
                $flowDay[] = ['jam' => $h, 'masuk' => $masuk, 'keluarEst' => $keluarEst];
            }
            foreach ($db->query('SELECT DAYOFWEEK(tanggal) dow, jam, ROUND(AVG(masuk)) m '
                . 'FROM spi_hourly_flow GROUP BY dow, jam')->getResultArray() as $h) {
                $heatM[(int) $h['dow']][(int) $h['jam']] = (int) $h['m'];
            }
        }

        // Per pintu (gate) MASUK saja untuk tanggal terpilih.
        // Gate KELUAR tak ditampilkan: counter keluar SPI tak reliable & tak bisa diderivasi per-gerbang.
        $gates = ['masuk' => []];
        if ($db->tableExists('spi_gate_daily')) {
            foreach ($db->table('spi_gate_daily')->where('tanggal', $date)->where('arah', 'masuk')->orderBy('jumlah', 'DESC')->get()->getResultArray() as $r) {
                $gates['masuk'][] = ['gate' => $r['gate'], 'jumlah' => (int) $r['jumlah']];
            }
        }

        // Rekonsiliasi: EOD rekaman (MAX income harian) vs SPI final (spi_income_daily)
        $recon = [];
        if ($canRev) {
            $hasIncome = $db->tableExists('spi_income_daily');
            $recon = $db->query(
                'SELECT s.tanggal, MAX(s.total_income) our_income'
                . ($hasIncome ? ', (SELECT total FROM spi_income_daily d WHERE d.tanggal = s.tanggal) spi_income' : ', NULL spi_income')
                . ' FROM spi_live_snapshot s GROUP BY s.tanggal ORDER BY s.tanggal DESC LIMIT 30'
            )->getResultArray();
        }

        return view('parking/occupancy', [
            'title'   => 'Okupansi Intraday — Parkir',
            'empty'   => empty($days),
            'canVeh'  => $canVeh,
            'canRev'  => $canRev,
            'date'    => $date,
            'days'    => $days,
            'points'  => $points,
            'peak'    => $peak,
            'peakInc' => $peakInc,
            'heat'    => $heat,
            'heatM'   => $heatM,
            'flowDay' => $flowDay,
            'gates'   => $gates,
            'recon'   => $recon,
        ]);
    }
}
