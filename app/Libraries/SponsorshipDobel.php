<?php

namespace App\Libraries;

/**
 * Pendeteksi sponsor DOBEL antara program mandiri (sponsorship_*) dan event
 * (event_sponsors). Kasus nyata: September 2026 diinput dua kali — sebagai
 * Program 12 dan sebagai Event 15 — sehingga deal terhitung dobel di Summary.
 *
 * Pembagian tugas:
 *  - Fungsi MURNI (normalisasi, skor nama, irisan periode, cocokkan) tidak
 *    menyentuh database, jadi mudah diuji: lihat `php spark mic:sp-uji-dobel`.
 *  - deteksi() / idDikecualikan() / cariSerupa() memuat data lalu memanggil
 *    fungsi murni itu.
 *
 * Pendeteksi TIDAK pernah mengubah data sponsor/realisasi. Hanya keputusan tim di
 * tabel `sponsorship_dobel` yang berpengaruh: status 'dobel' mengecualikan SATU
 * sisi (program/event) dari total gabungan lewat idDikecualikan() — satu-satunya
 * pintu pengecualian yang dipakai semua agregat Summary & Laporan Bulanan.
 */
class SponsorshipDobel
{
    /** Kemiripan nama minimal agar dianggap "mirip". */
    public const AMBANG_MIRIP = 0.85;
    /** Kemiripan nama minimal agar dianggap "sama" (hanya beda huruf besar/kecil, tanda baca, spasi). */
    public const AMBANG_SAMA = 0.97;
    /** Selisih nilai relatif maksimum untuk "nama sama + nilai beda". */
    public const SELISIH_MAKS = 0.5;
    /** Status deal program yang ikut dihitung di total (sama dengan getCommittedByPrograms). */
    public const STATUS_DEAL = ['terkonfirmasi', 'lunas'];

    /** Kata generik yang diabaikan saat membandingkan inti nama ("GC Korean BBQ" ≈ "GC Korean"). */
    private const KATA_UMUM = ['pt', 'cv', 'tbk', 'ud', 'the', 'dan', 'and', 'bbq', 'official'];

    /** @var array<string,array>|null memo per-request untuk idDikecualikan() */
    private static ?array $memoKecuali = null;

    // ══ Fungsi murni ═════════════════════════════════════════════════════

