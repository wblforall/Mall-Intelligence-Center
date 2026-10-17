<?php

namespace App\Commands;

use App\Libraries\BudgetFsSinkron;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sinkron cermin BTB FlowStore → antrean tab "FlowStore" di Budget Departemen.
 *
 *   php spark mic:fs-sync                      # dari TANGGAL MULAI penarikan (bawaan 1 Okt 2026) sampai hari ini, semua dept yang punya pemetaan divisi
 *   php spark mic:fs-sync --dari 2026-01-01 --sampai 2026-10-17 --dept 10
 *
 * `--dari` SELALU dipangkas ke tanggal mulai penarikan dept (barang diterima sebelumnya sudah diisi dari Excel); item lama
 * yang masih 'baru' ikut dibersihkan dari cermin. Tanggal mulai: .env flowstore.mulai atau tabel budget_fs_setelan (diatur admin di tab).
 * Idempoten dan aman dijalankan cron. TIDAK mengubah budget_realisasi — hanya cermin & antrean.
 * Saran cron (sekali sehari pukul 06:15):  15 6 * * *  php /path/spark mic:fs-sync >> writable/logs/fs-sync.log 2>&1
 */
class FsSync extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:fs-sync';
    protected $description = 'Sinkron BTB FlowStore ke antrean Budget Departemen (tanpa mengubah realisasi).';
    protected $usage       = 'mic:fs-sync [--dari YYYY-MM-DD] [--sampai YYYY-MM-DD] [--dept ID]';
    protected $options     = [
        '--dari'   => 'Tanggal terima awal (bawaan = tanggal mulai penarikan; tak pernah lebih awal dari itu)',
        '--sampai' => 'Tanggal terima akhir (bawaan: hari ini)',
        '--dept'   => 'Hanya divisi milik dept MIC ini',
    ];

    public function run(array $params)
    {
        $dari   = CLI::getOption('dari');
        $sampai = CLI::getOption('sampai');
        $dept   = CLI::getOption('dept');

        $s = new BudgetFsSinkron();
        CLI::write('Sumber: ' . $s->feed()->mode(), 'light_gray');
        $h = $s->jalankan(is_string($dari) ? $dari : null, is_string($sampai) ? $sampai : null, is_string($dept) && $dept !== '' ? (int) $dept : null, null);

        if (! $h['ok']) {
            CLI::error('Sinkron GAGAL: ' . $h['galat']);
            return EXIT_ERROR;
        }
        if (! empty($h['dipangkas'])) CLI::write('Catatan: rentang dipangkas ke tanggal mulai penarikan (sebelum itu dicatat manual/Excel).', 'yellow');
        if (! empty($h['dihapus'])) CLI::write($h['dihapus'] . ' item lama (sebelum tanggal mulai, masih belum diproses) dibuang dari cermin.', 'yellow');
        CLI::write(sprintf('Selesai: %d item dibaca, %d baru, %d diperbarui, %d berubah jadi batal.', $h['jumlah'], $h['baru'], $h['diperbarui'], $h['batal']), 'green');
        return EXIT_SUCCESS;
    }
}
