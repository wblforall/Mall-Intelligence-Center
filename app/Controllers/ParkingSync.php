<?php

namespace App\Controllers;

/**
 * Trigger manual sinkronisasi data parkir SPI (7 hari terakhir) dari tombol UI.
 * Menjalankan command mic:spi-sync secara sinkron. Akses: punya salah satu menu parkir.
 */
class ParkingSync extends BaseController
{
    public function run()
    {
        if (! $this->canViewMenu('parking_vehicles') && ! $this->canViewMenu('parking_revenue')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['ok' => false, 'message' => 'Akses ditolak.', 'csrf' => csrf_hash()]);
        }

        @set_time_limit(0);
        ignore_user_abort(true);

        $from = date('Y-m-d', strtotime('-7 days'));
        $to   = date('Y-m-d');

        try {
            $out = (string) command("mic:spi-sync --from {$from} --to {$to}");
        } catch (\Throwable $e) {
            log_message('error', '[parking/sync] ' . $e->getMessage());
            return $this->response->setJSON([
                'ok' => false, 'message' => 'Gagal sinkronisasi: ' . $e->getMessage(), 'csrf' => csrf_hash(),
            ]);
        }

        // Deteksi kegagalan login SPI, jika tidak ambil ringkasan baris "Selesai. ..."
        if (stripos($out, 'Gagal login') !== false) {
            return $this->response->setJSON([
                'ok' => false, 'message' => 'Gagal login ke SPI. Periksa kredensial SPI_* di .env.', 'csrf' => csrf_hash(),
            ]);
        }
        $msg = 'Sinkronisasi selesai.';
        if (preg_match('/Selesai\.[^\r\n]*/', $out, $m)) {
            $msg = trim($m[0]);
        }

        return $this->response->setJSON(['ok' => true, 'message' => $msg, 'csrf' => csrf_hash()]);
    }
}