    /** Huruf kecil, tanda baca → spasi, spasi ganda dirapatkan, di-trim. */
    public static function normalisasi(string $nama): string
    {
        $s = mb_strtolower($nama, 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
    }

    /** Nama ternormalisasi tanpa kata generik (pt, cv, bbq, ...). Bila habis, pakai apa adanya. */
    public static function inti(string $normal): string
    {
        $tok = array_values(array_filter(explode(' ', $normal), fn($t) => $t !== '' && ! in_array($t, self::KATA_UMUM, true)));
        return $tok ? implode(' ', $tok) : $normal;
    }

    /** Kemiripan 0..1 antara dua nama sponsor (1 = sama setelah normalisasi). */
    public static function skorNama(string $a, string $b): float
    {
        $na = self::normalisasi($a);
        $nb = self::normalisasi($b);
        if ($na === '' || $nb === '') return 0.0;
        if ($na === $nb) return 1.0;
        if (str_replace(' ', '', $na) === str_replace(' ', '', $nb)) return 0.98;      // "toys kingdom" vs "toyskingdom"
        $ia = self::inti($na);
        $ib = self::inti($nb);
        if ($ia === $ib) return 0.95;                                                   // "gc korean bbq" vs "gc korean"
        return max(self::rasioLev($na, $nb), self::rasioLev($ia, $ib));
    }

    private static function rasioLev(string $a, string $b): float
    {
        $a = substr($a, 0, 250);
        $b = substr($b, 0, 250);
        $maks = max(strlen($a), strlen($b));
        if ($maks === 0) return 0.0;
        return 1 - levenshtein($a, $b) / $maks;
    }

    /** Selisih relatif nilai 0..1 (0 = sama). Dua-duanya 0 → 0. */
    public static function selisihRelatif(int $a, int $b): float
    {
        $m = max($a, $b);
        return $m <= 0 ? 0.0 : abs($a - $b) / $m;
    }

    /**
     * Keyakinan sepasang sponsor: 'kuat' | 'sedang' | null (lemah → tidak ditampilkan).
     * kuat   = nama sama + nilai sama.
     * sedang = nama mirip + nilai sama, ATAU nama sama + nilai beda <= 50%.
     */
    public static function keyakinan(string $namaA, int $nilaiA, string $namaB, int $nilaiB): ?string
    {
        $skor = self::skorNama($namaA, $namaB);
        $sama = $nilaiA === $nilaiB;
        if ($skor >= self::AMBANG_SAMA && $sama) return 'kuat';
        if ($skor >= self::AMBANG_MIRIP && $sama) return 'sedang';
        if ($skor >= self::AMBANG_SAMA && self::selisihRelatif($nilaiA, $nilaiB) <= self::SELISIH_MAKS) return 'sedang';
        return null;
    }

    /**
     * Himpunan bulan ('Y-m') yang disentuh sebuah periode; null bila periode tak diketahui.
     * Dilonggarkan ke tingkat BULAN: periode "beririsan" bila berbagi minimal satu bulan.
     *
     * @return string[]|null
     */
    public static function bulanPeriode(?string $mulai, ?string $selesai): ?array
    {
        if (! $mulai || $mulai === '0000-00-00') return null;
        $s = substr($mulai, 0, 7);
        $e = ($selesai && $selesai !== '0000-00-00') ? substr($selesai, 0, 7) : $s;
        if ($e < $s) $e = $s;
        $out = [];
        for ($m = $s, $guard = 0; $m <= $e && $guard < 240; $guard++) {
            $out[] = $m;
            $m = date('Y-m', strtotime($m . '-01 +1 month'));
        }
        return $out;
    }

    /** @return string[]|null */
    public static function bulanProgram(array $p): ?array
    {
        return self::bulanPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null);
    }

    /** Event: start_date .. start_date + event_days - 1. @return string[]|null */
    public static function bulanEvent(array $e): ?array
    {
        $mulai = $e['start_date'] ?? null;
        if (! $mulai || $mulai === '0000-00-00') return null;
        $hari = max(1, (int) ($e['event_days'] ?? 1));
        return self::bulanPeriode($mulai, date('Y-m-d', strtotime($mulai . ' +' . ($hari - 1) . ' day')));
    }

    /** Dua himpunan bulan berbagi minimal satu bulan? (null = tak diketahui = tidak). */
    public static function periodeBeririsan(?array $bulanA, ?array $bulanB): bool
    {
        if (! $bulanA || ! $bulanB) return false;
        return count(array_intersect($bulanA, $bulanB)) > 0;
    }

