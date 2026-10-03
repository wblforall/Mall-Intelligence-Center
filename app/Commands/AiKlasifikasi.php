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
    protected $usage       = 'mic:ai-klasifikasi [--batas 500] [--dry-run] [--ulang] [--tak-jelas]';

    /** Maksimal entri prompt yang dibaca per sesi (jaga memori). */
    private const MAKS_ENTRI = 50;

    /** Alat Claude yang `sasaran`-nya berupa jalur/pola berkas (bukan perintah shell —
     *  perintah bisa memuat isi data, jadi tidak dikirim). */
    private const ALAT_BERKAS = ['Read', 'Write', 'Edit', 'MultiEdit', 'Glob', 'NotebookEdit', 'NotebookRead'];

    /** Jejak berkas yang bukan pekerjaan pengguna: memori/konfigurasi Claude & berkas sementara. */
    private const JALUR_ABAIKAN = ['/.claude/', '\\.claude\\', 'appdata\\local\\temp', '/tmp/', 'scratchpad'];

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
        $takJelas = (bool) CLI::getOption('tak-jelas'); // klasifikasi ulang yang masih tak_jelas
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
        $sesi = $db->table('ai_sessions s')
            ->select('s.id, s.proyek, s.cwd, s.git_branch, s.jml_alat, d.name AS dept')
            ->join('employees e', 'e.id = s.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left');
        if ($takJelas) {
            $sesi->where('s.klasifikasi_kantor', 'tak_jelas');
        } elseif (! $ulang) {
            // Normal: hanya yang belum pernah atau bertambah sejak terakhir.
            $sesi->groupStart()
                ->where('s.klasifikasi_at IS NULL', null, false)
                ->orWhere('s.klasifikasi_at < s.terakhir_at', null, false)
            ->groupEnd();
        } // --ulang: ambil SEMUA (regenerasi label, mis. setelah aturan berubah)
        $sesi = $sesi->orderBy('s.terakhir_at', 'DESC')
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
            $konteks = [
                'folder' => self::folderRingkas($s['cwd'] ?? null),
                'berkas' => $this->berkasSesi($db, $sesiId),
                'dept'   => $s['dept'] ?? null,
            ];

            $hasil  = null;
            $metode = 'kata_kunci';

            if ($pakaiAi) {
                $hasil = Klasifikator::ai($teks, $proyek, $branch, $alat, $konteks);
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
                // Jeda antar sesi. Provider utama (p1) di prod adalah Groq
                // (free ~30 req/menit) → ~2 dtk/panggilan menjaga laju di
                // bawah ambang itu, dan tetap jauh lebih cepat dari 3,5 dtk.
                usleep(2000000); // 2 detik
            }

            if ($hasil === null) {
                $hasil  = Klasifikator::kataKunci($teks, $proyek, $branch, $alat, $konteks);
                $metode = 'kata_kunci';
            }

            if ($dryRun || $takJelas) {
                CLI::write(sprintf('  #%d → %s / %s / %s [%s]', $sesiId, $hasil['kantor'], $hasil['jenis'], $hasil['tema'], $metode));
            }

            if (! $dryRun) {
                $data = [
                    'klasifikasi_jenis'  => $hasil['jenis'],
                    'klasifikasi_tema'   => $hasil['tema'],
                    'klasifikasi_kantor' => $hasil['kantor'],
                    'klasifikasi_metode' => $metode,
                    'klasifikasi_at'     => $now,
                ];
                // Ringkasan hanya di-set bila hasil punya nilai (dari AI); saat
                // fallback kata kunci (ringkasan null) ringkasan lama dibiarkan
                // agar tak tertimpa kosong.
                if (! empty($hasil['ringkasan'])) {
                    $data['ringkasan'] = $hasil['ringkasan'];
                }
                $db->table('ai_sessions')->where('id', $sesiId)->update($data);
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

    /** Tiga segmen terakhir cwd (mis. "bond\1. OPERASIONAL\AB") — cukup sebagai petunjuk,
     *  tanpa membawa nama akun Windows di awal jalur. */
    private static function folderRingkas(?string $cwd): ?string
    {
        $bagian = array_values(array_filter(preg_split('/[\\\\\/]+/', trim((string) $cwd)), fn($b) => $b !== '' && ! preg_match('/^[A-Za-z]:$/', $b)));
        return $bagian === [] ? null : implode('\\', array_slice($bagian, -3));
    }

    /**
     * Nama dasar berkas yang dibuka/ditulis Claude di sesi ini (maks 15, unik),
     * tanpa memori Claude dan berkas sementara. Hanya nama — isi berkas tidak dikirim.
     *
     * @return string[]
     */
    private function berkasSesi($db, int $sesiId): array
    {
        $rows = $db->table('ai_entries')
            ->select('sasaran')
            ->where('ai_session_id', $sesiId)
            ->where('jenis', 'alat')
            ->whereIn('alat', self::ALAT_BERKAS)
            ->where('sasaran IS NOT NULL', null, false)
            ->orderBy('waktu', 'ASC')
            ->limit(200)
            ->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $jalur = trim((string) $r['sasaran']);
            $lc    = mb_strtolower($jalur);
            foreach (self::JALUR_ABAIKAN as $abai) {
                if (mb_strpos($lc, $abai) !== false) continue 2;
            }
            $nama = mb_substr(trim((string) preg_replace('#^.*[\\\\/]#', '', $jalur)), 0, 80);
            if ($nama !== '' && ! in_array($nama, $hasil, true)) $hasil[] = $nama;
            if (count($hasil) >= 15) break;
        }
        return $hasil;
    }
}
