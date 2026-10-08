<?php

namespace App\Libraries;

/**
 * Skor mutu prompt per sesi (Pemantauan AI).
 *
 * Penilaian dilakukan HANYA oleh LLM (lewat AiKlasifikasi::panggilProvider,
 * multi-provider failover yang sama dengan klasifikasi). TIDAK ADA fallback
 * aturan yang menghasilkan angka: bila semua provider gagal, {@see nilai}
 * mengembalikan NULL dan sesi tetap "Belum dinilai" lalu dicoba lagi cron.
 *
 * Heuristik di kelas ini ({@see layakDinilai}) hanya memutuskan sesi LAYAK
 * dinilai atau tidak — tidak pernah menghasilkan skor.
 *
 * RUBRIK adalah satu-satunya sumber: dipakai prompt sistem LLM, validasi, dan
 * halaman terbuka /ai-monitor/rubrik.
 */
class AiSkorPrompt
{
    public const DIMENSI = [
        'tujuan' => [
            'nama'  => 'Tujuan jelas',
            'arti'  => 'Apa yang diminta dan hasil akhirnya terbaca jelas.',
            'lemah' => 'perbaiki yang tadi',
            'kuat'  => 'Perbaiki rekap parkir bulanan agar total motor dan mobil tidak dihitung ganda.',
        ],
        'konteks' => [
            'nama'  => 'Konteks',
            'arti'  => 'Menyebut sistem, berkas, data, halaman, atau aturan yang relevan.',
            'lemah' => 'laporannya error',
            'kuat'  => 'Halaman /ai-monitor/laporan error 500 saat bulan Oktober dipilih; lihat AiMonitor::laporan.',
        ],
        'kriteria' => [
            'nama'  => 'Kriteria hasil',
            'arti'  => 'Format, batasan, definisi selesai, atau contoh yang diharapkan.',
            'lemah' => 'buatkan ringkasan',
            'kuat'  => 'Buatkan ringkasan 5 poin, Bahasa Indonesia, tanpa istilah teknis, siap ditempel ke email.',
        ],
        'kekhususan' => [
            'nama'  => 'Kekhususan',
            'arti'  => 'Tidak terlalu singkat atau ambigu; satu permintaan fokus, tidak campur aduk.',
            'lemah' => 'benerin semua, tambah fitur baru, sekalian cek server',
            'kuat'  => 'Tambahkan kolom "catatan" pada form tenant. Hal lain kita bahas terpisah.',
        ],
        'iterasi' => [
            'nama'  => 'Efisiensi iterasi',
            'arti'  => 'Seberapa jarang harus mengulang atau mengoreksi arah (dilihat dari urutan prompt).',
            'lemah' => 'Beberapa kali "bukan itu", "salah", "coba lagi" karena arahan awal kurang.',
            'kuat'  => 'Arahan awal cukup, perubahan berikutnya adalah penyempurnaan, bukan koreksi arah.',
        ],
    ];

    public const MAKS_PER_DIMENSI = 20;

    /** Label tingkat: batas bawah => [kunci, label]. Urut menurun. */
    public const TINGKAT = [
        85 => ['sangat_baik', 'Sangat baik'],
        70 => ['baik', 'Baik'],
        40 => ['cukup', 'Cukup'],
        0  => ['perlu_dilatih', 'Perlu dilatih'],
    ];

    /** Aturan kelayakan & tampilan (juga dipakai halaman rubrik). */
    public const MIN_PROMPT_MANUSIA = 2;
    public const MIN_SESI_TAMPIL    = 5;
    public const JEDA_SESI_AKTIF    = 30; // menit sejak terakhir_at sebelum sesi dianggap selesai

    // Batas cuplikan ke LLM.
    private const MAKS_PROMPT_KIRIM = 6;
    private const MAKS_PER_PROMPT   = 900;
    private const MAKS_TOTAL        = 4000;
    private const MAKS_TOKEN        = 700;

    /** Prompt yang hanya persetujuan/lanjutan, bukan pemberian instruksi. */
    private const KATA_LANJUT = ['lanjut', 'lanjutkan', 'ok', 'oke', 'okay', 'ya', 'iya', 'yes', 'y', 'gas',
        'sip', 'silakan', 'silahkan', 'continue', 'go', 'next', 'terus', 'setuju', 'betul', 'benar', 'lakukan', 'kerjakan'];

