<?php

namespace App\Libraries;

/**
 * Pemantauan AI — mengurai baris transkrip Claude Code dan menyimpannya.
 *
 * Laptop mengirim baris JSONL apa adanya (lihat public/ai-monitor/kirim.ps1);
 * SEMUA tafsir dilakukan di sini, bukan di laptop. Alasannya: format
 * transkrip Claude Code berubah antar versi, dan memperbaiki satu berkas PHP
 * di server jauh lebih murah daripada memasang ulang skrip di tiap laptop.
 *
 * PEMANTAUAN INI TERBUKA. Skrip laptop tidak disembunyikan dan boleh
 * dimatikan pemakainya; tim diberi tahu lewat edaran + layar pemberitahuan
 * saat pemasangan. Lihat public/ai-monitor/README.txt.
 *
 * Yang disimpan hanya tiga jenis entri:
 *  - prompt  : ketikan manusia (bukan notifikasi tugas, bukan teks sistem)
 *  - balasan : teks jawaban Claude
 *  - alat    : alat yang dipanggil Claude (berkas yang diubah, perintah, dsb.)
 * Isi berkas yang DIBACA Claude (tool_result) tidak pernah dikirim laptop.
 */
class AiLog
{
    /** Batas isi satu entri. Jawaban panjang dipotong, bukan dibuang. */
    public const MAKS_ISI = 20000;

    /** Batas ukuran satu kiriman (byte) — skrip laptop memecah di 4 MB. */
    public const MAKS_KIRIMAN = 8 * 1024 * 1024;

    // ── Penyamaran rahasia ────────────────────────────────────────────────
    // Dijalankan ke SETIAP teks sebelum disimpan. Perintah AI sering memuat
    // kata sandi, token, atau kunci API; begitu tersimpan mentah, log ini
    // sendiri jadi kebocoran. Pola di bawah mengganti nilainya dengan
    // penanda, menyisakan cukup konteks untuk paham tanpa menyimpan rahasia.
    private const POLA_RAHASIA = [
        // key=value / key: value untuk kata kunci sensitif. Tanpa \b di depan
        // supaya bentuk menempel seperti "-ppassword=xxx" ikut tertangkap;
        // sengaja condong over-redact — menyamarkan terlalu banyak tak
        // berbahaya, membiarkan satu kata sandi lolos berbahaya.
        '/(pass(?:word)?|sandi|secret|token|api[_-]?key|apikey|pwd|authorization|bearer|private[_-]?key)\s*[:=]\s*\S+/i'
            => '$1=[disamarkan]',
        // Kata sandi MySQL/Postgres yang menempel ke flag: "-pSECRET" (tanpa
        // spasi, tanpa "="). "-p" diikuti spasi (prompt interaktif) tak kena.
        '/(?<=\s)-p(?=\S)[^\s]+/'
            => '-p[disamarkan]',
        // Token mirip JWT / sk-... / ghp_... / AKIA...
        '/\b(sk-[A-Za-z0-9]{12,}|ghp_[A-Za-z0-9]{20,}|xox[baprs]-[A-Za-z0-9-]{10,}|AKIA[0-9A-Z]{12,}|eyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{5,})/'
            => '[token-disamarkan]',
        // URL dengan kredensial inline: scheme://user:pass@host
        '#([a-z][a-z0-9+.-]*://[^\s:@/]+):[^\s@/]+@#i'
            => '$1:[disamarkan]@',
        // Blok kunci privat PEM
        '/-----BEGIN [^-]+PRIVATE KEY-----.*?-----END [^-]+PRIVATE KEY-----/s'
            => '[kunci-privat-disamarkan]',
    ];

    /** Samarkan rahasia pada satu teks. Aman untuk null. */
    public static function samarkan(?string $teks): ?string
    {
        if ($teks === null || $teks === '') return $teks;
        foreach (self::POLA_RAHASIA as $pola => $ganti) {
            $teks = preg_replace($pola, $ganti, $teks) ?? $teks;
        }
        return $teks;
    }

    // ── Penguraian ────────────────────────────────────────────────────────

