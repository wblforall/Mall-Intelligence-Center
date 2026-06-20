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

        $spi  = new SpiReportingService();
        $live = $spi->fetchLive();

        return view('parking/live', [
            'title'    => 'Live — Parkir',
            'canVeh'   => $canVeh,
            'canRev'   => $canRev,
            'live'     => $live,
            'payments' => $canRev ? $spi->fetchPaymentBreakdown() : [],
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
        if ($canRev) {
            $out += [
                'tunai'       => $live['tunai'],
                'nontunai'    => $live['nontunai'],
                'totalincome' => $live['totalincome'],
                'payments'    => $spi->fetchPaymentBreakdown(),
            ];
        }
        return $this->response->setJSON($out);
    }
}
