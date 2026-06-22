<?php

namespace App\Controllers;

use App\Services\SpiReportingService;

/**
 * Halaman LIVE gabungan (real-time). Satu halaman menampilkan:
 *  - Okupansi kendaraan  → hanya bila punya 'parking_vehicles'
 *  - Income hari ini + payment methods → hanya bila punya 'parking_revenue'
 * Field rupiah di-strip server-side untuk user tanpa akses revenue.
 */
class ParkingLive extends BaseController
{
    public function index()
    {
        // Akses Live diatur sendiri lewat menu key 'parking_live' (independen dari veh/rev).
        if (! $this->canViewMenu('parking_live')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $canVeh = $this->canViewMenu('parking_vehicles'); // hanya untuk link silang Kendaraan
        $canRev = $this->canViewMenu('parking_revenue');  // hanya untuk link silang Revenue

        // TIDAK menarik SPI di sini (lambat) — okupansi render instan dgn nilai 0, diisi via AJAX.
        $zero = ['ok' => true, 'mobil' => 0, 'motor' => 0, 'total' => 0,
            'lot_mobil' => 0, 'lot_motor' => 0, 'lot_mobil_tersedia' => 0, 'lot_motor_tersedia' => 0];

        // Aktivitas per pintu MASUK hari ini (kumulatif, dari DB — diisi cron --flows ~30 mnt).
        // Pintu keluar tak ditampilkan: counter keluar SPI tak reliable (dobel-scan gerbang).
        $motorGates = SpiReportingService::GATE_MOTOR_MASUK;
        $mobilGates = SpiReportingService::GATE_MOBIL_MASUK;
        $gates = ['masuk' => ['motor' => [], 'mobil' => [], 'other' => []]];
        $db = \Config\Database::connect();
        if ($db->tableExists('spi_gate_daily')) {
            foreach ($db->table('spi_gate_daily')->where('tanggal', date('Y-m-d'))->where('arah', 'masuk')
                ->orderBy('jumlah', 'DESC')->get()->getResultArray() as $r) {
                $entry = ['gate' => $r['gate'], 'jumlah' => (int) $r['jumlah']];
                if (in_array($r['gate'], $motorGates))      $gates['masuk']['motor'][] = $entry;
                elseif (in_array($r['gate'], $mobilGates))  $gates['masuk']['mobil'][] = $entry;
                else                                         $gates['masuk']['other'][] = $entry;
            }
        }

        return view('parking/live', [
            'title'     => 'Live — Parkir',
            'canVeh'    => $canVeh,
            'canRev'    => $canRev,
            'live'      => $zero,
            'gates'     => $gates,
            'dataUntil' => (new SpiReportingService())->latestDataDate(),
        ]);
    }

    /** JSON live — okupansi real-time (income/payment tidak lagi di Live). */
    public function data()
    {
        if (! $this->canViewMenu('parking_live')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }
        $live = (new SpiReportingService())->fetchLive();
        return $this->response->setJSON([
            'ok'                 => $live['ok'],
            'mobil'              => $live['mobil'],
            'motor'              => $live['motor'],
            'total'              => $live['total'],
            'lot_mobil'          => $live['lot_mobil'],
            'lot_motor'          => $live['lot_motor'],
            'lot_mobil_tersedia' => $live['lot_mobil_tersedia'],
            'lot_motor_tersedia' => $live['lot_motor_tersedia'],
        ]);
    }
}
