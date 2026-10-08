<?php

namespace App\Commands;

use App\Libraries\AiKlasifikasi as Klasifikator;
use App\Libraries\AiSkorPrompt;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Skor mutu prompt per sesi Pemantauan AI (dinilai LLM, tanpa fallback skor).
 *
 * Urutan prosedur: sesi selesai -> diklasifikasi (mic:ai-klasifikasi) -> baru
 * dinilai di sini. Hanya sesi yang SUDAH diklasifikasi dan BUKAN 'pribadi'
 * yang diproses; sesi yang terakhir_at-nya < 30 menit lalu dianggap masih
 * aktif dan ditunda.
 *
 * Bila semua provider LLM gagal (kuota/429/hang/JSON rusak), sesi dibiarkan
 * "Belum dinilai" (skor NULL) dan dicoba lagi di putaran berikutnya setelah
 * jeda 2 jam; tidak pernah diisi skor heuristik. Pola batas laju meniru
 * mic:ai-klasifikasi: jeda 2,5 dtk per panggilan, batch berhenti rapi setelah
 * 8 kegagalan beruntun.
 *
 * Cron (tiap 10 menit, digeser 5 menit setelah klasifikasi):
 *   5-55/10 * * * *  cd /path/mall-intelligence-center && php spark mic:ai-skor --jalan >> writable/logs/ai-skor.log 2>&1
 *
 * Backfill:  php spark mic:ai-skor --coba --sejak=2026-10-01 --batas=10   (tidak menulis)
 *            php spark mic:ai-skor --jalan --sejak=2026-10-01 --batas=100
 */
