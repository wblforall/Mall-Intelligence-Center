<?php

namespace App\Commands;

use App\Libraries\AiEkonomi as Analis;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Analisa AI Ekonomi untuk tab Market Intelligence (Dashboard).
 *
 * Dua tahap yang dipisah jelas:
 *   RISET (berbayar, web search): Claude mencari angka terbaru BI-Rate, inflasi, PDB/PDRB, IHSG,
 *     harga BBM dan headline dari sumber resmi/berita tepercaya; hasil divalidasi kode lalu disimpan
 *     sebagai snapshot sumber 'web' di eco_snapshots. JADWAL: SEKALI SEMINGGU, Senin pagi.
 *   ANALISIS (Claude menulis JSON ketat → ai_ekonomi): memakai fakta terkini + snapshot riset
 *     TERAKHIR; tidak mencari ulang. Bila fakta sama dengan analisa terakhir hari ini, dilewati.
 *
 * Opsi:
 *   --mingguan    riset LALU analisis (untuk cron mingguan; satu perintah)
 *   --riset-saja  hanya riset (tanpa analisis)
 *   --jalan       hanya analisis, memakai snapshot riset terakhir (tanpa pencarian)
 *   --coba        dry-run: tidak menulis APA PUN (tanpa snapshot, tanpa analisa). Berdiri sendiri =
 *                 analisis tanpa riset; boleh digabung dengan --riset-saja / --mingguan
 *                 (riset tetap memanggil API berbayar, hasilnya hanya ditampilkan)
 *   --tanpa-ai    bersama --coba: hanya tampilkan fakta, tanpa memanggil AI analisis
 *   --paksa       abaikan hash sama (tetap memanggil AI analisis)
 *
 * Kode keluar: 0 sukses/sudah sesuai (kegagalan riset TIDAK fatal; analisis lanjut dengan jalur lama),
 * 1 gagal total (AI/kunci/validasi analisis).
 *
 * Cron (SATU baris, SENIN 08.00 WITA) — BELUM dipasang di server mana pun:
 *   0 8 * * 1 cd /path/mall-intelligence-center && /usr/bin/php spark mic:ai-ekonomi --mingguan >> writable/logs/ai-ekonomi.log 2>&1
 *
 * PERHATIAN ZONA WAKTU: cron memakai zona waktu SERVER, yang bisa bukan WITA. Senin 08.00 WITA (UTC+8)
 * = Senin 00.00 UTC, jadi bila server berzona UTC tulis `0 0 * * 1 ...` (atau pasang CRON_TZ=Asia/Makassar
 * bila didukung). Bila zona server lain, geser hari/jam sesuai selisihnya. Cek dulu dengan `date`.
 * Jalankan sebagai user yang sama dengan pemilik folder writable/.
 *
 * Kunci API dibaca dari .env: aiekonomi.key (opsional) atau provider aiklas.pN_* bertipe anthropic.
 * Model: aiekonomi.model (bawaan claude-sonnet-5-5). Web search harus aktif di organisasi Anthropic.
 */
