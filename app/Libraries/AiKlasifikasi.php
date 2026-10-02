<?php

namespace App\Libraries;

/**
 * Klasifikasi sesi Pemantauan AI.
 *
 * Tahap pertama GRATIS, tanpa API: hanya kata kunci (method {@see kataKunci}).
 * Methodnya sengaja MURNI (tanpa DB, tanpa state) agar mudah diuji dan agar
 * versi AI nanti cukup ditambah sebagai method lain (mis. public static
 * function ai(...)) dengan kontrak keluaran yang sama — pemanggil tinggal
 * memilih method dan menyimpan klasifikasi_metode yang sesuai.
 *
 * Keluaran selalu: ['jenis' => ..., 'tema' => ..., 'kantor' => ...].
 *   jenis  : coding|debugging|ideating|menulis|riset|lainnya
 *   tema   : label manusiawi (mis. nama proyek dirapikan) atau 'Lainnya'
 *   kantor : kantor|pribadi|tak_jelas
 */
class AiKlasifikasi
{
    // ── Kata kunci per jenis (Indonesia + Inggris, case-insensitive) ─────
    private const KW_DEBUGGING = ['error', 'bug', 'fix', 'gagal', 'exception', 'stack',
        'gak jalan', 'tidak jalan', 'crash', '500', 'fatal'];
    private const KW_CODING = ['code', 'coding', 'fungsi', 'function', 'class', 'query',
        'sql', 'database', 'deploy', 'git', 'api', 'script', 'kompil', 'migrasi', 'refactor'];
    private const KW_IDEATING = ['ide', 'idea', 'brainstorm', 'rencana', 'konsep', 'rancang',
        'design', 'saran', 'gimana kalau'];
    private const KW_MENULIS = ['tulis', 'buatkan teks', 'email', 'surat', 'artikel',
        'ringkas', 'terjemah', 'caption', 'draft'];
    private const KW_RISET = ['cari', 'apa itu', 'jelaskan', 'bandingkan', 'apakah',
        'kenapa', 'riset', 'research'];

    // ── Kantor (sistem kerja WBL + sinonim) ──────────────────────────────
    // Token pendek dicek dengan batas kata agar tak salah tangkap (mis. "mic"
    // di dalam kata lain). Token berimbuhan tanda hubung dicek sebagai substring.
    private const KW_KANTOR_KATA = ['optera', 'opsjobs', 'clara', 'pentacity', 'ewalk', 'mic',
        'pamsign', 'esign', 'flowstore', 'erp', 'wbl', 'footfall', 'meteran', 'tenant',
        'loyalty', 'parkir', 'pest', 'housekeeping'];
    private const KW_KANTOR_SUB = ['mall-intelligence', 'e-sign', 'web-store',
        'erp-integrasi', 'wbl-one', 'htdocs'];
    private const KW_PRIBADI = ['pribadi', 'personal', 'rumah', 'keluarga', 'liburan', 'game pribadi'];
    /** Sinyal pribadi KUAT (pendidikan/urusan jelas pribadi) — diperiksa
     *  SEBELUM kata kantor agar tak kalah oleh path kerja (mis. 'htdocs'). */
    private const KW_PRIBADI_KUAT = ['kuliah', 'tugas kuliah', 'skripsi', 'tesis', 'kampus',
        'dosen', 'mahasiswa', 'ujian', 'makalah', 'sekolah', 'pekerjaan rumah', 'jurnal kampus', 'lcoi'];

    // Ekstensi berkas kode — kehadirannya menandakan sesi coding.
    private const EXT_KODE = ['.php', '.js', '.ts', '.jsx', '.tsx', '.vue', '.py', '.sql',
        '.css', '.scss', '.html', '.java', '.go', '.rb', '.sh', '.json', '.c', '.cpp', '.cs'];

    /** Nilai enum sah — dipakai untuk validasi keluaran AI. */
    private const JENIS_SAH  = ['coding', 'debugging', 'ideating', 'menulis', 'riset', 'lainnya'];
    private const KANTOR_SAH = ['kantor', 'pribadi', 'tak_jelas'];

