<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SpiReportingService;

/**
 * Rekam 1 snapshot LIVE parkir SPI ke spi_live_snapshot. Dijalankan cron tiap ~15 menit.
 * Independen dari sesi login MIC — pakai kredensial SPI sendiri.
 *
 *   php spark mic:spi-snapshot                 # okupansi saja (cepat ~1dtk)
 *   php spark mic:spi-snapshot --flows         # + dashboard /home (arus/gate/jenis/payment, ~24dtk)
 *   php spark mic:spi-snapshot --prune-days 120
 *
 * Cron disarankan (pisah beban — home SPI berat):
 *   10,20,40,50 * * * *  mic:spi-snapshot --prune-days 120   # okupansi tiap 10 mnt
 *   0,30 * * * *         mic:spi-snapshot --flows             # + home tiap 30 mnt
 */
class SpiSnapshot extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:spi-snapshot';
    protected $description  = 'Rekam snapshot live parkir SPI (okupansi + income + payment).';
    protected $usage        = 'mic:spi-snapshot [--prune-days N]';
    protected $options      = [
        '--prune-days' => 'Hapus snapshot lebih tua dari N hari (default: tidak)',
        '--flows'      => 'Ikut tarik dashboard /home (arus jam/gate/jenis/payment) — lambat ~24dtk',
    ];

    public function run(array $params)
    {
        $withFlows = (bool) CLI::getOption('flows');

        $spi  = new SpiReportingService();
        $live = $spi->fetchLive();
        if (empty($live['ok'])) {
            CLI::error('Live SPI tidak tersedia — snapshot dilewati (tidak menyimpan 0).');
            log_message('warning', '[spi-snapshot] live ok=false, dilewati');
            return;
        }

        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // /home (arus + gate + per-jenis + payment) hanya saat --flows (home ~24dtk; jangan tiap run).
        $flows = ['hourly' => [], 'gates' => ['masuk' => [], 'keluar' => []], 'types' => [], 'payments' => []];
        if ($withFlows) {
            try { $flows = $spi->fetchDashboardFlows(); } catch (\Throwable $e) {
                log_message('warning', '[spi-snapshot] flows: ' . $e->getMessage());
            }
        }
        // payments_json dari dashboard (Jenis Pembayaran) → format [{method,amount}]
        $payments = [];
        foreach (($flows['payments'] ?? []) as $method => $amt) { $payments[] = ['method' => $method, 'amount' => (int) $amt]; }

        $db->table('spi_live_snapshot')->insert([
            'captured_at'     => $now,
            'tanggal'         => $today,
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

        // Arus per jam & per pintu + per-jenis (hanya saat --flows; kumulatif hari ini → ganti penuh)
        if ($withFlows) try {
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
            // Per jenis kendaraan (masuk + income)
            $typeRows = [];
            foreach (($flows['types'] ?? []) as $jenis => $v) {
                if ($jenis === 'lain') { continue; }
                $typeRows[] = ['tanggal' => $today, 'jenis' => $jenis,
                    'masuk' => (int) ($v['masuk'] ?? 0), 'income' => (int) ($v['income'] ?? 0), 'updated_at' => $now];
            }
            if ($typeRows) {
                $db->table('spi_capture_type_daily')->where('tanggal', $today)->delete();
                $db->table('spi_capture_type_daily')->insertBatch($typeRows);
            }
            CLI::write('Arus: jam=' . count($flows['hourly']) . ' gate_masuk=' . count($flows['gates']['masuk'])
                . ' gate_keluar=' . count($flows['gates']['keluar']) . ' jenis=' . count($typeRows)
                . ' payment=' . count($payments) . '.', 'green');
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
