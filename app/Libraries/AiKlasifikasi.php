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
    private const KW_KANTOR_KATA = ['optera', 'opsjobs', 'clara', 'pentacity', 'mic',
        'pamsign', 'esign', 'flowstore', 'erp', 'wbl', 'footfall', 'meteran', 'tenant',
        'loyalty', 'parkir', 'pest'];
    private const KW_KANTOR_SUB = ['mall-intelligence', 'e-sign', 'web-store',
        'erp-integrasi', 'wbl-one', 'htdocs'];
    private const KW_PRIBADI = ['pribadi', 'personal', 'rumah', 'keluarga', 'liburan', 'game pribadi'];

    // Ekstensi berkas kode — kehadirannya menandakan sesi coding.
    private const EXT_KODE = ['.php', '.js', '.ts', '.jsx', '.tsx', '.vue', '.py', '.sql',
        '.css', '.scss', '.html', '.java', '.go', '.rb', '.sh', '.json', '.c', '.cpp', '.cs'];

    /** Nilai enum sah — dipakai untuk validasi keluaran AI. */
    private const JENIS_SAH  = ['coding', 'debugging', 'ideating', 'menulis', 'riset', 'lainnya'];
    private const KANTOR_SAH = ['kantor', 'pribadi', 'tak_jelas'];

    /**
     * Klasifikasi berbasis LLM (provider-agnostik, OpenAI-compatible).
     *
     * Memanggil endpoint {base_url}/chat/completions via HTTP (bukan SDK apa
     * pun) dengan konfigurasi dari env(): aiklas.base_url, aiklas.model,
     * aiklas.api_key. Kembalikan ['jenis','tema','kantor'] bila sukses, atau
     * NULL untuk SEGALA kegagalan (key kosong, HTTP != 200, 429, JSON tak
     * valid, exception) — pemanggil lalu jatuh ke {@see kataKunci}.
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
        $baseUrl = rtrim((string) env('aiklas.base_url'), '/');
        $model   = (string) env('aiklas.model');
        $apiKey  = (string) env('aiklas.api_key');
        if ($apiKey === '' || $baseUrl === '' || $model === '') {
            return null; // belum dikonfigurasi → fallback
        }

        // Cuplikan prompt, dipotong total agar hemat token & biaya.
        $cuplikan = mb_substr(trim(implode("\n---\n", $promptTeks)), 0, 4000);

        $sistem = 'Anda mengklasifikasi sesi penggunaan Claude Code (asisten coding) '
            . 'berdasarkan cuplikan prompt pengguna dan nama proyek. '
            . 'Jawab HANYA satu objek JSON tanpa teks lain, berbentuk: '
            . '{"jenis": "<coding|debugging|ideating|menulis|riset|lainnya>", '
            . '"tema": "<ringkas, maksimal 60 karakter>", '
            . '"kantor": "<kantor|pribadi|tak_jelas>"}. '
            . 'Arti jenis: coding = menulis/ubah kode; debugging = memperbaiki error/bug; '
            . 'ideating = menggagas ide/rencana/rancangan; menulis = menyusun teks non-kode '
            . '(email, artikel, ringkasan, terjemahan); riset = mencari tahu/menjelaskan/membandingkan; '
            . 'lainnya = selain itu. '
            . 'Arti kantor: kantor = berkaitan pekerjaan atau sistem internal perusahaan '
            . '(termasuk bila nama proyek adalah sistem kerja); pribadi = urusan pribadi; '
            . 'tak_jelas = tidak cukup petunjuk. Bila ragu pada kantor, pilih tak_jelas.';

        $pengguna = 'Proyek: ' . ($proyek ?: '(tidak ada)')
            . "\nBranch git: " . ($gitBranch ?: '(tidak ada)')
            . "\nJumlah pemanggilan alat: " . $jmlAlat
            . "\n\nCuplikan prompt pengguna:\n" . ($cuplikan !== '' ? $cuplikan : '(kosong)');

        try {
            $client = \Config\Services::curlrequest([
                'timeout'     => 25,
                'http_errors' => false, // jangan lempar pada status != 2xx
            ]);
            $resp = $client->post($baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    // Disarankan OpenRouter (identifikasi aplikasi; opsional).
                    'HTTP-Referer'  => 'https://mic.wbl-bsb.com',
                    'X-Title'       => 'MIC AI Monitor',
                ],
                'json' => [
                    'model'           => $model,
                    'temperature'     => 0,
                    'max_tokens'      => 200,
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
            $isi  = $data['choices'][0]['message']['content'] ?? null;
            if (! is_string($isi) || $isi === '') {
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
            return null; // segala error → fallback
        }
    }

    /** Ambil objek JSON pertama dari teks (toleran bila ada teks pembungkus). */
    private static function ekstrakJson(string $teks): ?array
    {
        $teks = trim($teks);
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
            $branch = trim((string) $gitBranch);
            if ($branch !== '' && ! in_array(mb_strtolower($branch), ['main', 'master'], true)) {
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