    /** Batas waktu per panggilan HTTP ke satu provider (detik).
     *  Cukup longgar untuk model "reasoning" yang lambat, tapi tetap membuat
     *  provider yang hang (mis. NVIDIA chat yang pernah HTTP 000) gugur tepat
     *  waktu lalu failover ke provider berikutnya. */
    private const AI_TIMEOUT = 40;

    /** max_tokens per panggilan. Harus cukup besar: sebagian model adalah
     *  "reasoning" yang membakar token untuk berpikir di message.reasoning_content
     *  SEBELUM menuliskan JSON di message.content; bila terlalu kecil,
     *  finish_reason='length' dan content tetap null. */
    private const AI_MAX_TOKENS = 1024;

    /** Label provider yang BERHASIL dipakai pada panggilan ai() terakhir
     *  (mis. 'p1'), atau null bila semua gagal. Dibaca command untuk log. */
    public static ?string $providerTerakhir = null;

    /**
     * Klasifikasi berbasis LLM (provider-agnostik, OpenAI-compatible) dengan
     * MULTI-PROVIDER AUTO-FAILOVER.
     *
     * Membaca daftar provider berurut dari env (lihat {@see daftarProvider}),
     * lalu mencoba satu per satu: provider PERTAMA yang mengembalikan
     * {jenis,tema,kantor} valid dipakai. Bila sebuah provider gagal (HTTP != 200,
     * timeout/hang, 429, JSON tak valid, content kosong, exception) → lanjut ke
     * provider berikutnya. Semua gagal → NULL (pemanggil jatuh ke {@see kataKunci}).
     *
     * Label provider yang berhasil disimpan di {@see $providerTerakhir}.
     *
     * Catatan privasi: isi prompt yang dikirim SUDAH disamarkan rahasianya di
     * server oleh AiLog::samarkan() sebelum disimpan, jadi aman dikirim ke
     * penyedia LLM.
     *
     * @param string[] $promptTeks
     * @return array{jenis:string, tema:string, kantor:string}|null
     */
    public static function ai(array $promptTeks, ?string $proyek, ?string $gitBranch, int $jmlAlat): ?array
    {
        self::$providerTerakhir = null;

        $providers = self::daftarProvider();
        if ($providers === []) {
            return null; // belum dikonfigurasi → fallback
        }

        // Cuplikan prompt, dipotong total agar hemat token & biaya.
        $cuplikan = mb_substr(trim(implode("\n---\n", $promptTeks)), 0, 4000);

        $sistem = 'Anda mengklasifikasi sesi penggunaan Claude Code (asisten coding) '
            . 'berdasarkan cuplikan prompt pengguna dan nama proyek. Nilai dari TUJUAN/OUTPUT yang diminta, '
            . 'BUKAN sekadar ada-tidaknya kode. '
            . 'Jawab HANYA satu objek JSON tanpa teks lain, berbentuk: '
            . '{"jenis": "<coding|debugging|ideating|menulis|riset|lainnya>", '
            . '"tema": "<ringkas, maksimal 60 karakter>", '
            . '"kantor": "<kantor|pribadi|tak_jelas>"}. '
            . 'Arti jenis: coding = mengembangkan/mengubah perangkat lunak/fitur/sistem nyata; '
            . 'debugging = memperbaiki error/bug; ideating = menggagas ide/rencana/rancangan; '
            . 'menulis = menghasilkan dokumen/laporan/teks, TERMASUK bila kode hanya dipakai untuk MENGHASILKAN '
            . 'dokumen/PDF (mis. tugas membuat laporan seperti LCOI → menulis, bukan coding); '
            . 'riset = mencari tahu/menjelaskan/membandingkan; lainnya = selain itu. '
            . 'Konteks perusahaan: PT Wulandari Bangun Laksana (WBL) mengelola mal eWalk dan Pentacity (Balikpapan). '
            . 'kantor = berkaitan pekerjaan WBL: operasional mal eWalk/Pentacity (pest control, traffic/footfall, '
            . 'tenant, loyalty, parkir, event, housekeeping, meteran) atau sistem internal '
            . '(OpsJobs/Optera, Clara, MIC, PAM e-Sign, FlowStore, ERP). '
            . 'pribadi = urusan pribadi ATAU tugas kuliah/sekolah (mis. LCOI, skripsi, makalah, PR, ujian). '
            . 'tak_jelas = tidak cukup petunjuk. Bila ragu, pilih tak_jelas.';

        $pengguna = 'Proyek: ' . ($proyek ?: '(tidak ada)')
            . "\nBranch git: " . ($gitBranch ?: '(tidak ada)')
            . "\nJumlah pemanggilan alat: " . $jmlAlat
            . "\n\nCuplikan prompt pengguna:\n" . ($cuplikan !== '' ? $cuplikan : '(kosong)');

        foreach ($providers as $p) {
            $hasil = self::panggilProvider($p, $sistem, $pengguna, $proyek);
            if ($hasil !== null) {
                self::$providerTerakhir = $p['label'];
                return $hasil;
            }
        }

        return null; // semua provider gagal → fallback kata kunci
    }