    /**
     * Pencocokan murni.
     *
     * @param array $programs    baris sponsorship_programs
     * @param array $sponsorProg baris sponsorship_sponsors (datar; punya program_id)
     * @param array $events      baris events (id, name, start_date, event_days, mall)
     * @param array $sponsorEv   baris event_sponsors (datar; punya event_id)
     * @param array $keputusan   baris sponsorship_dobel (program_sponsor_id, event_sponsor_id, status, ...)
     * @return array{grup:array<int,array>, pasangan_bukan:array<int,array>}
     *   grup: satu entri per (program × event) yang punya pasangan kembar. Tiap pasangan membawa
     *   'keputusan' (null = belum diputuskan) dan 'sisi_kecuali'.
     */
    public static function cocokkan(array $programs, array $sponsorProg, array $events, array $sponsorEv, array $keputusan = []): array
    {
        // Indeks sponsor + keputusan.
        $spP = [];   // id => baris
        foreach ($sponsorProg as $s) {
            if (! in_array((string) ($s['status_deal'] ?? 'lunas'), self::STATUS_DEAL, true)) continue;
            $spP[(int) $s['id']] = $s;
        }
        $spE = [];
        foreach ($sponsorEv as $s) { $spE[(int) $s['id']] = $s; }

        $putus = [];  // "ps:es" => baris keputusan
        foreach ($keputusan as $k) {
            $putus[(int) $k['program_sponsor_id'] . ':' . (int) $k['event_sponsor_id']] = $k;
        }

        $progById = [];
        foreach ($programs as $p) { $progById[(int) $p['id']] = $p; }
        $evById = [];
        foreach ($events as $e) { $evById[(int) $e['id']] = $e; }

        $progPerId = [];   // program_id => [sponsor...]
        foreach ($spP as $s) { $progPerId[(int) $s['program_id']][] = $s; }
        $evPerId = [];
        foreach ($spE as $s) { $evPerId[(int) $s['event_id']][] = $s; }

        $grup = [];
        foreach ($progPerId as $pid => $listP) {
            if (! isset($progById[$pid])) continue;
            $bulanP = self::bulanProgram($progById[$pid]);
            foreach ($evPerId as $eid => $listE) {
                if (! isset($evById[$eid])) continue;
                if (! self::periodeBeririsan($bulanP, self::bulanEvent($evById[$eid]))) continue;

                $terpakaiP = [];
                $terpakaiE = [];
                $pasangan  = [];

                // 1) Keputusan 'dobel' yang sudah ada tetap jadi pasangan (dikunci).
                foreach ($listP as $sp) {
                    foreach ($listE as $se) {
                        $k = $putus[(int) $sp['id'] . ':' . (int) $se['id']] ?? null;
                        if ($k && $k['status'] === 'dobel') {
                            $pasangan[] = self::susunPasangan($sp, $se, $k);
                            $terpakaiP[(int) $sp['id']] = true;
                            $terpakaiE[(int) $se['id']] = true;
                        }
                    }
                }

                // 2) Kandidat baru (keputusan 'bukan_dobel' dilewati), diurut yang paling yakin dulu.
                $kandidat = [];
                foreach ($listP as $sp) {
                    if (isset($terpakaiP[(int) $sp['id']])) continue;
                    foreach ($listE as $se) {
                        if (isset($terpakaiE[(int) $se['id']])) continue;
                        if (isset($putus[(int) $sp['id'] . ':' . (int) $se['id']])) continue;
                        $kuat = self::keyakinan((string) $sp['nama_sponsor'], (int) $sp['nilai'], (string) $se['nama_sponsor'], (int) $se['nilai']);
                        if ($kuat === null) continue;
                        $kandidat[] = [
                            'rank'  => $kuat === 'kuat' ? 0 : 1,
                            'skor'  => self::skorNama((string) $sp['nama_sponsor'], (string) $se['nama_sponsor']),
                            'beda'  => abs((int) $sp['nilai'] - (int) $se['nilai']),
                            'sp'    => $sp, 'se' => $se,
                        ];
                    }
                }
                usort($kandidat, function ($a, $b) {
                    return ($a['rank'] <=> $b['rank']) ?: ($b['skor'] <=> $a['skor']) ?: ($a['beda'] <=> $b['beda'])
                        ?: ((int) $a['sp']['id'] <=> (int) $b['sp']['id']) ?: ((int) $a['se']['id'] <=> (int) $b['se']['id']);
                });
                foreach ($kandidat as $c) {   // 1:1 — satu sponsor hanya dipasangkan sekali per kelompok
                    $a = (int) $c['sp']['id'];
                    $b = (int) $c['se']['id'];
                    if (isset($terpakaiP[$a]) || isset($terpakaiE[$b])) continue;
                    $terpakaiP[$a] = true;
                    $terpakaiE[$b] = true;
                    $pasangan[] = self::susunPasangan($c['sp'], $c['se'], null);
                }

                if (! $pasangan) continue;
                usort($pasangan, fn($x, $y) => (($x['keyakinan'] === 'kuat' ? 0 : 1) <=> ($y['keyakinan'] === 'kuat' ? 0 : 1)) ?: strcmp($x['nama_p'], $y['nama_p']));

                $nKuat = $nSedang = $nBelum = $nDobel = 0;
                $nilaiMin = $nilaiP = $nilaiE = 0;
                foreach ($pasangan as $ps) {
                    $ps['keyakinan'] === 'kuat' ? $nKuat++ : $nSedang++;
                    $ps['keputusan'] === null ? $nBelum++ : $nDobel++;
                    $nilaiMin += min($ps['nilai_p'], $ps['nilai_e']);
                    $nilaiP   += $ps['nilai_p'];
                    $nilaiE   += $ps['nilai_e'];
                }
                $grup[] = [
                    'program'    => $progById[$pid],
                    'event'      => $evById[$eid],
                    'pasangan'   => $pasangan,
                    'n'          => count($pasangan),
                    'n_kuat'     => $nKuat,
                    'n_sedang'   => $nSedang,
                    'n_belum'    => $nBelum,
                    'n_dobel'    => $nDobel,
                    'm_program'  => count($listP),
                    'm_event'    => count($listE),
                    'nilai_min'  => $nilaiMin,
                    'nilai_prog' => $nilaiP,
                    'nilai_event'=> $nilaiE,
                    'keyakinan'  => $nKuat > 0 && $nKuat >= $nSedang ? 'kuat' : 'sedang',
                ];
            }
        }
        // Yang masih perlu diperiksa dulu, lalu yang terbanyak.
        usort($grup, fn($a, $b) => (($b['n_belum'] > 0) <=> ($a['n_belum'] > 0)) ?: ($b['n'] <=> $a['n']));

        return ['grup' => $grup];
    }

