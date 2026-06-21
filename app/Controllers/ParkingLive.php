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

        // TIDAK menarik SPI di sini (lambat) — halaman render instan dgn nilai 0,
        // lalu diisi via AJAX /parking/live-data + overlay progress. latestDataDate dari DB (cepat).
        $zero = ['ok' => true, 'mobil' => 0, 'motor' => 0, 'total' => 0,
            'lot_mobil' => 0, 'lot_motor' => 0, 'lot_mobil_tersedia' => 0, 'lot_motor_tersedia' => 0,
            'tunai' => 0, 'nontunai' => 0, 'totalincome' => 0];

        return view('parking/live', [
            'title'     => 'Live — Parkir',
            'canVeh'    => $canVeh,
            'canRev'    => $canRev,
            'live'      => $zero,
            'payments'  => [],
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
        if ($canRev) {
            $out += [
                'tunai'       => $live['tunai'],
                'nontunai'    => $live['nontunai'],
                'totalincome' => $live['totalincome'],
                // Rincian metode dari snapshot terbaru (DB) — bukan scrape home (yg ~24dtk).
                // Diperbarui berkala oleh cron mic:spi-snapshot.
                'payments'    => $this->latestPayments(),
            ];
        }
        return $this->response->setJSON($out);
    }

    /** Rincian metode pembayaran dari snapshot terbaru yang punya data (DB, instan). */
    private function latestPayments(): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('spi_live_snapshot')) { return []; }
        $row = $db->table('spi_live_snapshot')
            ->select('payments_json')->where('payments_json IS NOT NULL', null, false)
            ->orderBy('captured_at', 'DESC')->limit(1)->get()->getRowArray();
        $arr = $row ? json_decode($row['payments_json'] ?: '[]', true) : [];
        return is_array($arr) ? $arr : [];
    }
}
