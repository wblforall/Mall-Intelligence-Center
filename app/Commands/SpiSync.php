<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SpiReportingService;
use App\Models\DailyVehicleModel;
use App\Libraries\ActivityLog;

/**
 * Sinkronkan salinan lokal data parkir SPI (Hybrid sync) + opsional isi daily_vehicles.
 *
 *   php spark mic:spi-sync                        # 7 hari terakhir
 *   php spark mic:spi-sync --from 2023-01-01      # backfill sejak 2023 s/d hari ini
 *   php spark mic:spi-sync --from 2026-06-01 --to 2026-06-17
 *   php spark mic:spi-sync --fill-vehicles        # ikut isi daily_vehicles (tanggal kosong)
 *   php spark mic:spi-sync --fill-vehicles --force   # timpa daily_vehicles yang sudah ada
 *
 * Catatan opsi: gunakan SPASI (--from 2023-01-01), bukan tanda sama dengan.
 * Cron harian: 0 8 * * *  (SPI update jam 7 pagi → kita tarik jam 8 pagi).
 */
class SpiSync extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:spi-sync';
    protected $description  = 'Tarik salinan data parkir SPI ke tabel lokal (+ opsi isi daily_vehicles).';
    protected $usage        = 'mic:spi-sync [--from YYYY-MM-DD] [--to YYYY-MM-DD] [--fill-vehicles] [--force]';
    protected $options      = [
        '--from'           => 'Tanggal mulai (default: 7 hari lalu)',
        '--to'             => 'Tanggal akhir (default: hari ini)',
        '--fill-vehicles'  => 'Isi juga tabel MIC daily_vehicles',
        '--force'          => 'Timpa daily_vehicles yang sudah ada (default: hanya yang kosong)',
    ];

    public function run(array $params)
    {
        $from = CLI::getOption('from') ?: date('Y-m-d', strtotime('-7 days'));
        $to   = CLI::getOption('to')   ?: date('Y-m-d');
        $fillVeh = (bool) CLI::getOption('fill-vehicles');
        $force   = (bool) CLI::getOption('force');

        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $spi = new SpiReportingService();
        if (! $spi->ping()) {
            CLI::error('Gagal login ke SPI. Periksa kredensial SPI_* di .env.');
            return;
        }

        $db   = \Config\Database::connect();
        $vehM = new DailyVehicleModel();
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
            $pass = $fillVeh ? $spi->fetchDailyPass($mStart, $mEnd) : [];

            foreach ($qty as $r) {
                if (! $r['tanggal']) { continue; }
                $db->table('spi_vehicle_daily')->replace([
                    'tanggal' => $r['tanggal'], 'mobil' => $r['mobil'], 'motor' => $r['motor'],
                    'box' => $r['box'], 'truck' => $r['truck'], 'taxi' => $r['taxi'], 'bus' => $r['bus'],
                    'total' => $r['total'],
                    'mobil_free' => $pass[$r['tanggal']]['mobil'] ?? 0,
                    'motor_free' => $pass[$r['tanggal']]['motor'] ?? 0,
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

            // Isi daily_vehicles MIC (casual → kolom utama, pass → *_free)
            if ($fillVeh) {
                foreach ($qty as $r) {
                    $tgl = $r['tanggal']; if (! $tgl) { continue; }
                    $exist = $vehM->where('tanggal', $tgl)->first();
                    if ($exist && ! $force) { continue; }
                    $row = [
                        'tanggal'          => $tgl,
                        'total_mobil'      => $r['mobil'],
                        'total_motor'      => $r['motor'],
                        'total_mobil_box'  => $r['box'],
                        'total_truck'      => $r['truck'],
                        'total_bus'        => $r['bus'],
                        'total_mobil_free' => $pass[$tgl]['mobil'] ?? 0,
                        'total_motor_free' => $pass[$tgl]['motor'] ?? 0,
                    ];
                    if ($exist) { $vehM->update($exist['id'], $row); }
                    else        { $vehM->insert($row); }
                    $totVeh++;
                }
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
