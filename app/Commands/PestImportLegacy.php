<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Impor rekap bulanan Pest Control 2025–2026 dari CSV hasil ekstraksi dua
 * berkas Excel PestCare.
 *
 * Berkas asalnya rekap BULANAN dan tidak memuat tanggal kunjungan. Tanggalnya
 * TIDAK dikarang: tiap bulan menjadi satu baris pest_visits bertanda
 * sumber='rekap_legacy' dengan tanggal hari terakhir bulan itu. Baris bertanda
 * itu ikut di rekap bulanan & pembanding antar tahun, tetapi dikecualikan dari
 * tren mingguan — di sana satuannya minggu, dan data ini tidak punya
 * resolusi sehalus itu.
 *
 * Aman dijalankan ulang: bulan yang sudah terimpor dilewati.
 */
class PestImportLegacy extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:pest-import-legacy';
    protected $description = 'Impor rekap bulanan Pest Control 2025-2026 dari CSV (sumber=rekap_legacy).';
    protected $usage       = 'mic:pest-import-legacy [--file PATH] [--dry]';
    protected $options     = [
        '--file' => 'Path CSV (default: data/pest-legacy-2025-2026.csv)',
        '--dry'  => 'Tampilkan rencana tanpa menulis apa pun.',
    ];

    public function run(array $params)
    {
        $file = CLI::getOption('file') ?: ROOTPATH . 'data/pest-legacy-2025-2026.csv';
        $dry  = (bool) CLI::getOption('dry');

        if (! is_file($file)) {
            CLI::error('Berkas tidak ditemukan: ' . $file);
            return EXIT_ERROR;
        }

        $db = \Config\Database::connect();

        // Peta nama item → id. Item yang tidak dikenal menghentikan impor:
        // lebih baik gagal keras daripada diam-diam membuang sebagian data.
        $items = [];
        foreach ($db->table('pest_items')->get()->getResultArray() as $r) {
            $items[mb_strtolower($r['nama'])] = (int) $r['id'];
        }

        $fh = fopen($file, 'r');
        $header = fgetcsv($fh);
        if ($header !== ['mall', 'tahun', 'bulan', 'item', 'jumlah']) {
            CLI::error('Header CSV tidak sesuai. Diharapkan: mall,tahun,bulan,item,jumlah');
            fclose($fh);
            return EXIT_ERROR;
        }

        // Kumpulkan dulu per (mall, bulan) — satu kunjungan per bulan.
        $bucket = [];
        $baris  = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 5) continue;
            [$mall, $tahun, $bulan, $item, $jumlah] = $row;

            $kunci = mb_strtolower(trim($item));
            if (! isset($items[$kunci])) {
                CLI::error('Item tidak dikenal di master: "' . $item . '" — tambahkan dulu lewat /pest-items.');
                fclose($fh);
                return EXIT_ERROR;
            }
            if (! in_array($mall, ['ewalk', 'pentacity'], true)) {
                CLI::error('Mall tidak dikenal: ' . $mall);
                fclose($fh);
                return EXIT_ERROR;
            }

            // Hari terakhir bulan itu — tapi TIDAK PERNAH melewati hari ini.
            // Tanpa batas ini, mengimpor bulan berjalan melahirkan kunjungan
            // bertanggal masa depan (ditemukan saat pengujian: rekap September
            // 2026 mendarat di 30 Sep padahal hari ini baru tanggal 16).
            $tanggal = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan)));
            if ($tanggal > date('Y-m-d')) $tanggal = date('Y-m-d');
            $bucket[$mall . '|' . $tanggal][$items[$kunci]] = (int) $jumlah;
            $baris++;
        }
        fclose($fh);

        CLI::write('Berkas   : ' . $file);
        CLI::write('Baris CSV: ' . $baris);
        CLI::write('Kunjungan: ' . count($bucket) . ' (satu per bulan per mall)');
        CLI::newLine();

        $dibuat = 0; $dilewati = 0; $temuan = 0;

        foreach ($bucket as $kunci => $perItem) {
            [$mall, $tanggal] = explode('|', $kunci);

            // Diperiksa per BULAN, bukan per tanggal.
            //
            // Ini bukan detail: tanggal baris bulan berjalan dibatasi ke hari
            // ini, sehingga menjalankan importer di hari yang berbeda
            // menghasilkan tanggal yang berbeda pula untuk bulan yang sama.
            // Kalau yang dibandingkan tanggalnya, bulan berjalan akan terimpor
            // ULANG setiap hari dan angkanya berlipat diam-diam —
            // UNIQUE(mall, tanggal, sumber) tidak mencegahnya karena
            // tanggalnya memang beda. Yang harus unik di sini adalah BULANnya.
            $ada = $db->table('pest_visits')
                ->where('mall', $mall)
                ->where("DATE_FORMAT(tanggal, '%Y-%m')", substr($tanggal, 0, 7))
                ->where('sumber', 'rekap_legacy')
                ->get()->getRowArray();

            if ($ada) { $dilewati++; continue; }

            if ($dry) { $dibuat++; $temuan += count($perItem); continue; }

            $db->transStart();
            $db->table('pest_visits')->insert([
                'mall'       => $mall,
                'tanggal'    => $tanggal,
                'vendor'     => 'PestCare',
                'sumber'     => 'rekap_legacy',
                'catatan'    => 'Impor rekap bulanan Excel ' . substr($tanggal, 0, 7),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $visitId = (int) $db->insertID();

            $rows = [];
            foreach ($perItem as $itemId => $jumlah) {
                if ($jumlah <= 0) continue;   // nol tidak disimpan
                $rows[] = [
                    'visit_id'   => $visitId,
                    'item_id'    => $itemId,
                    'area_id'    => 0,        // rekap bulanan tak punya rincian area
                    'jumlah'     => $jumlah,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }
            if ($rows) $db->table('pest_findings')->insertBatch($rows);
            $db->transComplete();

            if ($db->transStatus() === false) {
                CLI::error('  GAGAL ' . $mall . ' ' . $tanggal);
                continue;
            }
            $dibuat++; $temuan += count($rows);
        }

        CLI::newLine();
        CLI::write(($dry ? '[DRY RUN] ' : '') . 'Kunjungan dibuat : ' . $dibuat, 'green');
        CLI::write(($dry ? '[DRY RUN] ' : '') . 'Baris temuan     : ' . $temuan, 'green');
        if ($dilewati) CLI::write('Sudah terimpor    : ' . $dilewati, 'dark_gray');

        return EXIT_SUCCESS;
    }
}
