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
        $canVeh = $this->canViewMenu('parking_vehicles');
        $canRev = $this->canViewMenu('parking_revenue');
        if (! $canVeh && ! $canRev) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        // TIDAK menarik SPI di sini (lambat) — okupansi render instan dgn nilai 0, diisi via AJAX.
        $zero = ['ok' => true, 'mobil' => 0, 'motor' => 0, 'total' => 0,
            'lot_mobil' => 0, 'lot_motor' => 0, 'lot_mobil_tersedia' => 0, 'lot_motor_tersedia' => 0];

        // Aktivitas per pintu hari ini (kumulatif, dari DB — diisi cron --flows ~30 mnt)
        $gates = ['masuk' => [], 'keluar' => []];
        $db = \Config\Database::connect();
        if ($canVeh && $db->tableExists('spi_gate_daily')) {
            foreach ($db->table('spi_gate_daily')->where('tanggal', date('Y-m-d'))
                ->orderBy('jumlah', 'DESC')->get()->getResultArray() as $r) {
                $gates[$r['arah']][] = ['gate' => $r['gate'], 'jumlah' => (int) $r['jumlah']];
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

    /** JSON live — field disesuaikan hak akses. */
    public function data()
    {
        $canVeh = $this->canViewMenu('parking_vehicles');
        $canRev = $this->canViewMenu('parking_revenue');
        if (! $canVeh && ! $canRev) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $spi  = new SpiReportingService();
        $live = $spi->fetchLive();
        $out  = ['ok' => $live['ok']];

        if ($canVeh) {
            $out += [
                'mobil'              => $live['mobil'],
                'motor'              => $live['motor'],
                'total'              => $live['total'],
                'lot_mobil'          => $live['lot_mobil'],
                'lot_motor'          => $live['lot_motor'],
                'lot_mobil_tersedia' => $live['lot_mobil_tersedia'],
                'lot_motor_tersedia' => $live['lot_motor_tersedia'],
            ];
        }
        // Income/payment TIDAK lagi ditampilkan di Live (pindah ke Okupansi Intraday).
        return $this->response->setJSON($out);
    }
}
