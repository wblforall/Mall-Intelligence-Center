<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\AiKlasifikasi as Klasifikator;

/**
 * Klasifikasi sesi Pemantauan AI (berbasis kata kunci, gratis).
 *
 * Mengisi kolom klasifikasi_* pada ai_sessions dari isi prompt + metadata.
 * Idempoten: hanya menyentuh sesi yang belum pernah diklasifikasi ATAU yang
 * bertambah entrinya sejak terakhir diklasifikasi (klasifikasi_at < terakhir_at),
 * sehingga aman dijalankan berulang dari cron.
 *
 * Jadwal cron (contoh, tiap 10 menit):
 *   *\/10 * * * *  cd /path/mall-intelligence-center && php spark mic:ai-klasifikasi >> writable/logs/ai-klasifikasi.log 2>&1
 *
 * TODO (versi AI): tambah command/flag terpisah (mis. --metode=ai) yang
 * memanggil AiKlasifikasi::ai(...) untuk sebagian sesi dan menyimpan
 * klasifikasi_metode='ai'. Skema kolom sudah siap menampungnya.
 */
class AiKlasifikasi extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:ai-klasifikasi';
    protected $description = 'Klasifikasikan sesi Pemantauan AI (jenis aktivitas, tema, kantor/pribadi) berbasis kata kunci.';
    protected $usage       = 'mic:ai-klasifikasi [--batas 500] [--dry-run]';

    /** Maksimal entri prompt yang dibaca per sesi (jaga memori). */
    private const MAKS_ENTRI = 50;

    public function run(array $params)
    {
        $batas  = (int) (CLI::getOption('batas') ?: 500);
        $dryRun = (bool) CLI::getOption('dry-run');
        $db     = db_connect();

        // Sesi yang perlu (re)klasifikasi: belum pernah, atau sudah bertambah
        // entrinya sejak terakhir diklasifikasi.
        $sesi = $db->table('ai_sessions')
            ->select('id, proyek, git_branch, jml_alat')
            ->groupStart()
                ->where('klasifikasi_at IS NULL', null, false)
                ->orWhere('klasifikasi_at < terakhir_at', null, false)
            ->groupEnd()
            ->orderBy('terakhir_at', 'DESC')
            ->limit($batas)
            ->get()->getResultArray();

        CLI::write('Sesi perlu diklasifikasi: ' . count($sesi), 'cyan');

        $n   = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($sesi as $s) {
            $sesiId = (int) $s['id'];

            // Kumpulkan isi prompt manusia pada sesi ini (dibatasi).
            $rows = $db->table('ai_entries')
                ->select('isi')
                ->where('ai_session_id', $sesiId)
                ->where('jenis', 'prompt')
                ->where('isi IS NOT NULL', null, false)
                ->orderBy('waktu', 'ASC')
                ->limit(self::MAKS_ENTRI)
                ->get()->getResultArray();
            $teks = array_column($rows, 'isi');

            $hasil = Klasifikator::kataKunci(
                $teks,
                $s['proyek'] ?? null,
                $s['git_branch'] ?? null,
                (int) $s['jml_alat']
            );

            if (! $dryRun) {
                $db->table('ai_sessions')->where('id', $sesiId)->update([
                    'klasifikasi_jenis'  => $hasil['jenis'],
                    'klasifikasi_tema'   => $hasil['tema'],
                    'klasifikasi_kantor' => $hasil['kantor'],
                    'klasifikasi_metode' => 'kata_kunci',
                    'klasifikasi_at'     => $now,
                ]);
            }
            $n++;
        }

        CLI::write("Selesai. Diklasifikasi: {$n}" . ($dryRun ? ' (dry-run, tidak disimpan)' : ''), 'green');
    }
}
