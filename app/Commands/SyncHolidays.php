<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\HolidaySyncService;
use App\Libraries\ActivityLog;

/**
 * Sinkronkan hari libur nasional dari kalender Google.
 * Jalankan via cron tiap awal bulan: 0 3 1 * * php spark mic:holidays-sync
 * Sinkron tahun berjalan + tahun depan (agar kalender Des→Jan sudah siap).
 */
class SyncHolidays extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:holidays-sync';
    protected $description = 'Tarik hari libur nasional Indonesia dari feed kalender Google (tahun ini + tahun depan).';

    public function run(array $params)
    {
        $service = new HolidaySyncService();
        $years   = [(int) date('Y'), (int) date('Y') + 1];

        $totalNew = 0;
        foreach ($years as $year) {
            $res = $service->sync($year);
            if (! $res['ok']) {
                CLI::write("Gagal sync {$year} — feed tidak dapat diambil.", 'red');
                log_message('error', "[holidays-sync] gagal fetch {$year}");
                continue;
            }
            $totalNew += $res['inserted'];
            CLI::write("{$year}: +{$res['inserted']} baru, {$res['skipped']} sudah ada.", 'green');
        }

        // Catat ke activity log (CLI: tanpa sesi → user_name 'System'); guard agar job tak gagal
        try {
            ActivityLog::write('create', 'public_holiday', null,
                'auto-sync kalender (cron): +' . $totalNew . ' libur baru');
        } catch (\Throwable $e) {
            log_message('warning', '[holidays-sync] gagal tulis activity log: ' . $e->getMessage());
        }

        CLI::write("Selesai. Total {$totalNew} hari libur baru ditambahkan.", 'cyan');
    }
}