    // ── Tingkat ──────────────────────────────────────────────────────────

    /** @return array{0:string,1:string} [kunci, label] untuk skor 0-100. */
    public static function tingkat(int|float $skor): array
    {
        foreach (self::TINGKAT as $batas => $t) {
            if ($skor >= $batas) return $t;
        }
        return self::TINGKAT[0];
    }

    // ── Kelayakan (BUKAN skor) ───────────────────────────────────────────

    /**
     * Bersihkan daftar prompt: buang kosong & pesan sistem (perintah slash,
     * penanda interupsi). Sisanya dianggap prompt manusia.
     *
     * @param string[] $prompt
     * @return string[]
     */
    public static function promptManusia(array $prompt): array
    {
        $out = [];
        foreach ($prompt as $p) {
            $p = trim((string) $p);
            if ($p === '') continue;
            if (str_starts_with($p, '<') || str_starts_with($p, '[Request interrupted') || str_starts_with($p, 'Caveat:')) continue;
            $out[] = $p;
        }
        return $out;
    }

    /** True bila prompt hanya "lanjut/ok/ya" dsb. (bukan instruksi). */
    public static function hanyaLanjutan(string $p): bool
    {
        $p = mb_strtolower(trim($p));
        $p = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', '', $p));
        if ($p === '') return true;
        if (in_array($p, self::KATA_LANJUT, true)) return true;
        $kata = preg_split('/\s+/u', $p);
        return count($kata) <= 3 && count(array_diff($kata, self::KATA_LANJUT)) === 0;
    }

    /**
     * Sesi layak dinilai bila punya >= 2 prompt manusia DAN setidaknya satu
     * di antaranya benar-benar instruksi. Hanya memutuskan layak/tidak.
     *
     * @param string[] $prompt
     */
    public static function layakDinilai(array $prompt): bool
    {
        $m = self::promptManusia($prompt);
        if (count($m) < self::MIN_PROMPT_MANUSIA) return false;
        foreach ($m as $p) {
            if (! self::hanyaLanjutan($p)) return true;
        }
        return false;
    }

    // ── Penilaian LLM ────────────────────────────────────────────────────

    /**
     * Cuplikan prompt untuk LLM: maksimal 6 prompt manusia (pertama + yang
     * paling bermakna bila lebih banyak), dipotong, total <= 4000 karakter.
     * Prompt sudah disamarkan rahasianya saat disimpan (AiLog::samarkan).
     *
     * @param string[] $prompt
     * @return string[]
     */
    public static function cuplikan(array $prompt): array
    {
        $m = self::promptManusia($prompt);
        if (count($m) > self::MAKS_PROMPT_KIRIM) {
            // Urutan dipertahankan: 3 pertama + 3 terakhir (awal arahan & koreksi akhir).
            $m = array_merge(array_slice($m, 0, 3), array_slice($m, -3));
        }
        $out = [];
        $total = 0;
        foreach ($m as $p) {
            $p = mb_substr($p, 0, self::MAKS_PER_PROMPT);
            if ($total + mb_strlen($p) > self::MAKS_TOTAL) {
                $p = mb_substr($p, 0, max(0, self::MAKS_TOTAL - $total));
            }
            if ($p === '') break;
            $out[] = $p;
            $total += mb_strlen($p);
        }
        return $out;
    }

    /** Prompt sistem LLM — dibangun dari rubrik agar tak pernah menyimpang. */
    public static function promptSistem(): string
    {
        $dim = [];
        foreach (self::DIMENSI as $k => $d) {
            $dim[] = "- {$k} ({$d['nama']}): {$d['arti']}";
        }
        return 'Anda menilai MUTU PROMPT (cara pengguna memberi instruksi) pada satu sesi Claude Code di kantor. '
            . 'Nilai cara bertanya/menginstruksikan, BUKAN topik, kecerdasan orangnya, atau hasil kerja AI. '
            . 'Dasar penilaian hanya cuplikan prompt yang diberikan. Bersikap adil dan konstruktif; '
            . 'prompt singkat yang tepat sasaran untuk tugas kecil tetap bisa baik. '
            . "Beri nilai bulat 0-" . self::MAKS_PER_DIMENSI . " untuk tiap dimensi:\n" . implode("\n", $dim) . "\n"
            . 'Untuk "iterasi", lihat urutan prompt: sering mengoreksi arah ("bukan", "salah", "coba lagi") menurunkan nilai. '
            . 'Jawab HANYA satu objek JSON tanpa teks lain: '
            . '{"tujuan":<0-20>,"konteks":<0-20>,"kriteria":<0-20>,"kekhususan":<0-20>,"iterasi":<0-20>,'
            . '"saran":"<1-2 kalimat Bahasa Indonesia, ramah dan konkret, cara memperbaiki prompt berikutnya, maksimal ~300 karakter>"}.';
    }

