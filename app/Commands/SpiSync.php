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
 *   php spark mic:spi-sync --mirror-only --from 2023-01-01   # backfill daily_vehicles
 *                                                 # dari salinan lokal, TANPA hit SPI (instan)
 *
 * daily_vehicles (sumber kendaraan Event Summary) kini SELALU dicerminkan
 * otomatis dari spi_vehicle_daily untuk rentang yang disync — menggantikan
 * input manual "Input Kendaraan" yang sudah dihapus.
 *
 * Ketahanan koneksi DB: MariaDB hosting memakai wait_timeout 30 dtk, sedangkan panggilan
 * SPI bisa jauh lebih lama → koneksi diputus server ("MySQL server has gone away") dan
 * tulisan gagal diam-diam. Karena itu: wait_timeout sesi dinaikkan, koneksi disambung
 * ulang sebelum tiap blok tulis, dan HASIL TIAP TULIS diperiksa. Ada yang gagal →
 * ringkasan "Selesai dengan galat" + exit code 1 (cron/monitor bisa mendeteksi).
 *
 * Catatan opsi: gunakan SPASI (--from 2023-01-01), bukan tanda sama dengan.
 * Cron harian: 0 8 * * *  (SPI update jam 7 pagi → kita tarik jam 8 pagi).
 */