    private static function susunPasangan(array $sp, array $se, ?array $k): array
    {
        $kuat = self::keyakinan((string) $sp['nama_sponsor'], (int) $sp['nilai'], (string) $se['nama_sponsor'], (int) $se['nilai']);
        return [
            'ps_id'     => (int) $sp['id'],
            'es_id'     => (int) $se['id'],
            'nama_p'    => (string) $sp['nama_sponsor'],
            'nama_e'    => (string) $se['nama_sponsor'],
            'nilai_p'   => (int) $sp['nilai'],
            'nilai_e'   => (int) $se['nilai'],
            'selisih'   => (int) $sp['nilai'] - (int) $se['nilai'],
            'jenis_p'   => (string) ($sp['jenis'] ?? ''),
            'jenis_e'   => (string) ($se['jenis'] ?? ''),
            'skor'      => round(self::skorNama((string) $sp['nama_sponsor'], (string) $se['nama_sponsor']), 3),
            'keyakinan' => $kuat ?? 'sedang',   // pasangan yang dikunci keputusan 'dobel' tetap tampil walau datanya kini berubah
            'keputusan' => $k,
        ];
    }

    /** Ringkasan satu kalimat untuk sebuah kelompok. */
    public static function ringkasan(array $g): string
    {
        return 'Program ' . $g['program']['nama_program'] . ' ↔ Event ' . $g['event']['name'] . ': '
            . $g['n'] . ' dari ' . $g['m_program'] . ' sponsor kembar, Rp ' . number_format($g['nilai_min'], 0, ',', '.');
    }

    // ══ Akses data ═══════════════════════════════════════════════════════