    /**
     * Nilai satu sesi lewat LLM. NULL bila tak ada provider, semua provider
     * gagal, atau JSON tak valid — pemanggil membiarkan sesi "Belum dinilai"
     * (tidak ada fallback skor).
     *
     * @param string[] $prompt Prompt manusia sesi (berurut waktu).
     * Hasil memuat `model`: model yang BERHASIL menjawab (host-singkat/model).
     *
     * @return array{skor:int, rincian:array<string,int>, saran:?string, model:?string}|null
     */
    public static function nilai(array $prompt, array $konteks = []): ?array
    {
        if (! AiKlasifikasi::terkonfigurasi()) return null;

        $c = self::cuplikan($prompt);
        if ($c === []) return null;

        $baris = [];
        foreach ($c as $i => $p) {
            $baris[] = '[' . ($i + 1) . '] ' . $p;
        }
        $pengguna = 'Jumlah prompt manusia pada sesi: ' . count(self::promptManusia($prompt))
            . (isset($konteks['jenis']) && $konteks['jenis'] ? "\nJenis sesi: " . $konteks['jenis'] : '')
            . "\n\nCuplikan prompt pengguna (berurut):\n" . implode("\n---\n", $baris);

        $isi = AiKlasifikasi::panggilProvider(self::promptSistem(), $pengguna, self::MAKS_TOKEN);
        if ($isi === null) return null;

        $hasil = self::parse($isi);
        if ($hasil === null) return null;
        $hasil['model'] = AiKlasifikasi::$modelTerakhir;
        return $hasil;
    }

    /**
     * Parse + validasi KETAT keluaran LLM. Setiap dimensi wajib ada dan numerik;
     * dibulatkan dan dibatasi ke 0-20 (nilai di luar rentang ditolak bila
     * jauh melenceng: < -1 atau > 21 dianggap JSON tak valid, bukan dipaksa).
     *
     * @return array{skor:int, rincian:array<string,int>, saran:?string}|null
     */
    public static function parse(string $teks): ?array
    {
        $j = self::ekstrakJson($teks);
        if ($j === null) return null;
        // Dimensi boleh bersarang di "rincian"/"skor".
        foreach (['rincian', 'skor', 'dimensi'] as $kunci) {
            if (isset($j[$kunci]) && is_array($j[$kunci]) && ! isset($j['tujuan'])) {
                $j = array_merge($j, $j[$kunci]);
            }
        }

        $rincian = [];
        foreach (array_keys(self::DIMENSI) as $k) {
            $v = $j[$k] ?? null;
            if (is_string($v) && is_numeric(trim($v))) $v = (float) trim($v);
            if (! is_int($v) && ! is_float($v)) return null;
            if ($v < -1 || $v > self::MAKS_PER_DIMENSI + 1) return null;
            $rincian[$k] = max(0, min(self::MAKS_PER_DIMENSI, (int) round($v)));
        }

        $saran = trim((string) ($j['saran'] ?? ''));
        $saran = $saran === '' ? null : mb_substr($saran, 0, 400);

        return ['skor' => array_sum($rincian), 'rincian' => $rincian, 'saran' => $saran];
    }

    private static function ekstrakJson(string $teks): ?array
    {
        $teks = trim($teks);
        if (str_starts_with($teks, '```')) {
            $teks = trim((string) preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $teks));
        }
        $j = json_decode($teks, true);
        if (is_array($j)) return $j;
        $a = strpos($teks, '{');
        $b = strrpos($teks, '}');
        if ($a !== false && $b !== false && $b > $a) {
            $j = json_decode(substr($teks, $a, $b - $a + 1), true);
            if (is_array($j)) return $j;
        }
        return null;
    }
}
