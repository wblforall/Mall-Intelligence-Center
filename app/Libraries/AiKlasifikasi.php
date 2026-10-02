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
 * Keluaran selalu: ['jenis' => ..., 'tema' => ..., 'kantor' => ..., 'ringkasan' => ...].
 *   jenis     : coding|debugging|ideating|menulis|riset|lainnya
 *   tema      : label manusiawi (mis. nama proyek dirapikan) atau 'Lainnya'
 *   kantor    : kantor|pribadi|tak_jelas
 *   ringkasan : 1–2 kalimat (string) APA yang dikerjakan di sesi, atau null
 *               (kata kunci tak membuat ringkasan → selalu null di kataKunci()).
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
     * @return array{jenis:string, tema:string, kantor:string, ringkasan:?string}|null
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
            . '"kantor": "<kantor|pribadi|tak_jelas>", '
            . '"ringkasan": "<1-2 kalimat Bahasa Indonesia, maksimal ~280 karakter>"}. '
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
            . 'tak_jelas = tidak cukup petunjuk. Bila ragu, pilih tak_jelas. '
            . 'Arti ringkasan: 1-2 kalimat padat yang menjelaskan APA yang sebenarnya '
            . 'DIKERJAKAN dan DIHASILKAN pada sesi (aktivitas & hasil nyata), '
            . 'lebih kaya dari tema. JANGAN menyalin judul atau prompt pertama; '
            . 'nilai dari keseluruhan prompt. Tulis ringkas, maksimal ~280 karakter. '
            . 'Bila benar-benar tak ada petunjuk, boleh string kosong.';

        $pengguna = 'Proyek: ' . ($proyek ?: '(tidak ada)')
            . "\nBranch git: " . ($gitBranch ?: '(tidak ada)')
            . "\nJumlah pemanggilan alat: " . $jmlAlat
            . "\n\nCuplikan prompt pengguna:\n" . ($cuplikan !== '' ? $cuplikan : '(kosong)');

        // Panggil provider (multi-provider failover di panggilProvider), lalu
        // parse + validasi JSON di sini. $providerTerakhir di-set di dalam loop.
        $isi = self::panggilProvider($sistem, $pengguna, self::AI_MAX_TOKENS);
        if ($isi === null) {
            return null; // semua provider gagal → fallback kata kunci
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

        // Ringkasan: trim ≤300 char; kosong → null.
        $ringkasan = trim((string) ($parsed['ringkasan'] ?? ''));
        $ringkasan = $ringkasan === '' ? null : mb_substr($ringkasan, 0, 300);

        return ['jenis' => $jenis, 'tema' => $tema, 'kantor' => $kantor, 'ringkasan' => $ringkasan];
    }

    /**
     * RINGKASAN AGREGAT per orang/komputer untuk sebuah periode.
     *
     * Mensintesis ringkasan tiap sesi menjadi SATU paragraf (3-5 kalimat)
     * Bahasa Indonesia: apa saja yang dikerjakan & dihasilkan, pola/fokus utama,
     * dan porsi kerja (kantor) vs pribadi. Memakai {@see panggilProvider} yang
     * sama (multi-provider failover). TIDAK melempar error — segala kegagalan
     * (provider mati, kosong, exception) → NULL agar pemanggil pakai fallback.
     *
     * @param array<int, array{judul?:string, ringkasan?:string, jenis?:string,
     *              tema?:string, kantor?:string}> $items Daftar per-sesi (maks ~40,
     *              total dipotong ≤4000 char).
     * @param array{nama?:string, label_periode?:string, total_sesi?:int,
     *              total_prompt?:int, jenis_dominan?:string, pct_kantor?:int|null} $meta
     * @return string|null Paragraf ringkasan (≤800 char) atau null.
     */
    public static function ringkasanPeriode(array $items, array $meta): ?string
    {
        try {
            if ($items === [] || ! self::terkonfigurasi()) {
                return null;
            }

            // Maks ~40 sesi, lalu potong total daftar ≤4000 char.
            $items = array_slice($items, 0, 40);
            $baris = [];
            $panjang = 0;
            foreach ($items as $it) {
                $judul  = trim((string) ($it['judul'] ?? ''));
                $ring   = trim((string) ($it['ringkasan'] ?? ''));
                $jenis  = trim((string) ($it['jenis'] ?? ''));
                $tema   = trim((string) ($it['tema'] ?? ''));
                $kantor = trim((string) ($it['kantor'] ?? ''));

                $teks = '- ' . ($judul !== '' ? $judul : '(tanpa judul)');
                $tag  = array_filter([$jenis, $tema, $kantor], fn($v) => $v !== '');
                if ($tag !== []) $teks .= ' [' . implode(', ', $tag) . ']';
                if ($ring !== '') $teks .= ': ' . $ring;

                if ($panjang + mb_strlen($teks) > 4000) break;
                $baris[]  = $teks;
                $panjang += mb_strlen($teks) + 1;
            }
            if ($baris === []) {
                return null;
            }

            $sistem = 'Rangkum aktivitas penggunaan Claude Code (asisten coding) seseorang '
                . 'selama sebuah periode, berdasarkan ringkasan tiap sesinya. '
                . 'Tulis 3-5 kalimat Bahasa Indonesia dalam SATU paragraf. '
                . 'Fokus: APA SAJA yang dikerjakan & dihasilkan, pola/fokus utama, '
                . 'dan porsi kerja (kantor) vs pribadi. Netral & faktual, tanpa menghakimi. '
                . 'Jangan mengarang di luar data. Jawab HANYA paragraf ringkasannya, '
                . 'tanpa judul, tanpa poin, tanpa kata pembuka.';

            $m = [];
            if (! empty($meta['nama']))          $m[] = 'Nama: ' . $meta['nama'];
            if (! empty($meta['label_periode'])) $m[] = 'Periode: ' . $meta['label_periode'];
            if (isset($meta['total_sesi']))      $m[] = 'Total sesi: ' . (int) $meta['total_sesi'];
            if (isset($meta['total_prompt']))    $m[] = 'Total prompt: ' . (int) $meta['total_prompt'];
            if (! empty($meta['jenis_dominan'])) $m[] = 'Jenis dominan: ' . $meta['jenis_dominan'];
            if (isset($meta['pct_kantor']) && $meta['pct_kantor'] !== null) {
                $m[] = 'Porsi kantor: ' . (int) $meta['pct_kantor'] . '%';
            }

            $pengguna = "Konteks:\n" . implode("\n", $m)
                . "\n\nRingkasan tiap sesi:\n" . implode("\n", $baris);

            // jsonMode=false → minta prosa bebas, bukan objek JSON.
            $teks = self::panggilProvider($sistem, $pengguna, 600, false);
            if ($teks === null) {
                return null;
            }

            $teks = trim($teks);
            // Lepas pembungkus pagar kode bila model menambahkannya.
            if (str_starts_with($teks, '```')) {
                $teks = trim((string) preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $teks));
            }
            return $teks === '' ? null : mb_substr($teks, 0, 800);
        } catch (\Throwable $e) {
            return null; // tak pernah melempar
        }
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
     * Pemanggil LLM reusable dengan MULTI-PROVIDER AUTO-FAILOVER.
     *
     * Mencoba provider p1..pN berurut (lihat {@see daftarProvider}); provider
     * PERTAMA yang mengembalikan teks content tak kosong dipakai, labelnya
     * disimpan di {@see $providerTerakhir}. Mengembalikan TEKS MENTAH content
     * (belum di-parse) agar bisa dipakai banyak keperluan: ai() memakainya untuk
     * JSON klasifikasi, ringkasanPeriode() untuk prosa. Semua gagal → NULL.
     *
     * @param int  $maxTokens max_tokens per panggilan.
     * @param bool $jsonMode  true → minta response_format json_object (klasifikasi);
     *                        false → prosa bebas (ringkasan periode).
     */
    private static function panggilProvider(string $sistem, string $pengguna, int $maxTokens = 400, bool $jsonMode = true): ?string
    {
        self::$providerTerakhir = null;
        foreach (self::daftarProvider() as $p) {
            $isi = self::httpSatuProvider($p, $sistem, $pengguna, $maxTokens, $jsonMode);
            if ($isi !== null && trim($isi) !== '') {
                self::$providerTerakhir = $p['label'];
                return $isi;
            }
        }
        return null; // semua provider gagal
    }

    /**
     * Panggil SATU provider (HTTP) dan kembalikan TEKS MENTAH content pertama
     * yang tak kosong, atau NULL pada segala kegagalan (HTTP != 200, timeout,
     * hang, content kosong, exception) agar pemanggil failover ke provider
     * berikutnya.
     *
     * @param array{base:string, model:string, key:string, label:string} $p
     */
    private static function httpSatuProvider(array $p, string $sistem, string $pengguna, int $maxTokens, bool $jsonMode): ?string
    {
        try {
            $client = \Config\Services::curlrequest([
                'timeout'         => self::AI_TIMEOUT,
                'connect_timeout' => 10,
                'http_errors'     => false, // jangan lempar pada status != 2xx
            ]);
            $json = [
                'model'       => $p['model'],
                'temperature' => 0,
                'max_tokens'  => $maxTokens,
                // Eksplisit non-stream: beberapa provider stream default
                // sehingga respons non-stream bisa menggantung.
                'stream'      => false,
                'messages'    => [
                    ['role' => 'system', 'content' => $sistem],
                    ['role' => 'user',   'content' => $pengguna],
                ],
            ];
            // Diminta HANYA untuk mode JSON; parsing pemanggil tetap defensif.
            if ($jsonMode) {
                $json['response_format'] = ['type' => 'json_object'];
            }

            $resp = $client->post($p['base'] . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $p['key'],
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    // Disarankan OpenRouter (identifikasi aplikasi; opsional).
                    'HTTP-Referer'  => 'https://mic.wbl-bsb.com',
                    'X-Title'       => 'MIC AI Monitor',
                ],
                'json' => $json,
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
            foreach (['content', 'reasoning_content', 'reasoning'] as $kolom) {
                $kandidat = $msg[$kolom] ?? null;
                if (is_string($kandidat) && trim($kandidat) !== '') {
                    return $kandidat;
                }
            }
            return null;
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
     * @return array{jenis:string, tema:string, kantor:string, ringkasan:null}
     */
    public static function kataKunci(array $promptTeks, ?string $proyek, ?string $gitBranch, int $jmlAlat): array
    {
        $hay = mb_strtolower(trim(implode("\n", $promptTeks)));
        $proyekLc = mb_strtolower((string) $proyek);
        $gabung   = $hay . "\n" . $proyekLc; // untuk cek kantor & ekstensi kode

        return [
            'jenis'     => self::tentukanJenis($hay, $proyekLc, $gabung, $jmlAlat),
            'tema'      => self::tentukanTema($hay, $proyek, $gitBranch),
            'kantor'    => self::tentukanKantor($gabung),
            'ringkasan' => null, // kata kunci tak membuat ringkasan
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