class AiEkonomi extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:ai-ekonomi';
    protected $description = 'Buat analisa AI ekonomi harian (tab Market Intelligence Dashboard).';
    protected $usage       = 'mic:ai-ekonomi --mingguan|--riset-saja|--jalan|--coba [--coba] [--paksa] [--tanpa-ai]';

    /** Baca `--nama`, `--nama=nilai`, atau `--nama nilai` (null bila tak ada). */
    private function opsi(string $nama): ?string
    {
        $argv = $_SERVER['argv'] ?? [];
        foreach ($argv as $i => $a) {
            if ($a === "--{$nama}") {
                $n = $argv[$i + 1] ?? null;
                return ($n !== null && ! str_starts_with($n, '--')) ? $n : '';
            }
            if (str_starts_with($a, "--{$nama}=")) return substr($a, strlen($nama) + 3);
        }
        return null;
    }

    public function run(array $params)
    {
        $coba     = $this->opsi('coba') !== null;
        $jalan    = $this->opsi('jalan') !== null;
        $mingguan = $this->opsi('mingguan') !== null;
        $risetSaja = $this->opsi('riset-saja') !== null;
        $mode = (int) $jalan + (int) $mingguan + (int) $risetSaja;
        if (($mode === 0 && ! $coba) || $mode > 1) {
            CLI::error('Pilih tepat satu mode: --mingguan, --riset-saja, --jalan, atau --coba (boleh digabung dengan --riset-saja/--mingguan).');
            CLI::write($this->usage);
            return EXIT_USER_INPUT;
        }
        $paksa  = $this->opsi('paksa') !== null;
        $tanpa  = $this->opsi('tanpa-ai') !== null;
        $mulai  = microtime(true);
        $nama   = $mingguan ? '--mingguan' : ($risetSaja ? '--riset-saja' : ($jalan ? '--jalan' : ''));
        CLI::write('[' . date('Y-m-d H:i:s') . ' WITA] mic:ai-ekonomi ' . trim($nama . ($coba ? ' --coba' : '') . ($paksa ? ' --paksa' : '')));

        // ── Hanya riset ──
        if ($risetSaja) {
            Analis::$keringTanpaTulis = $coba;
            try {
                $rr = Analis::riset(['coba' => $coba]);
            } finally {
                Analis::$keringTanpaTulis = false;
            }
            $this->laporRiset($rr, $coba);
            return EXIT_SUCCESS; // riset gagal bukan galat fatal
        }

        // ── Analisis (dengan riset lebih dulu bila --mingguan) ──
        $r = Analis::buat(['paksa' => $paksa || $mingguan, 'coba' => $coba, 'tanpa_ai' => $tanpa, 'oleh' => null, 'riset' => $mingguan]);
        $dt = round(microtime(true) - $mulai, 1);

        if (! empty($r['riset'])) $this->laporRiset($r['riset'], $coba);

        if ($coba) {
            CLI::write('── FAKTA ──', 'yellow');
            CLI::write(json_encode($r['fakta'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if (isset($r['hasil'])) {
                CLI::write('── HASIL AI ──', 'yellow');
                CLI::write(json_encode($r['hasil'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }

        $ring = sprintf('status=%s token=%d/%d %ss — %s', $r['status'], $r['token_masuk'] ?? 0, $r['token_keluar'] ?? 0, $dt, $r['pesan']);
        if (! empty($r['fakta']['data_hilang'])) {
            $ring .= ' | hilang: ' . implode(', ', $r['fakta']['data_hilang']);
        }

        if (in_array($r['status'], ['gagal', 'tanpa_kunci'], true)) {
            CLI::error($ring);
            log_message('error', '[mic:ai-ekonomi] ' . $ring);
            return EXIT_ERROR;
        }
        CLI::write($ring, $r['status'] === 'ok' ? 'green' : null);
        log_message('info', '[mic:ai-ekonomi] ' . $ring);
        return EXIT_SUCCESS;
    }

    /** Log ringkas riset: pencarian, token, indikator berhasil/gagal, estimasi biaya pencarian. */
    private function laporRiset(array $rr, bool $coba): void
    {
        $gagal = [];
        foreach ($rr['gagal'] ?? [] as $id => $alasan) $gagal[] = "{$id} ({$alasan})";
        $baris = sprintf(
            'riset: status=%s%s pencarian=%d token=%d/%d tool=%s berhasil=[%s] headline=%d gagal=[%s]%s — %s',
            $rr['status'], $coba ? ' (coba, tidak ditulis)' : '', $rr['pencarian'], $rr['token_masuk'], $rr['token_keluar'], $rr['tool'] ?: '-',
            implode(',', $rr['berhasil'] ?? []), $rr['berita'] ?? 0, implode('; ', $gagal),
            ! empty($rr['galat_tool']) ? ' galat_tool=[' . implode(',', $rr['galat_tool']) . ']' : '', $rr['pesan']
        );
        foreach ($rr['putaran'] ?? [] as $i => $q) {
            $baris .= sprintf(' | req%d: in=%d out=%d cari=%d stop=%s', $i + 1, $q['masuk'], $q['keluar'], $q['cari'], $q['stop']);
        }
        $baris .= sprintf(' | biaya pencarian ≈ $%.2f', $rr['pencarian'] * 0.01);
        $ok = $rr['status'] === 'ok';
        CLI::write($baris, $ok ? 'green' : 'yellow');
        foreach ($rr['catatan_riset'] ?? [] as $c) CLI::write('  catatan riset: ' . $c);
        foreach ($rr['dipertahankan'] ?? [] as $id => $a) CLI::write("  dipertahankan {$id}: {$a}");
        log_message($ok ? 'info' : 'warning', '[mic:ai-ekonomi] ' . $baris);
    }
}
