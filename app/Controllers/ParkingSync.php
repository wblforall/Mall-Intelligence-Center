<?php

namespace App\Controllers;

/**
 * Trigger manual sinkronisasi data parkir SPI (7 hari terakhir) dari tombol UI.
 * Menjalankan command mic:spi-sync sinkron, lalu deteksi apakah ada data baru/perubahan
 * dengan membandingkan sidik-jari (latest date + agregat window) sebelum vs sesudah.
 * Akses: punya salah satu menu parkir.
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

        $db    = \Config\Database::connect();
        $since = date('Y-m-d', strtotime('-10 days')); // window cek perubahan (mencakup sync 7 hari + margin)

        $latestBefore = $this->latestDate($db);
        $fpBefore     = $this->fingerprint($db, $since);

        $from = date('Y-m-d', strtotime('-7 days'));
        $to   = date('Y-m-d');
        try {
            $out = (string) command("mic:spi-sync --from {$from} --to {$to}");
        } catch (\Throwable $e) {
            log_message('error', '[parking/sync] ' . $e->getMessage());
            return $this->response->setJSON(['ok' => false, 'message' => 'Gagal sinkronisasi: ' . $e->getMessage(), 'csrf' => csrf_hash()]);
        }
        if (stripos($out, 'Gagal login') !== false) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Gagal login ke SPI. Periksa kredensial SPI_* di .env.', 'csrf' => csrf_hash()]);
        }

        // Bust cache agar banner "data s/d ..." & live ikut segar
        $site = env('SPI_SITE', 'BSB');
        foreach (['spi_latest_' . $site, 'spi_live_' . $site, 'spi_pay_' . $site] as $k) { cache()->delete($k); }

        $latestAfter = $this->latestDate($db);
        $fpAfter     = $this->fingerprint($db, $since);
        $fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';

        if ($latestAfter && $latestAfter > (string) $latestBefore) {
            $status = 'new';
            $message = 'Data baru masuk — terkini s/d ' . $fmt($latestAfter)
                . ($latestBefore ? ' (sebelumnya ' . $fmt($latestBefore) . ')' : '') . '.';
        } elseif ($fpAfter !== $fpBefore) {
            $status = 'updated';
            $message = 'Data diperbarui (finalisasi angka) — terkini s/d ' . $fmt($latestAfter) . '.';
        } else {
            $status = 'nochange';
            $message = 'Tidak ada data baru — arsip sudah terkini s/d ' . $fmt($latestAfter) . '.';
        }

        return $this->response->setJSON([
            'ok'      => true,
            'status'  => $status,           // new | updated | nochange
            'latest'  => $latestAfter,
            'message' => $message,
            'detail'  => trim((string) (preg_match('/Selesai\.[^\r\n]*/', $out, $m) ? $m[0] : '')),
            'csrf'    => csrf_hash(),
        ]);
    }

    /** Tanggal data terakhir (qty>0) dari DB — langsung, tanpa cache. */
    private function latestDate($db): ?string
    {
        if (! $db->tableExists('spi_vehicle_daily')) { return null; }
        $r = $db->table('spi_vehicle_daily')->selectMax('tanggal', 'mx')->where('total >', 0)->get()->getRowArray();
        return $r['mx'] ?? null;
    }

    /** Sidik-jari agregat window (deteksi perubahan nilai walau tanggal sama). */
    private function fingerprint($db, string $since): string
    {
        $sum = function (string $sql) use ($db, $since): int {
            try { return (int) ($db->query($sql, [$since])->getRow()->s ?? 0); }
            catch (\Throwable $e) { return 0; }
        };
        $v = $sum('SELECT IFNULL(SUM(total),0)+IFNULL(SUM(mobil_free+motor_free+box_free+truck_free+taxi_free+bus_free),0) s FROM spi_vehicle_daily WHERE tanggal >= ?');
        $i = $sum('SELECT IFNULL(SUM(total),0) s FROM spi_income_daily WHERE tanggal >= ?');
        $p = $sum('SELECT IFNULL(SUM(amount),0) s FROM spi_payment_daily WHERE tanggal >= ?');
        $d = $sum('SELECT IFNULL(SUM(le1+h1_2+h2_3+h3_4+h4_5+h5_6+h6_7+gt7),0) s FROM spi_duration_daily WHERE tanggal >= ?');
        return "$v|$i|$p|$d";
    }
}