class AiSkor extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:ai-skor';
    protected $description = 'Nilai mutu prompt sesi Pemantauan AI lewat LLM (sesi pribadi tidak dinilai).';
    protected $usage       = 'mic:ai-skor --coba|--jalan [--sejak=YYYY-MM-DD] [--batas=N]';

    private const MAKS_ENTRI          = 50;
    private const JEDA_USEC           = 2500000; // 2,5 dtk antar panggilan
    private const MAKS_GAGAL_BERUNTUN = 8;
    private const ULANG_SETELAH_JAM   = 2;       // jeda coba ulang sesi yang gagal dinilai
    private const BATAS_BAWAAN        = 30;

    /**
     * Baca opsi `--nama`, `--nama=nilai`, atau `--nama nilai`. CLI::getOption
     * bawaan CI 4.4 tidak mengenal sintaks `=`. Mengembalikan null bila tak ada,
     * '' untuk flag tanpa nilai, selain itu nilainya.
     */
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
        $coba  = $this->opsi('coba') !== null;
        $jalan = $this->opsi('jalan') !== null;
        if ($coba === $jalan) {
            CLI::error('Pilih tepat satu mode: --coba (tidak menulis) atau --jalan.');
            CLI::write($this->usage);
            return;
        }
        $sejak = (string) ($this->opsi('sejak') ?? '');
        if ($sejak !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $sejak)) {
            CLI::error('Format --sejak harus YYYY-MM-DD.');
            return;
        }
        $batas = (int) ($this->opsi('batas') ?: self::BATAS_BAWAAN);
        $batas = max(1, min($batas, 500));
        $db    = db_connect();

        $mode = strtolower((string) (env('aiklas.mode') ?: 'kata_kunci'));
        if ($mode !== 'ai' || ! Klasifikator::terkonfigurasi()) {
            CLI::write('AI tidak aktif/terkonfigurasi (aiklas.mode=ai + provider) — tidak ada yang dinilai.', 'yellow');
            return;
        }
        CLI::write('Mode: ' . ($coba ? 'COBA (tidak menulis)' : 'jalan') . ", batas {$batas}", 'cyan');

        $sekarang  = date('Y-m-d H:i:s');
        $selesaiS  = date('Y-m-d H:i:s', strtotime('-' . AiSkorPrompt::JEDA_SESI_AKTIF . ' minutes'));
        $ulangS    = date('Y-m-d H:i:s', strtotime('-' . self::ULANG_SETELAH_JAM . ' hours'));

        // Privasi: sesi yang (kini) pribadi tak boleh menyimpan skor.
        if ($jalan) {
            $db->query("UPDATE ai_sessions SET skor_prompt = NULL, skor_rincian = NULL, skor_saran = NULL,
                        skor_metode = NULL, skor_model = NULL, skor_at = NULL
                        WHERE klasifikasi_kantor = 'pribadi' AND (skor_metode IS NOT NULL OR skor_at IS NOT NULL)");
        }

        $q = $db->table('ai_sessions s')
            ->select('s.id, s.klasifikasi_jenis, s.klasifikasi_kantor, s.skor_metode')
            ->where('s.klasifikasi_at IS NOT NULL', null, false)           // sudah diklasifikasi
            ->where('s.klasifikasi_kantor <>', 'pribadi')
            ->where('s.employee_id IS NOT NULL', null, false)
            ->where('s.jml_prompt >=', AiSkorPrompt::MIN_PROMPT_MANUSIA)
            ->where('s.terakhir_at <=', $selesaiS)                         // sesi sudah selesai
            ->groupStart()
                // belum pernah dicoba, atau gagal dinilai > 2 jam lalu
                ->groupStart()->where('s.skor_metode IS NULL', null, false)
                    ->groupStart()->where('s.skor_at IS NULL', null, false)->orWhere('s.skor_at <', $ulangS)->groupEnd()
                ->groupEnd()
                // sudah dinilai/dilewati tapi sesi bertambah sejak itu
                ->orGroupStart()->where('s.skor_metode IS NOT NULL', null, false)->where('s.skor_at < s.terakhir_at', null, false)->groupEnd()
            ->groupEnd();
        if ($sejak !== '') $q->where('DATE(s.terakhir_at) >=', $sejak);
        $sesi = $q->orderBy('s.skor_at IS NULL', 'DESC', false)
            ->orderBy('s.terakhir_at', 'DESC')
            ->limit($batas)->get()->getResultArray();

        CLI::write('Sesi perlu dinilai: ' . count($sesi), 'cyan');

        $nilai = $lewati = $gagal = 0;
        $beruntun = 0;
        $perProvider = [];

        foreach ($sesi as $s) {
            $id = (int) $s['id'];
            $rows = $db->table('ai_entries')->select('isi')
                ->where('ai_session_id', $id)->where('jenis', 'prompt')
                ->where('isi IS NOT NULL', null, false)
                ->orderBy('waktu', 'ASC')->limit(self::MAKS_ENTRI)->get()->getResultArray();
            $prompt = array_column($rows, 'isi');

            // Kelayakan (heuristik hanya untuk memutuskan layak/tidak, bukan skor).
            if (! AiSkorPrompt::layakDinilai($prompt)) {
                CLI::write("  #{$id} dilewati (kurang dari 2 prompt instruksi)");
                if ($jalan) {
                    $db->table('ai_sessions')->where('id', $id)->update([
                        'skor_prompt' => null, 'skor_rincian' => null, 'skor_saran' => null,
                        'skor_metode' => 'lewati', 'skor_model' => null, 'skor_at' => $sekarang,
                    ]);
                }
                $lewati++;
                continue;
            }

            $hasil = AiSkorPrompt::nilai($prompt, ['jenis' => $s['klasifikasi_jenis'] ?? null]);
            usleep(self::JEDA_USEC);

            if ($hasil === null) {
                $gagal++;
                $beruntun++;
                CLI::write("  #{$id} GAGAL dinilai AI — tetap 'Belum dinilai', dicoba lagi nanti", 'yellow');
                if ($jalan && empty($s['skor_metode'])) {
                    // Cap waktu percobaan saja (jeda coba ulang); tanpa skor.
                    $db->table('ai_sessions')->where('id', $id)->update(['skor_at' => $sekarang]);
                }
                if ($beruntun >= self::MAKS_GAGAL_BERUNTUN) {
                    CLI::write('Peringatan: AI gagal/rate-limited beruntun — batch dihentikan, sisanya menyusul.', 'yellow');
                    break;
                }
                continue;
            }

            $beruntun = 0;
            $prov = Klasifikator::$providerTerakhir ?: '?';
            $perProvider[$prov] = ($perProvider[$prov] ?? 0) + 1;
            CLI::write(sprintf('  #%d skor %d (%s) [%s] {%s} %s', $id, $hasil['skor'], AiSkorPrompt::tingkat($hasil['skor'])[1],
                implode('/', $hasil['rincian']), (string) ($hasil['model'] ?? '?'), (string) $hasil['saran']));
            if ($jalan) {
                $db->table('ai_sessions')->where('id', $id)->update([
                    'skor_prompt'  => $hasil['skor'],
                    'skor_rincian' => json_encode($hasil['rincian']),
                    'skor_saran'   => $hasil['saran'],
                    'skor_metode'  => 'llm',
                    'skor_model'   => $hasil['model'] ?? null,
                    'skor_at'      => $sekarang,
                ]);
            }
            $nilai++;
        }

        $rin = '';
        if ($perProvider) {
            arsort($perProvider);
            $rin = ' [' . implode(', ', array_map(fn($k, $v) => "{$k}={$v}", array_keys($perProvider), $perProvider)) . ']';
        }
        CLI::write("Selesai. dinilai AI: {$nilai}{$rin} · dilewati: {$lewati} · gagal (menunggu): {$gagal}"
            . ($coba ? ' (mode coba, tidak disimpan)' : ''), 'green');
    }
}