    /**
     * Susun daftar provider BERURUT dari env.
     *
     * Didukung dua format (numbered diutamakan bila ada):
     *   aiklas.p1_base_url / aiklas.p1_model / aiklas.p1_key, p2_*, p3_*, …
     *     (berhenti pada nomor pertama yang base_url-nya kosong)
     *   aiklas.base_url / aiklas.model / aiklas.api_key  (format lama; dipakai
     *     HANYA bila tak ada satupun entri numbered — demi kompatibilitas)
     *
     * @return array<int, array{base:string, model:string, key:string, label:string}>
     */
    private static function daftarProvider(): array
    {
        $out = [];
        for ($i = 1; $i <= 20; $i++) {
            $base = rtrim((string) env("aiklas.p{$i}_base_url"), '/');
            if ($base === '') {
                break; // nomor berurut; nomor kosong = akhir daftar
            }
            $model = (string) env("aiklas.p{$i}_model");
            $key   = (string) env("aiklas.p{$i}_key");
            if ($model === '' || $key === '') {
                continue; // entri tak lengkap → lewati, jangan putus daftar
            }
            $out[] = ['base' => $base, 'model' => $model, 'key' => $key, 'label' => "p{$i}"];
        }

        if ($out === []) {
            // Format lama (satu provider).
            $base  = rtrim((string) env('aiklas.base_url'), '/');
            $model = (string) env('aiklas.model');
            $key   = (string) env('aiklas.api_key');
            if ($base !== '' && $model !== '' && $key !== '') {
                $out[] = ['base' => $base, 'model' => $model, 'key' => $key, 'label' => 'p1'];
            }
        }

        return $out;
    }

