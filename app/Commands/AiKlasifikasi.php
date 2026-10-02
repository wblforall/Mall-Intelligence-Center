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
    protected $usage       = 'mic:ai-klasifikasi [--batas 500] [--dry-run] [--ulang]';

    /** Maksimal entri prompt yang dibaca per sesi (jaga memori). */
    private const MAKS_ENTRI = 50;

    /** Berapa kali 429 beruntun sebelum batch dihentikan rapi.
     *  Dilonggarkan (8) agar 429 sesekali tak langsung membatalkan batch;
     *  dipasangkan dengan jeda ~3,5 dtk di bawah agar tetap di bawah
     *  batas per-menit model gratis (~20/menit). */
    private const MAKS_429_BERUNTUN = 8;

    public function run(array $params)
    {
        $batas  = (int) (CLI::getOption('batas') ?: 500);
        $dryRun = (bool) CLI::getOption('dry-run');
        $ulang  = (bool) CLI::getOption('ulang'); // paksa klasifikasi ulang SEMUA
        $db     = db_connect();

        // Mode: 'ai' memakai LLM dengan fallback kata kunci; selain itu
        // (termasuk kosong) murni kata kunci. AI hanya aktif bila setidaknya
        // satu provider terkonfigurasi (numbered p1_* atau format lama).
        $mode      = strtolower((string) (env('aiklas.mode') ?: 'kata_kunci'));
        $punyaKey  = Klasifikator::terkonfigurasi();
        $pakaiAi   = ($mode === 'ai') && $punyaKey;
        CLI::write('Mode: ' . ($pakaiAi ? 'ai (multi-provider, fallback kata_kunci)' : 'kata_kunci'), 'cyan');

        // Sesi yang perlu (re)klasifikasi: belum pernah, atau sudah bertambah
        // entrinya sejak terakhir diklasifikasi.
        $sesi = $db->table('ai_sessions')
            ->select('id, proyek, git_branch, jml_alat');
        if (! $ulang) {
            // Normal: hanya yang belum pernah atau bertambah sejak terakhir.
            $sesi->groupStart()
                ->where('klasifikasi_at IS NULL', null, false)
                ->orWhere('klasifikasi_at < terakhir_at', null, false)
            ->groupEnd();
        } // --ulang: ambil SEMUA (regenerasi label, mis. setelah aturan berubah)
        $sesi = $sesi->orderBy('terakhir_at', 'DESC')
            ->limit($batas)
            ->get()->getResultArray();

        CLI::write('Sesi perlu diklasifikasi: ' . count($sesi), 'cyan');

        $viaAi   = 0;
        $viaKw   = 0;
        $beruntun429 = 0;
        $perProvider = []; // label provider => jumlah sukses
        $now     = date('Y-m-d H:i:s');

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
            $teks   = array_column($rows, 'isi');
            $proyek = $s['proyek'] ?? null;
            $branch = $s['git_branch'] ?? null;
            $alat   = (int) $s['jml_alat'];

            $hasil  = null;
            $metode = 'kata_kunci';

            if ($pakaiAi) {
                $hasil = Klasifikator::ai($teks, $proyek, $branch, $alat);
                if ($hasil !== null) {
                    $metode = 'ai';
                    $prov   = Klasifikator::$providerTerakhir ?: '?';
                    $perProvider[$prov] = ($perProvider[$prov] ?? 0) + 1;
                    $beruntun429 = 0; // sukses → reset penghitung rate-limit
                } else {
                    // Semua provider gagal untuk sesi ini (bisa 429/hang/err/
                    // JSON invalid). ai() menelan detail; bila layanan sedang
                    // dibatasi/mati, kegagalan terjadi terus-menerus.
                    $beruntun429++;
                }
                // Jeda antar sesi. Provider utama (p1) di prod BUKAN tier
                // "free-per-day" OpenRouter, jadi 1 dtk sudah cukup sopan dan
                // jauh lebih cepat daripada 3,5 dtk.
                usleep(1000000); // 1 detik
            }

            if ($hasil === null) {
                $hasil  = Klasifikator::kataKunci($teks, $proyek, $branch, $alat);
                $metode = 'kata_kunci';
            }

            if (! $dryRun) {
                $db->table('ai_sessions')->where('id', $sesiId)->update([
                    'klasifikasi_jenis'  => $hasil['jenis'],
                    'klasifikasi_tema'   => $hasil['tema'],
                    'klasifikasi_kantor' => $hasil['kantor'],
                    'klasifikasi_metode' => $metode,
                    'klasifikasi_at'     => $now,
                ]);
            }
            $metode === 'ai' ? $viaAi++ : $viaKw++;

            // Bila AI gagal beruntun (kemungkinan rate-limited), hentikan batch
            // dengan rapi; sisanya diproses run berikutnya (tetap idempoten).
            if ($pakaiAi && $beruntun429 >= self::MAKS_429_BERUNTUN) {
                CLI::write('Peringatan: AI gagal/rate-limited beruntun — batch dihentikan, sisanya menyusul di run berikutnya.', 'yellow');
                break;
            }
        }

        $rincian = '';
        if ($perProvider !== []) {
            arsort($perProvider);
            $bagian = [];
            foreach ($perProvider as $label => $n) {
                $bagian[] = "{$label}={$n}";
            }
            $rincian = ' [' . implode(', ', $bagian) . ']';
        }

        CLI::write(
            "Selesai. via ai: {$viaAi}{$rincian} · via kata_kunci: {$viaKw}" . ($dryRun ? ' (dry-run, tidak disimpan)' : ''),
            'green'
        );
    }
}
