<?php

namespace App\Libraries;

/**
 * Rekap Budget Departemen — murni hitung, tanpa DB, supaya bisa diuji.
 *
 * Total SELALU dijumlah dari baris (rencana per bulan, transaksi realisasi),
 * tidak pernah dari kolom ringkasan yang tersimpan: Excel LPSS 2026 meleset
 * ±455 jt karena rumus total menunjuk kolom yang salah mulai Juni.
 */
class BudgetRekap
{
    const MAL = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];

    /** Pilihan mal untuk satu transaksi realisasi ('' = tanpa mal). */
    const MAL_REALISASI = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'Keduanya (50:50)'];

    /**
     * Bagian nilai yang jatuh ke filter mal.
     * 'both' dibagi 50:50; selisih pembulatan sen jatuh ke Pentacity agar
     * eWalk + Pentacity selalu tepat sama dengan nilai asli.
     */
    public static function porsi(string $mall, float $nilai, string $filter): float
    {
        if ($filter === 'semua') return $nilai;
        if ($mall === $filter) return $nilai;
        if ($mall !== 'both') return 0.0;
        $separuh = round($nilai / 2, 2);
        return $filter === 'ewalk' ? $separuh : round($nilai - $separuh, 2);
    }

    /**
     * @param array  $pos       [['id','nama','aktif',...], ...] urut tampil
     * @param array  $rencana   [['pos_id','bulan','mall','jumlah'], ...] satu tahun
     * @param array  $realisasi [['pos_id','bulan','mall','nilai'], ...] satu tahun, bulan = bulan pembebanan
     * @param array  $lalu      [['pos_id','mall','nilai'], ...] realisasi tahun sebelumnya
     * @param string $filter    'semua' | 'ewalk' | 'pentacity'
     * @param int    $sdBulan   1..12 — batas "s.d. bulan" untuk budget berjalan
     */
    public static function susun(array $pos, array $rencana, array $realisasi, array $lalu, string $filter, int $sdBulan): array
    {
        $kosong = fn () => [
            'bulan'     => array_fill_keys(range(1, 12), ['b' => 0.0, 'r' => 0.0, 'ket' => []]),
            'budget'    => 0.0, 'budgetSd' => 0.0, 'realisasi' => 0.0, 'lalu' => 0.0,
        ];

        $baris = [];
        foreach ($pos as $p) {
            $baris[(int) $p['id']] = ['pos' => $p] + $kosong();
        }

        // Selain filter 'semua', baris tanpa mal tak masuk ke mal mana pun —
        // dicatat terpisah agar pengguna tahu ada angka yang tak terlihat.
        $tanpaMal = 0.0;

        foreach ($rencana as $r) {
            $id = (int) $r['pos_id'];
            if (! isset($baris[$id])) continue;
            $mall = (string) $r['mall'];
            $n    = (float) $r['jumlah'];
            if ($filter !== 'semua' && $mall === '') { $tanpaMal += $n; continue; }
            $v = self::porsi($mall, $n, $filter);
            $m = (int) $r['bulan'];
            $baris[$id]['bulan'][$m]['b'] += $v;
            // Rincian berketerangan (mis. "Imlek", "Idul Fitri") untuk ditampilkan
            // di rekap; baris tanpa keterangan cukup masuk total.
            $ket = trim((string) ($r['keterangan'] ?? ''));
            if ($ket !== '') {
                $baris[$id]['bulan'][$m]['ket'][] = ['k' => $ket, 'j' => $v, 'mall' => $mall];
            }
        }

        foreach ($realisasi as $r) {
            $id = (int) $r['pos_id'];
            if (! isset($baris[$id])) continue;
            $mall = (string) $r['mall'];
            $n    = (float) $r['nilai'];
            if ($filter !== 'semua' && $mall === '') { $tanpaMal += $n; continue; }
            $baris[$id]['bulan'][(int) $r['bulan']]['r'] += self::porsi($mall, $n, $filter);
        }

        foreach ($lalu as $r) {
            $id = (int) $r['pos_id'];
            if (! isset($baris[$id])) continue;
            $baris[$id]['lalu'] += self::porsi((string) $r['mall'], (float) $r['nilai'], $filter);
        }

        $total = ['pos' => null] + $kosong();
        foreach ($baris as $id => &$b) {
            foreach ($b['bulan'] as $m => $sel) {
                $b['budget']    += $sel['b'];
                $b['realisasi'] += $sel['r'];
                if ($m <= $sdBulan) $b['budgetSd'] += $sel['b'];
                $total['bulan'][$m]['b'] += $sel['b'];
                $total['bulan'][$m]['r'] += $sel['r'];
            }
            // Pos nonaktif hanya tampil bila masih punya angka tahun ini.
            if (empty($b['pos']['aktif']) && $b['budget'] == 0 && $b['realisasi'] == 0) {
                unset($baris[$id]);
                continue;
            }
            $total['budget']    += $b['budget'];
            $total['budgetSd']  += $b['budgetSd'];
            $total['realisasi'] += $b['realisasi'];
            $total['lalu']      += $b['lalu'];
        }
        unset($b);

        foreach ($baris as &$b) self::lengkapi($b);
        unset($b);
        self::lengkapi($total);

        return ['baris' => $baris, 'total' => $total, 'tanpaMal' => $tanpaMal];
    }

    /** Sisa & persen; persen null bila budget nol (realisasi tanpa budget). */
    private static function lengkapi(array &$b): void
    {
        $b['sisa']  = $b['budget'] - $b['realisasi'];
        $b['pct']   = $b['budget'] > 0 ? $b['realisasi'] / $b['budget'] * 100 : null;
        $b['pctSd'] = $b['budgetSd'] > 0 ? $b['realisasi'] / $b['budgetSd'] * 100 : null;
    }

    /**
     * Rincian satu sel dari isian grid → baris siap simpan.
     * Terima [{k: keterangan, j: nilai}, ...]; nilai berupa angka atau teks
     * Rupiah. Baris bernilai nol/kosong dibuang; keterangan dipangkas 200.
     *
     * @return list<array{keterangan: ?string, jumlah: float}>
     */
    public static function rincianSel(mixed $isian): array
    {
        if (! is_array($isian)) return [];
        $out = [];
        foreach ($isian as $b) {
            if (! is_array($b)) continue;
            $j = is_int($b['j'] ?? null) || is_float($b['j'] ?? null) ? (float) $b['j'] : self::angka((string) ($b['j'] ?? ''));
            if (! $j || $j < 0) continue;
            $k = mb_substr(trim((string) ($b['k'] ?? '')), 0, 200);
            $out[] = ['keterangan' => $k !== '' ? $k : null, 'jumlah' => round($j, 2)];
        }
        return $out;
    }

    /** "1.250.000" / "Rp 1.250.000" / "1250000" → 1250000.0; tanda minus ditolak. */
    public static function angka(?string $teks): ?float
    {
        $teks = trim((string) $teks);
        if ($teks === '') return null;
        $teks = preg_replace('/[^\d,]/', '', $teks);           // buang Rp, titik ribuan, spasi, minus
        if ($teks === '' || $teks === ',') return null;
        return (float) str_replace(',', '.', $teks);
    }
}