    /**
     * Urai satu baris transkrip (sudah di-decode jadi array).
     * Murni — tak menyentuh DB. Mengembalikan null untuk baris yang tak
     * perlu disimpan (notifikasi tugas, ringkasan, meta, teks sistem).
     *
     * @return array{jenis:string, uuid:string, waktu:?string, ...}|null
     */
    public static function uraiEntri(array $b): ?array
    {
        $tipe = $b['type'] ?? null;
        $uuid = $b['uuid'] ?? null;
        if (! $uuid || ! in_array($tipe, ['user', 'assistant'], true)) {
            return null;
        }

        // Baris anak (subagent) ditandai isSidechain — tetap pekerjaan AI,
        // tapi bukan ketikan manusia; disimpan sebagai balasan/alat saja.
        $msg     = $b['message'] ?? [];
        $content = $msg['content'] ?? null;

        if ($tipe === 'user') {
            // Hanya ketikan manusia asli. Penanda yang menyingkirkan sisanya:
            //  - origin.kind != 'human'  → notifikasi tugas, peer, dsb.
            //  - isMeta / isCompactSummary → teks sistem, ringkasan auto
            //  - isSidechain → prompt ke subagent, bukan dari manusia
            if (($b['origin']['kind'] ?? null) !== 'human') return null;
            if (! empty($b['isMeta']) || ! empty($b['isCompactSummary'])) return null;
            if (! empty($b['isSidechain'])) return null;

            $teks = self::ambilTeks($content);
            if ($teks === '') return null;

            return [
                'jenis' => 'prompt',
                'uuid'  => $uuid,
                'waktu' => self::waktu($b),
                'alat'  => null,
                'sasaran' => null,
                'isi'   => self::potong(self::samarkan($teks)),
            ];
        }

        // assistant: bisa berisi thinking, text, atau tool_use.
        // Satu baris Claude Code = satu blok. Kita simpan teks & tool_use;
        // thinking sengaja dilewati (bukan keluaran untuk pengguna).
        if (! is_array($content)) return null;
        foreach ($content as $blok) {
            $jb = $blok['type'] ?? null;
            if ($jb === 'text') {
                $teks = trim((string) ($blok['text'] ?? ''));
                if ($teks === '') return null;
                return [
                    'jenis' => 'balasan',
                    'uuid'  => $uuid,
                    'waktu' => self::waktu($b),
                    'alat'  => null,
                    'sasaran' => null,
                    'isi'   => self::potong(self::samarkan($teks)),
                ];
            }
            if ($jb === 'tool_use') {
                return [
                    'jenis'   => 'alat',
                    'uuid'    => $uuid,
                    'waktu'   => self::waktu($b),
                    'alat'    => substr((string) ($blok['name'] ?? '?'), 0, 100),
                    'sasaran' => self::sasaranAlat($blok['name'] ?? '', $blok['input'] ?? []),
                    'isi'     => null,
                ];
            }
        }
        return null;
    }

    /**
     * Pemakaian token dari satu baris assistant, dikunci request_id agar
     * blok-blok dalam satu permintaan API tak terhitung berganda.
     *
     * @return array{request_id:string, waktu:?string, model:?string, masuk:int, keluar:int}|null
     */
    public static function uraiUsage(array $b): ?array
    {
        if (($b['type'] ?? null) !== 'assistant') return null;
        $rid   = $b['requestId'] ?? null;
        $usage = $b['message']['usage'] ?? null;
        if (! $rid || ! is_array($usage)) return null;

        $masuk = (int) ($usage['input_tokens'] ?? 0)
            + (int) ($usage['cache_creation_input_tokens'] ?? 0)
            + (int) ($usage['cache_read_input_tokens'] ?? 0);

        return [
            'request_id' => substr((string) $rid, 0, 100),
            'waktu'      => self::waktu($b),
            'model'      => substr((string) ($b['message']['model'] ?? ''), 0, 80) ?: null,
            'masuk'      => $masuk,
            'keluar'     => (int) ($usage['output_tokens'] ?? 0),
        ];
    }

    // ── Pembantu ──────────────────────────────────────────────────────────

    /** Ambil teks dari content yang bisa string atau array blok. */
    private static function ambilTeks($content): string
    {
        if (is_string($content)) return trim($content);
        if (! is_array($content)) return '';
        $potong = [];
        foreach ($content as $blok) {
            // Hanya blok teks. tool_result (isi berkas yang dibaca) dilewati.
            if (($blok['type'] ?? null) === 'text') {
                $potong[] = (string) ($blok['text'] ?? '');
            }
        }
        return trim(implode("\n", $potong));
    }

    /** Ringkas sasaran panggilan alat tanpa menyimpan seluruh argumen. */
    private static function sasaranAlat(string $nama, array $input): ?string
    {
        $s = match (true) {
            isset($input['file_path'])  => $input['file_path'],
            isset($input['command'])    => $input['command'],
            isset($input['pattern'])    => $input['pattern'],
            isset($input['url'])        => $input['url'],
            isset($input['description'])=> $input['description'],
            default => null,
        };
        return $s === null ? null : self::potong(self::samarkan((string) $s), 500);
    }

    private static function waktu(array $b): ?string
    {
        $t = $b['timestamp'] ?? null;
        if (! $t) return null;
        $ts = strtotime((string) $t);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private static function potong(?string $teks, int $maks = self::MAKS_ISI): ?string
    {
        if ($teks === null) return null;
        return mb_strlen($teks) > $maks
            ? mb_substr($teks, 0, $maks) . "\n…[dipotong]"
            : $teks;
    }

    /** Nama proyek ringkas dari cwd (ambil segmen terakhir). */
    public static function namaProyek(?string $cwd): ?string
    {
        if (! $cwd) return null;
        $cwd = rtrim(str_replace('\\', '/', $cwd), '/');
        $seg = substr($cwd, strrpos($cwd, '/') + 1);
        return $seg !== '' ? substr($seg, 0, 150) : null;
    }
}
