<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SpiReportingService;

/**
 * Rekam 1 snapshot LIVE parkir SPI ke spi_live_snapshot. Dijalankan cron tiap ~15 menit.
 * Independen dari sesi login MIC — pakai kredensial SPI sendiri.
 *
 *   php spark mic:spi-snapshot                 # rekam 1 snapshot sekarang
 *   php spark mic:spi-snapshot --prune-days 90 # + hapus snapshot > 90 hari (rollup nanti)
 *
 * Cron disarankan: setiap 15 menit  ->  0,15,30,45 * * * *
 */
class SpiSnapshot extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:spi-snapshot';
    protected $description  = 'Rekam snapshot live parkir SPI (okupansi + income + payment).';
    protected $usage        = 'mic:spi-snapshot [--prune-days N]';
    protected $options      = ['--prune-days' => 'Hapus snapshot lebih tua dari N hari (default: tidak)'];

    public function run(array $params)
    {
        $spi  = new SpiReportingService();
        $live = $spi->fetchLive();
        if (empty($live['ok'])) {
            CLI::error('Live SPI tidak tersedia — snapshot dilewati (tidak menyimpan 0).');
            log_message('warning', '[spi-snapshot] live ok=false, dilewati');
            return;
        }

        // Payment hari ini (opsional; jangan gagalkan snapshot bila error)
        $payments = [];
        try { $payments = $spi->fetchPaymentBreakdown(); } catch (\Throwable $e) {
            log_message('warning', '[spi-snapshot] payment: ' . $e->getMessage());
        }

        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('spi_live_snapshot')->insert([
            'captured_at'     => $now,
            'tanggal'         => date('Y-m-d'),
            'total_in'        => (int) $live['total'],
            'mobil_in'        => (int) $live['mobil'],
            'motor_in'        => (int) $live['motor'],
            'other_in'        => (int) ($live['other'] ?? 0),
            'lot_mobil_avail' => (int) $live['lot_mobil_tersedia'],
            'lot_motor_avail' => (int) $live['lot_motor_tersedia'],
            'total_income'    => (int) $live['totalincome'],
            'tunai'           => (int) $live['tunai'],
            'nontunai'        => (int) $live['nontunai'],
            'payments_json'   => $payments ? json_encode($payments) : null,
            'created_at'      => $now,
        ]);

        CLI::write("Snapshot tersimpan {$now} — di dalam: {$live['total']} (mobil {$live['mobil']}/motor {$live['motor']}), "
            . 'income: ' . number_format($live['totalincome']) . '.', 'green');

        // Arus masuk/keluar per jam & per pintu (kumulatif hari ini → ganti penuh tiap rekaman)
        try {
            $flows = $spi->fetchDashboardFlows();
            $today = date('Y-m-d');
            if (! empty($flows['hourly'])) {
                $db->table('spi_hourly_flow')->where('tanggal', $today)->delete();
                $rows = [];
                foreach ($flows['hourly'] as $h => $v) {
                    $rows[] = ['tanggal' => $today, 'jam' => (int) $h,
                        'masuk' => (int) ($v['masuk'] ?? 0), 'keluar' => (int) ($v['keluar'] ?? 0), 'updated_at' => $now];
                }
                $db->table('spi_hourly_flow')->insertBatch($rows);
            }
            $gateRows = [];
            foreach (['masuk', 'keluar'] as $arah) {
                foreach (($flows['gates'][$arah] ?? []) as $g => $n) {
                    $gateRows[] = ['tanggal' => $today, 'gate' => $g, 'arah' => $arah, 'jumlah' => (int) $n, 'updated_at' => $now];
                }
            }
            if ($gateRows) {
                $db->table('spi_gate_daily')->where('tanggal', $today)->delete();
                $db->table('spi_gate_daily')->insertBatch($gateRows);
            }
            CLI::write('Arus: jam=' . count($flows['hourly']) . ' gate_masuk=' . count($flows['gates']['masuk'])
                . ' gate_keluar=' . count($flows['gates']['keluar']) . '.', 'green');
        } catch (\Throwable $e) {
            log_message('warning', '[spi-snapshot] flows: ' . $e->getMessage());
        }

        $pd = (int) (CLI::getOption('prune-days') ?: 0);
        if ($pd > 0) {
            $cut = date('Y-m-d', strtotime("-{$pd} days"));
            $db->table('spi_live_snapshot')->where('tanggal <', $cut)->delete();
            CLI::write("Prune: hapus snapshot < {$cut} ({$db->affectedRows()} baris).", 'yellow');
        }
    }
}
