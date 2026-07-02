<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SpiReportingService;
use App\Libraries\ActivityLog;

/**
 * Sinkronkan salinan lokal data parkir SPI (Hybrid sync).
 *
 *   php spark mic:spi-sync                        # 7 hari terakhir
 *   php spark mic:spi-sync --from 2023-01-01      # backfill sejak 2023 s/d hari ini
 *   php spark mic:spi-sync --from 2026-06-01 --to 2026-06-17
 *
 * daily_vehicles (sumber kendaraan Event Summary) kini SELALU dicerminkan
 * otomatis dari spi_vehicle_daily untuk rentang yang disync — menggantikan
 * input manual "Input Kendaraan" yang sudah dihapus.
 *
 * Catatan opsi: gunakan SPASI (--from 2023-01-01), bukan tanda sama dengan.
 * Cron harian: 0 8 * * *  (SPI update jam 7 pagi → kita tarik jam 8 pagi).
 */
class SpiSync extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:spi-sync';
    protected $description  = 'Tarik salinan data parkir SPI ke tabel lokal (+ cermin daily_vehicles).';
    protected $usage        = 'mic:spi-sync [--from YYYY-MM-DD] [--to YYYY-MM-DD]';
    protected $options      = [
        '--from'           => 'Tanggal mulai (default: 7 hari lalu)',
        '--to'             => 'Tanggal akhir (default: hari ini)',
    ];

    public function run(array $params)
    {
        $from = CLI::getOption('from') ?: date('Y-m-d', strtotime('-7 days'));
        $to   = CLI::getOption('to')   ?: date('Y-m-d');

        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $spi = new SpiReportingService();
        if (! $spi->ping()) {
            CLI::error('Gagal login ke SPI. Periksa kredensial SPI_* di .env.');
            return;
        }

        $db   = \Config\Database::connect();
        $now  = date('Y-m-d H:i:s');

        $totQty = 0; $totInc = 0; $totVeh = 0;

        // Proses per bulan agar tiap panggilan terbatas
        $cursor = date('Y-m-01', strtotime($from));
        while ($cursor <= $to) {
            $mStart = max($from, $cursor);
            $mEnd   = min($to, date('Y-m-t', strtotime($cursor)));
            CLI::write("Sync {$mStart} .. {$mEnd} ...", 'yellow');

            $qty  = $spi->fetchDailyQty($mStart, $mEnd);
            $inc  = $spi->fetchDailyIncome($mStart, $mEnd);

            foreach ($qty as $r) {
                if (! $r['tanggal']) { continue; }
                // Kolom *_free diisi belakangan dari statistik (lebih lengkap); di sini default 0.
                $db->table('spi_vehicle_daily')->replace([
                    'tanggal' => $r['tanggal'], 'mobil' => $r['mobil'], 'motor' => $r['motor'],
                    'box' => $r['box'], 'truck' => $r['truck'], 'taxi' => $r['taxi'], 'bus' => $r['bus'],
                    'total' => $r['total'],
                    'updated_at' => $now,
                ]);
                $totQty++;
            }
            foreach ($inc as $r) {
                if (! $r['tanggal']) { continue; }
                $db->table('spi_income_daily')->replace([
                    'tanggal' => $r['tanggal'], 'mobil' => $r['mobil'], 'motor' => $r['motor'],
                    'box' => $r['box'], 'truck' => $r['truck'], 'taxi' => $r['taxi'], 'bus' => $r['bus'],
                    'total' => $r['total'], 'updated_at' => $now,
                ]);
                $totInc++;
            }

            $cursor = date('Y-m-01', strtotime($cursor . ' +1 month'));
        }

        // Income bulanan resmi (casual + member) untuk rentang
        $mFrom = date('Y-m-01', strtotime($from));
        $casual = $this->indexMonthly($spi->fetchMonthlyIncome($mFrom, $to, 0));
        $member = $this->indexMonthly($spi->fetchMonthlyIncome($mFrom, $to, 1));
        $totMon = 0;
        foreach (array_unique(array_merge(array_keys($casual), array_keys($member))) as $bln) {
            $db->table('spi_income_monthly')->replace([
                'bulan' => $bln, 'casual' => $casual[$bln] ?? 0, 'member' => $member[$bln] ?? 0,
                'updated_at' => $now,
            ]);
            $totMon++;
        }

        // Rincian payment HISTORIS per tanggal×metode (table-casual-income — patchy, hanya yg ada).
        $totPay = 0;
        foreach ($spi->fetchCasualTableChunked($from, $to) as $row) {
            $tgl = $row['tanggal']; if (! $tgl) { continue; }
            foreach ($row['payments'] as $method => $amt) {
                $db->table('spi_payment_daily')->replace([
                    'tanggal' => $tgl, 'method' => $method, 'amount' => $amt, 'updated_at' => $now,
                ]);
                $totPay++;
            }
        }

        // Statistik harian dari statistik.php (sumber lengkap): durasi (spi_duration_daily)
        // + langganan/free per jenis → lengkapi spi_vehicle_daily.
        $totDur = 0; $totFree = 0;
        foreach ($spi->fetchStatistikDaily($from, $to) as $tgl => $d) {
            $b = $d['dur'];
            $db->table('spi_duration_daily')->replace([
                'tanggal' => $tgl,
                'le1' => $b['le1'], 'h1_2' => $b['h1_2'], 'h2_3' => $b['h2_3'], 'h3_4' => $b['h3_4'],
                'h4_5' => $b['h4_5'], 'h5_6' => $b['h5_6'], 'h6_7' => $b['h6_7'], 'gt7' => $b['gt7'],
                'updated_at' => $now,
            ]);
            $totDur++;
            $f = $d['free'];
            $upd = $db->table('spi_vehicle_daily')->where('tanggal', $tgl)->update([
                'mobil_free' => (int) $f['mobil'], 'motor_free' => (int) $f['motor'],
                'box_free'   => (int) $f['box'],   'truck_free' => (int) $f['truck'],
                'taxi_free'  => (int) $f['taxi'],  'bus_free'   => (int) $f['bus'],
                'updated_at' => $now,
            ]);
            if ($upd) { $totFree++; }
        }

        // ── Cermin spi_vehicle_daily → daily_vehicles (sumber kendaraan Event Summary) ──
        // Selalu upsert untuk rentang yang disync agar data terbaru ikut ter-refresh.
        // Kolom *_free authoritatif dari spi_vehicle_daily (sudah difinalisasi di atas).
        $db->query(
            'INSERT INTO daily_vehicles
                (tanggal, total_mobil, total_motor, total_mobil_box, total_truck, total_bus,
                 total_mobil_free, total_motor_free, created_at, updated_at)
             SELECT tanggal, mobil, motor, box, truck, bus, mobil_free, motor_free, ?, ?
             FROM spi_vehicle_daily WHERE tanggal >= ? AND tanggal <= ?
             ON DUPLICATE KEY UPDATE
                total_mobil = VALUES(total_mobil), total_motor = VALUES(total_motor),
                total_mobil_box = VALUES(total_mobil_box), total_truck = VALUES(total_truck),
                total_bus = VALUES(total_bus), total_mobil_free = VALUES(total_mobil_free),
                total_motor_free = VALUES(total_motor_free), updated_at = VALUES(updated_at)',
            [$now, $now, $from, $to]
        );
        $totVeh = (int) $db->table('spi_vehicle_daily')->where('tanggal >=', $from)->where('tanggal <=', $to)->countAllResults();

        CLI::write("Selesai. qty={$totQty} income={$totInc} bulanan={$totMon} daily_vehicles={$totVeh} payment={$totPay} free={$totFree} durasi={$totDur}.", 'green');
        try {
            ActivityLog::write('update', 'spi_parking', null,
                "sync SPI {$from}..{$to}: qty={$totQty}, income={$totInc}, bulanan={$totMon}, daily_vehicles={$totVeh}");
        } catch (\Throwable $e) {
            log_message('warning', '[spi-sync] activity log: ' . $e->getMessage());
        }
    }

    /** ['Mar 2026'=>val] → ['2026-03'=>val] */
    private function indexMonthly(array $pts): array
    {
        $out = [];
        foreach ($pts as $p) {
            $ts = strtotime('1 ' . $p['label']);
            if ($ts) { $out[date('Y-m', $ts)] = $p['value']; }
        }
        return $out;
    }
}