    /**
     * Panggil SATU provider. Kembalikan {jenis,tema,kantor} valid atau NULL
     * (segala kegagalan) agar pemanggil failover ke provider berikutnya.
     *
     * @param array{base:string, model:string, key:string, label:string} $p
     * @return array{jenis:string, tema:string, kantor:string}|null
     */
    private static function panggilProvider(array $p, string $sistem, string $pengguna, ?string $proyek): ?array
    {
        try {
            $client = \Config\Services::curlrequest([
                'timeout'         => self::AI_TIMEOUT,
                'connect_timeout' => 10,
                'http_errors'     => false, // jangan lempar pada status != 2xx
            ]);
            $resp = $client->post($p['base'] . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $p['key'],
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    // Disarankan OpenRouter (identifikasi aplikasi; opsional).
                    'HTTP-Referer'  => 'https://mic.wbl-bsb.com',
                    'X-Title'       => 'MIC AI Monitor',
                ],
                'json' => [
                    'model'           => $p['model'],
                    'temperature'     => 0,
                    'max_tokens'      => self::AI_MAX_TOKENS,
                    // Eksplisit non-stream: beberapa provider stream default
                    // sehingga respons non-stream bisa menggantung.
                    'stream'          => false,
                    // Diminta bila didukung; parsing di bawah tetap defensif.
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $sistem],
                        ['role' => 'user',   'content' => $pengguna],
                    ],
                ],
            ]);

            if ($resp->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $resp->getBody(), true);
            $msg  = $data['choices'][0]['message'] ?? null;
            if (! is_array($msg)) {
                return null;
            }

            // Parsing TAHAN BANTING. Model "reasoning" kadang menaruh content
            // null dan mengisi reasoning_content/reasoning; ambil yang pertama
            // tak kosong.
            $isi = '';
            foreach (['content', 'reasoning_content', 'reasoning'] as $kolom) {
                $kandidat = $msg[$kolom] ?? null;
                if (is_string($kandidat) && trim($kandidat) !== '') {
                    $isi = $kandidat;
                    break;
                }
            }
            if ($isi === '') {
                return null;
            }

            $parsed = self::ekstrakJson($isi);
            if ($parsed === null) {
                return null;
            }

            // Validasi enum + rapikan tema.
            $jenis = strtolower(trim((string) ($parsed['jenis'] ?? '')));
            if (! in_array($jenis, self::JENIS_SAH, true)) $jenis = 'lainnya';

            $kantor = strtolower(trim((string) ($parsed['kantor'] ?? '')));
            if (! in_array($kantor, self::KANTOR_SAH, true)) $kantor = 'tak_jelas';

            $tema = trim((string) ($parsed['tema'] ?? ''));
            $tema = mb_substr($tema, 0, 100);
            if ($tema === '') {
                $tema = ($proyek !== null && trim($proyek) !== '')
                    ? self::rapikanLabel(trim($proyek)) : 'Lainnya';
            }

            return ['jenis' => $jenis, 'tema' => $tema, 'kantor' => $kantor];
        } catch (\Throwable $e) {
            return null; // timeout/hang/koneksi/segala error → provider berikutnya
        }
    }

    /** True bila setidaknya satu provider AI terkonfigurasi di env. */
    public static function terkonfigurasi(): bool
    {
        return self::daftarProvider() !== [];
    }

    /** Ambil objek JSON pertama dari teks (toleran bila ada teks pembungkus). */
    private static function ekstrakJson(string $teks): ?array
    {
        $teks = trim($teks);

        // Lepas pembungkus pagar kode ```json … ``` bila ada.
        if (str_starts_with($teks, '```')) {
            $teks = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $teks);
            $teks = trim((string) $teks);
        }

        $j = json_decode($teks, true);
        if (is_array($j)) return $j;

        // Model kadang membungkus JSON dengan teks/```json. Ambil { ... } pertama.
        $awal = strpos($teks, '{');
        $akhir = strrpos($teks, '}');
        if ($awal !== false && $akhir !== false && $akhir > $awal) {
            $kandidat = substr($teks, $awal, $akhir - $awal + 1);
            $j = json_decode($kandidat, true);
            if (is_array($j)) return $j;
        }
        return null;
    }

    /**
     * Klasifikasi berbasis kata kunci.
     *
     * @param string[]    $promptTeks Daftar isi prompt manusia pada sesi.
     * @param string|null $proyek     Nama proyek (dari cwd) bila ada.
     * @param string|null $gitBranch  Branch git bila ada.
     * @param int         $jmlAlat    Jumlah pemanggilan alat pada sesi.
     * @return array{jenis:string, tema:string, kantor:string}
     */
    public static function kataKunci(array $promptTeks, ?string $proyek, ?string $gitBranch, int $jmlAlat): array
    {
        $hay = mb_strtolower(trim(implode("\n", $promptTeks)));
        $proyekLc = mb_strtolower((string) $proyek);
        $gabung   = $hay . "\n" . $proyekLc; // untuk cek kantor & ekstensi kode

        return [
            'jenis'  => self::tentukanJenis($hay, $proyekLc, $gabung, $jmlAlat),
            'tema'   => self::tentukanTema($hay, $proyek, $gitBranch),
            'kantor' => self::tentukanKantor($gabung),
        ];
    }

    // ── Jenis (urutan prioritas: debugging → coding → ideating → …) ──────
    private static function tentukanJenis(string $hay, string $proyekLc, string $gabung, int $jmlAlat): string
    {
        if (self::adaSalahSatu($hay, self::KW_DEBUGGING)) return 'debugging';

        if ($jmlAlat > 0
            || self::adaSalahSatu($hay, self::KW_CODING)
            || self::adaEkstensiKode($gabung)) {
            return 'coding';
        }

        if (self::adaSalahSatu($hay, self::KW_IDEATING)) return 'ideating';
        if (self::adaSalahSatu($hay, self::KW_MENULIS))  return 'menulis';
        if (self::adaSalahSatu($hay, self::KW_RISET))    return 'riset';

        return 'lainnya';
    }

    // ── Tema ──────────────────────────────────────────────────────────────
    private static function tentukanTema(string $hay, ?string $proyek, ?string $gitBranch): string
    {
        $proyek = trim((string) $proyek);
        if ($proyek !== '') {
            $label = self::rapikanLabel($proyek);
            // Tambah nama branch HANYA bila bermakna. 'HEAD' (detached),
            // 'main'/'master', dan nilai kosong adalah noise — jangan
            // dilekatkan supaya tema tak jadi "Proyek (HEAD)".
            $branch = trim((string) $gitBranch);
            if ($branch !== '' && ! in_array(mb_strtolower($branch), ['main', 'master', 'head'], true)) {
                $label .= ' (' . $branch . ')';
            }
            return mb_substr($label, 0, 100);
        }

        // Tanpa proyek: tebak topik ringkas dari kata benda umum di prompt.
        $topik = [
            'email' => 'Email', 'surat' => 'Surat', 'laporan' => 'Laporan',
            'artikel' => 'Artikel', 'caption' => 'Caption', 'dashboard' => 'Dashboard',
            'database' => 'Database', 'desain' => 'Desain', 'design' => 'Desain',
            'presentasi' => 'Presentasi', 'anggaran' => 'Anggaran', 'budget' => 'Anggaran',
        ];
        foreach ($topik as $kata => $labelTopik) {
            if (self::adaKata($hay, $kata)) return $labelTopik;
        }
        return 'Lainnya';
    }

    // ── Kantor / pribadi / tak jelas ─────────────────────────────────────
    private static function tentukanKantor(string $gabung): string
    {
        // Sinyal pribadi KUAT (pendidikan/urusan jelas pribadi) menang lebih
        // dulu — supaya "tugas kuliah" dkk tak keburu ditandai 'kantor' hanya
        // karena jalan di bawah path kerja (mis. 'htdocs').
        if (self::adaSalahSatu($gabung, self::KW_PRIBADI_KUAT)) return 'pribadi';

        foreach (self::KW_KANTOR_SUB as $sub) {
            if (mb_strpos($gabung, $sub) !== false) return 'kantor';
        }
        foreach (self::KW_KANTOR_KATA as $kata) {
            if (self::adaKata($gabung, $kata)) return 'kantor';
        }
        if (self::adaSalahSatu($gabung, self::KW_PRIBADI)) return 'pribadi';

        return 'tak_jelas'; // konservatif: ragu → tak jelas
    }

    // ── Pembantu ────────────────────────────────────────────────────────

    /** True bila salah satu needle (substring, sudah lowercase) ada di haystack. */
    private static function adaSalahSatu(string $hay, array $needles): bool
    {
        foreach ($needles as $n) {
            if ($n !== '' && mb_strpos($hay, $n) !== false) return true;
        }
        return false;
    }

    /** True bila $kata muncul sebagai KATA utuh (batas kata) di haystack. */
    private static function adaKata(string $hay, string $kata): bool
    {
        return (bool) preg_match('/\b' . preg_quote($kata, '/') . '\b/u', $hay);
    }

    private static function adaEkstensiKode(string $hay): bool
    {
        foreach (self::EXT_KODE as $ext) {
            if (mb_strpos($hay, $ext) !== false) return true;
        }
        return false;
    }

    /** "mall-intelligence-center" → "Mall Intelligence Center". */
    private static function rapikanLabel(string $proyek): string
    {
        $s = str_replace(['-', '_'], ' ', $proyek);
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return $s === '' ? $proyek : mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
    }
}