    /** Semua keputusan, dengan nama pelaku. */
    public static function semuaKeputusan(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sponsorship_dobel')) return [];
        return $db->table('sponsorship_dobel d')
            ->select('d.*, u1.name AS pembuat, u2.name AS pengubah')
            ->join('users u1', 'u1.id = d.dibuat_oleh', 'left')
            ->join('users u2', 'u2.id = d.diubah_oleh', 'left')
            ->orderBy('COALESCE(d.diubah_pada, d.dibuat_pada)', 'DESC', false)->orderBy('d.id', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Deteksi lengkap dari database.
     *
     * @return array{grup:array, keputusan:array, n_keputusan:int, n_belum:int}
     *   keputusan: riwayat (dobel & bukan_dobel) lengkap dengan nama kedua sisi.
     */
    public static function deteksi(): array
    {
        $db         = db_connect();
        $programs   = $db->table('sponsorship_programs')->get()->getResultArray();
        $sponsorP   = $db->table('sponsorship_sponsors')->whereIn('status_deal', self::STATUS_DEAL)->get()->getResultArray();
        $events     = $db->table('events')->select('id, name, mall, start_date, event_days')->get()->getResultArray();
        $sponsorE   = $db->table('event_sponsors')->get()->getResultArray();
        $keputusan  = self::semuaKeputusan();

        $hasil = self::cocokkan($programs, $sponsorP, $events, $sponsorE, $keputusan);

        // Riwayat keputusan, diperkaya nama kedua sisi (hanya yang kedua sponsornya masih ada).
        $spPAll = [];
        foreach ($db->table('sponsorship_sponsors s')->select('s.id, s.nama_sponsor, s.nilai, s.program_id, p.nama_program')
            ->join('sponsorship_programs p', 'p.id = s.program_id', 'left')->get()->getResultArray() as $r) { $spPAll[(int) $r['id']] = $r; }
        $spEAll = [];
        foreach ($db->table('event_sponsors s')->select('s.id, s.nama_sponsor, s.nilai, s.event_id, e.name AS event_name')
            ->join('events e', 'e.id = s.event_id', 'left')->get()->getResultArray() as $r) { $spEAll[(int) $r['id']] = $r; }

        $riwayat = [];
        foreach ($keputusan as $k) {
            $p = $spPAll[(int) $k['program_sponsor_id']] ?? null;
            $e = $spEAll[(int) $k['event_sponsor_id']] ?? null;
            if (! $p || ! $e) continue;
            $k['p'] = $p;
            $k['e'] = $e;
            $riwayat[] = $k;
        }
        $nBelum = 0;
        foreach ($hasil['grup'] as $g) { $nBelum += $g['n_belum']; }

        return ['grup' => $hasil['grup'], 'keputusan' => $riwayat, 'n_keputusan' => count($riwayat), 'n_belum' => $nBelum];
    }

    /**
     * SATU-SATUNYA pintu pengecualian dobel. Sponsor yang dikonfirmasi dobel dan berada di sisi yang
     * dikecualikan, selama KEDUA sponsor pada pasangan itu masih ada (agar tak ada total yang hilang
     * bila sisi pembanding sudah dihapus).
     *
     * @return array{program:int[], event:int[]}
     */
    public static function idDikecualikan(bool $segarkan = false): array
    {
        if (self::$memoKecuali !== null && ! $segarkan) return self::$memoKecuali;
        $out = ['program' => [], 'event' => []];
        $db  = db_connect();
        if ($db->tableExists('sponsorship_dobel')) {
            $rows = $db->table('sponsorship_dobel d')
                ->select('d.program_sponsor_id, d.event_sponsor_id, d.sisi_kecuali')
                ->join('sponsorship_sponsors ps', 'ps.id = d.program_sponsor_id')
                ->join('event_sponsors es', 'es.id = d.event_sponsor_id')
                ->where('d.status', 'dobel')
                ->get()->getResultArray();
            foreach ($rows as $r) {
                if ($r['sisi_kecuali'] === 'event')        $out['event'][]   = (int) $r['event_sponsor_id'];
                elseif ($r['sisi_kecuali'] === 'program')  $out['program'][] = (int) $r['program_sponsor_id'];
            }
            $out['program'] = array_values(array_unique($out['program']));
            $out['event']   = array_values(array_unique($out['event']));
        }
        return self::$memoKecuali = $out;
    }

    public static function lupakanMemo(): void
    {
        self::$memoKecuali = null;
    }

    /**
     * Label "dicatat di ..." untuk sponsor yang dikecualikan.
     *
     * @return array{program:array<int,string>, event:array<int,string>}  sponsor_id => keterangan sisi lain
     */
    public static function labelDikecualikan(): array
    {
        $out = ['program' => [], 'event' => []];
        $db  = db_connect();
        if (! $db->tableExists('sponsorship_dobel')) return $out;
        $rows = $db->table('sponsorship_dobel d')
            ->select('d.program_sponsor_id, d.event_sponsor_id, d.sisi_kecuali, p.nama_program, e.name AS event_name')
            ->join('sponsorship_sponsors ps', 'ps.id = d.program_sponsor_id')
            ->join('event_sponsors es', 'es.id = d.event_sponsor_id')
            ->join('sponsorship_programs p', 'p.id = ps.program_id', 'left')
            ->join('events e', 'e.id = es.event_id', 'left')
            ->where('d.status', 'dobel')
            ->get()->getResultArray();
        foreach ($rows as $r) {
            if ($r['sisi_kecuali'] === 'program')   $out['program'][(int) $r['program_sponsor_id']] = 'Event ' . $r['event_name'];
            elseif ($r['sisi_kecuali'] === 'event') $out['event'][(int) $r['event_sponsor_id']]     = 'Program ' . $r['nama_program'];
        }
        return $out;
    }

    /**
     * Peringatan lunak saat menambah/mengubah sponsor: adakah sponsor serupa (nama mirip + NILAI SAMA)
     * di sisi lain pada periode beririsan? Mengembalikan teks peringatan, atau null.
     *
     * @param string $sisiAsal 'program' (sponsor ada di program mandiri) atau 'event'
     * @param int    $indukId  id program (sisi program) atau id event (sisi event)
     */
    public static function cariSerupa(string $sisiAsal, int $indukId, string $nama, int $nilai): ?string
    {
        if ($nilai <= 0 || trim($nama) === '') return null;
        $db = db_connect();

        if ($sisiAsal === 'program') {
            $induk = $db->table('sponsorship_programs')->where('id', $indukId)->get()->getRowArray();
            if (! $induk) return null;
            $bulan = self::bulanProgram($induk);
            if (! $bulan) return null;
            $events = $db->table('events')->select('id, name, start_date, event_days')->get()->getResultArray();
            foreach ($events as $e) {
                if (! self::periodeBeririsan($bulan, self::bulanEvent($e))) continue;
                $rows = $db->table('event_sponsors')->where('event_id', $e['id'])->where('nilai', $nilai)->get()->getResultArray();
                foreach ($rows as $r) {
                    if (self::skorNama($nama, (string) $r['nama_sponsor']) >= self::AMBANG_MIRIP) return 'Event ' . $e['name'];
                }
            }
            return null;
        }

        $e = $db->table('events')->select('id, name, start_date, event_days')->where('id', $indukId)->get()->getRowArray();
        if (! $e) return null;
        $bulan = self::bulanEvent($e);
        if (! $bulan) return null;
        $programs = $db->table('sponsorship_programs')->get()->getResultArray();
        foreach ($programs as $p) {
            if (! self::periodeBeririsan($bulan, self::bulanProgram($p))) continue;
            $rows = $db->table('sponsorship_sponsors')->where('program_id', $p['id'])->where('nilai', $nilai)
                ->whereIn('status_deal', self::STATUS_DEAL)->get()->getResultArray();
            foreach ($rows as $r) {
                if (self::skorNama($nama, (string) $r['nama_sponsor']) >= self::AMBANG_MIRIP) return 'Program ' . $p['nama_program'];
            }
        }
        return null;
    }

    /** Teks flash standar untuk peringatan lunak. */
    public static function teksPeringatan(string $label): string
    {
        return 'Sponsor serupa sudah ada di ' . $label . '; cek agar tidak dobel.';
    }
}