class SpiSync extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:spi-sync';
    protected $description  = 'Tarik salinan data parkir SPI ke tabel lokal (+ cermin daily_vehicles).';
    protected $usage        = 'mic:spi-sync [--from YYYY-MM-DD] [--to YYYY-MM-DD] [--mirror-only]';
    protected $options      = [
        '--from'           => 'Tanggal mulai (default: 7 hari lalu)',
        '--to'             => 'Tanggal akhir (default: hari ini)',
        '--mirror-only'    => 'Hanya cermin spi_vehicle_daily → daily_vehicles (tanpa hit SPI)',
    ];

    /** wait_timeout/interactive_timeout sesi DB (detik) selama sync. */
    public const DB_SESSION_TIMEOUT = 900;

    /** Urutan jenis data di ringkasan. */
    private const JENIS = ['qty', 'income', 'bulanan', 'payment', 'durasi', 'free', 'daily_vehicles'];

    /** @var array<string,array{ok:int,gagal:int}> hitungan per jenis data */
    private array $hasil = [];

    /** @var array<string,string> galat pertama per jenis data (ringkas) */
    private array $galat = [];

    public function run(array $params)
    {
        $from = CLI::getOption('from') ?: date('Y-m-d', strtotime('-7 days'));
        $to   = CLI::getOption('to')   ?: date('Y-m-d');

        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $this->resetHasil();
        $db   = \Config\Database::connect();
        $now  = date('Y-m-d H:i:s');

        try {
            $this->siapkanKoneksi($db);
        } catch (\Throwable $e) {
            CLI::error('Gagal menyiapkan koneksi DB: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        // Mode backfill lokal: cermin daily_vehicles dari spi_vehicle_daily, tanpa hit SPI.
        if (CLI::getOption('mirror-only')) {
            $n = $this->tulisCermin($db, $from, $to, $now);
            if ($this->totalGagal() > 0) {
                CLI::error("Mirror lokal GAGAL ({$from}..{$to}): " . ($this->galat['daily_vehicles'] ?? '-'));
                return EXIT_ERROR;
            }
            CLI::write("Mirror lokal selesai. daily_vehicles={$n} ({$from}..{$to}).", 'green');
            return EXIT_SUCCESS;
        }

        $spi = (new SpiReportingService())->setStrict(true);
        try {
            if (! $spi->ping()) {
                CLI::error('Gagal login ke SPI. Periksa kredensial SPI_* di .env.');
                return EXIT_ERROR;
            }
        } catch (\Throwable $e) {
            // Teks "Gagal login" dipakai ParkingSync untuk mendeteksi kegagalan ini.
            CLI::error('Gagal login ke SPI: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        // Proses per bulan agar tiap panggilan terbatas
        $cursor = date('Y-m-01', strtotime($from));
        while ($cursor <= $to) {
            $mStart = max($from, $cursor);
            $mEnd   = min($to, date('Y-m-t', strtotime($cursor)));
            CLI::write("Sync {$mStart} .. {$mEnd} ...", 'yellow');

            $qty = $this->ambil('qty', "{$mStart}..{$mEnd}", fn() => $spi->fetchDailyQty($mStart, $mEnd));
            $inc = $this->ambil('income', "{$mStart}..{$mEnd}", fn() => $spi->fetchDailyIncome($mStart, $mEnd));

            // Kolom *_free diisi belakangan dari statistik (lebih lengkap); di sini default 0.
            $this->tulisBlok($db, 'qty', $qty ?? [], fn($db, $r) => ! $r['tanggal'] ? null
                : $db->table('spi_vehicle_daily')->replace([
                    'tanggal' => $r['tanggal'], 'mobil' => $r['mobil'], 'motor' => $r['motor'],
                    'box' => $r['box'], 'truck' => $r['truck'], 'taxi' => $r['taxi'], 'bus' => $r['bus'],
                    'total' => $r['total'],
                    'updated_at' => $now,
                ]));
            $this->tulisBlok($db, 'income', $inc ?? [], fn($db, $r) => ! $r['tanggal'] ? null
                : $db->table('spi_income_daily')->replace([
                    'tanggal' => $r['tanggal'], 'mobil' => $r['mobil'], 'motor' => $r['motor'],
                    'box' => $r['box'], 'truck' => $r['truck'], 'taxi' => $r['taxi'], 'bus' => $r['bus'],
                    'total' => $r['total'], 'updated_at' => $now,
                ]));

            $cursor = date('Y-m-01', strtotime($cursor . ' +1 month'));
        }

        // Income bulanan resmi (casual + member) untuk rentang. Tulis HANYA bila keduanya
        // berhasil diambil — jangan timpa angka lama dengan 0 karena satu sisi gagal.
        $mFrom  = date('Y-m-01', strtotime($from));
        $casual = $this->ambil('bulanan', "casual {$mFrom}..{$to}", fn() => $spi->fetchMonthlyIncome($mFrom, $to, 0));
        $member = $this->ambil('bulanan', "member {$mFrom}..{$to}", fn() => $spi->fetchMonthlyIncome($mFrom, $to, 1));
        if ($casual !== null && $member !== null) {
            $casual = $this->indexMonthly($casual);
            $member = $this->indexMonthly($member);
            $bulan  = array_unique(array_merge(array_keys($casual), array_keys($member)));
            $this->tulisBlok($db, 'bulanan', $bulan, fn($db, $bln) => $db->table('spi_income_monthly')->replace([
                'bulan' => $bln, 'casual' => $casual[$bln] ?? 0, 'member' => $member[$bln] ?? 0,
                'updated_at' => $now,
            ]));
        }

        // Rincian payment HISTORIS per tanggal×metode (casual-parking-data), potongan ≤7 hari.
        foreach (SpiReportingService::splitRange($from, $to, 7) as [$cs, $ce]) {
            $rows = $this->ambil('payment', "{$cs}..{$ce}", fn() => $spi->fetchCasualTable($cs, $ce)) ?? [];
            $pay  = [];
            foreach ($rows as $row) {
                if (! $row['tanggal']) { continue; }
                foreach ($row['payments'] as $method => $amt) {
                    $pay[] = ['tanggal' => $row['tanggal'], 'method' => $method, 'amount' => $amt];
                }
            }
            $this->tulisBlok($db, 'payment', $pay, fn($db, $p) => $db->table('spi_payment_daily')->replace([
                'tanggal' => $p['tanggal'], 'method' => $p['method'], 'amount' => $p['amount'], 'updated_at' => $now,
            ]));
        }

        // Statistik harian dari statistik.php (sumber lengkap): durasi (spi_duration_daily)
        // + langganan/free per jenis → lengkapi spi_vehicle_daily. Potongan ≤45 hari.
        foreach (SpiReportingService::splitRange($from, $to, 45) as [$cs, $ce]) {
            $stat = $this->ambil('durasi', "statistik {$cs}..{$ce}", fn() => $spi->fetchStatistikDaily($cs, $ce)) ?? [];
            $list = [];
            foreach ($stat as $tgl => $d) { $list[] = ['tanggal' => $tgl] + $d; }

            $this->tulisBlok($db, 'durasi', $list, function ($db, $d) use ($now) {
                $b = $d['dur'];
                return $db->table('spi_duration_daily')->replace([
                    'tanggal' => $d['tanggal'],
                    'le1' => $b['le1'], 'h1_2' => $b['h1_2'], 'h2_3' => $b['h2_3'], 'h3_4' => $b['h3_4'],
                    'h4_5' => $b['h4_5'], 'h5_6' => $b['h5_6'], 'h6_7' => $b['h6_7'], 'gt7' => $b['gt7'],
                    'updated_at' => $now,
                ]);
            });
            // Jangan timpa langganan/free yang sudah terisi dengan 0: statistik.php
            // sering "menggugurkan" data Pass hari lama (window bergulir) → kembalikan 0
            // dan menghapus nilai valid. Lewati update free bila hasil parse semuanya 0.
            $this->tulisBlok($db, 'free', $list, function ($db, $d) use ($now) {
                $f = $d['free'];
                $freeSum = (int) $f['mobil'] + (int) $f['motor'] + (int) $f['box']
                         + (int) $f['truck'] + (int) $f['taxi'] + (int) $f['bus'];
                if ($freeSum === 0) { return null; }
                return $db->table('spi_vehicle_daily')->where('tanggal', $d['tanggal'])->update([
                    'mobil_free' => (int) $f['mobil'], 'motor_free' => (int) $f['motor'],
                    'box_free'   => (int) $f['box'],   'truck_free' => (int) $f['truck'],
                    'taxi_free'  => (int) $f['taxi'],  'bus_free'   => (int) $f['bus'],
                    'updated_at' => $now,
                ]);
            });
        }

        // Cermin spi_vehicle_daily → daily_vehicles (sumber kendaraan Event Summary).
        $totVeh = $this->tulisCermin($db, $from, $to, $now);

        $ringkas = $this->ringkasan($totVeh);
        $gagal   = $this->totalGagal();
        if ($gagal > 0) {
            CLI::error("Selesai dengan galat ({$gagal} gagal). {$ringkas}.");
            foreach ($this->galat as $jenis => $msg) { CLI::error("  - {$jenis}: {$msg}"); }
            log_message('error', "[spi-sync] {$from}..{$to} {$gagal} gagal: {$ringkas} | "
                . implode(' | ', array_map(fn($j, $m) => "{$j}: {$m}", array_keys($this->galat), $this->galat)));
        } else {
            CLI::write("Selesai. {$ringkas}.", 'green');
        }
        try {
            ActivityLog::write('update', 'spi_parking', null,
                "sync SPI {$from}..{$to}" . ($gagal > 0 ? " (GAGAL {$gagal})" : '') . ": {$ringkas}");
        } catch (\Throwable $e) {
            log_message('warning', '[spi-sync] activity log: ' . $e->getMessage());
        }
        return $gagal > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    // ── Koneksi DB & pencatatan hasil tulis ─────────────────────

    /**
     * Sambung ulang koneksi DB lalu naikkan wait_timeout sesi. Dipanggil sebelum tiap
     * blok tulis: panggilan SPI di antaranya bisa melewati wait_timeout hosting (30 dtk).
     * Pengaturan SET SESSION hilang saat reconnect → selalu diset ulang di sini.
     */
    public function siapkanKoneksi($db): void
    {
        try {
            $db->reconnect();
        } catch (\Throwable $e) {
            // close() pada koneksi yang sudah diputus server bisa melempar → paksa sambung baru.
            $db->connID = false;
            $db->initialize();
        }
        $t = (int) self::DB_SESSION_TIMEOUT;
        if ($db->query("SET SESSION wait_timeout = {$t}, interactive_timeout = {$t}") === false) {
            throw new \RuntimeException('SET SESSION wait_timeout gagal: ' . $this->dbError($db));
        }
    }

    /**
     * Tulis sekumpulan baris dalam satu blok: sambung ulang dulu, lalu periksa hasil
     * tiap tulis. $tulis($db, $baris) mengembalikan hasil query builder (false = gagal),
     * atau null untuk melewati baris. Return jumlah baris berhasil di blok ini.
     */
    public function tulisBlok($db, string $jenis, array $rows, callable $tulis): int
    {
        if (! $rows) { return 0; }
        $this->hasil[$jenis] ??= ['ok' => 0, 'gagal' => 0];
        try {
            $this->siapkanKoneksi($db);
        } catch (\Throwable $e) {
            // Tetap coba tulis: tiap kegagalan akan tercatat di bawah.
            $this->catatGalat($jenis, 'sambung ulang DB: ' . $e->getMessage());
        }
        $ok = 0;
        foreach ($rows as $r) {
            $msg = '';
            try {
                $res = $tulis($db, $r);
            } catch (\Throwable $e) {
                $res = false;
                $msg = $e->getMessage();
            }
            if ($res === null) { continue; } // sengaja dilewati
            if ($res === false) {
                $this->hasil[$jenis]['gagal']++;
                $this->catatGalat($jenis, $msg !== '' ? $msg : $this->dbError($db));
                continue;
            }
            $this->hasil[$jenis]['ok']++;
            $ok++;
        }
        return $ok;
    }

    /**
     * Ambil data dari SPI; galat dicatat sebagai 1 kegagalan jenis itu dan return null
     * (sync lanjut ke data lain — retry otomatis di run berikutnya).
     */
    public function ambil(string $jenis, string $ket, callable $fetch): ?array
    {
        $this->hasil[$jenis] ??= ['ok' => 0, 'gagal' => 0];
        try {
            return (array) $fetch();
        } catch (\Throwable $e) {
            $this->hasil[$jenis]['gagal']++;
            $this->catatGalat($jenis, "ambil {$ket}: " . $e->getMessage());
            CLI::error("  {$jenis} {$ket}: " . $e->getMessage());
            return null;
        }
    }

    /** Cermin daily_vehicles dalam blok tulis yang diperiksa. Return jumlah baris sumber. */
    private function tulisCermin($db, string $from, string $to, string $now): int
    {
        $n = 0;
        $this->tulisBlok($db, 'daily_vehicles', [1], function ($db) use ($from, $to, $now, &$n) {
            $res = $this->mirrorVehicles($db, $from, $to, $now);
            if ($res === false) { return false; }
            $n = $res;
            return true;
        });
        return $n;
    }

    public function resetHasil(): void
    {
        $this->hasil = array_fill_keys(self::JENIS, ['ok' => 0, 'gagal' => 0]);
        $this->galat = [];
    }

    /** @return array<string,array{ok:int,gagal:int}> */
    public function getHasil(): array { return $this->hasil; }

    /** @return array<string,string> */
    public function getGalat(): array { return $this->galat; }

    public function totalGagal(): int
    {
        return array_sum(array_column($this->hasil, 'gagal'));
    }

    /** "qty=31 income=31 ... payment=120 (gagal 2)" — jujur per jenis data. */
    public function ringkasan(?int $dailyVehicles = null): string
    {
        $parts = [];
        foreach ($this->hasil as $jenis => $h) {
            $n = ($jenis === 'daily_vehicles' && $dailyVehicles !== null) ? $dailyVehicles : $h['ok'];
            $parts[] = "{$jenis}={$n}" . ($h['gagal'] > 0 ? " (gagal {$h['gagal']})" : '');
        }
        return implode(' ', $parts);
    }

    private function catatGalat(string $jenis, string $msg): void
    {
        $msg = trim(preg_replace('/\s+/', ' ', $msg));
        if (! isset($this->galat[$jenis])) { $this->galat[$jenis] = mb_substr($msg !== '' ? $msg : 'tak diketahui', 0, 200); }
    }

    private function dbError($db): string
    {
        try {
            $e = $db->error();
            return trim(($e['code'] ?? '') . ' ' . ($e['message'] ?? '')) ?: 'tulis DB gagal';
        } catch (\Throwable $t) {
            return 'tulis DB gagal';
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

    /**
     * Cermin spi_vehicle_daily → daily_vehicles untuk rentang [from..to] (upsert per tanggal).
     * Kolom *_free authoritatif dari spi_vehicle_daily. Return jumlah baris sumber, false bila gagal.
     */
    private function mirrorVehicles($db, string $from, string $to, string $now): int|false
    {
        $ok = $db->query(
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
        if ($ok === false) { return false; }
        return (int) $db->table('spi_vehicle_daily')
            ->where('tanggal >=', $from)->where('tanggal <=', $to)->countAllResults();
    }
}
