<?php

namespace App\Libraries;

/**
 * Pemeriksa "salah tahun" Budget Departemen — HANYA MEMBACA, tidak mengubah data.
 * Mencari pola yang lazim terjadi saat angka masuk ke tahun yang keliru:
 *  (a) realisasi bertanggal jauh di depan (> 1 bulan dari hari ini);
 *  (b) pos yang rencana tahun ini kosong sementara rencana tahun sebelah terisi
 *      mirip realisasi tahun ini (pola tertukar);
 *  (c) rencana tahun sebelah yang IDENTIK dengan tahun ini per pos (salinan
 *      yang belum disesuaikan) dibanding yang sudah berbeda.
 * Hasilnya petunjuk — "mungkin salah tahun?" — bukan putusan.
 */
class BudgetCekTahun
{
    /** Selisih wajar rencana vs realisasi untuk dianggap "mirip" (±40%). */
    const MIRIP_MIN = 0.6;
    const MIRIP_MAKS = 1.4;

    /**
     * @return array{jauh: array, tertukar: array, salinan: array, ada: bool}
     *   salinan = [['a'=>2026,'b'=>2027,'identik'=>[nama..],'beda'=>int,'semua'=>bool], ...]
     */
    public static function periksa(int $deptId, int $tahun, int $mulai): array
    {
        $db = db_connect();
        $out = ['jauh' => [], 'tertukar' => [], 'salinan' => [], 'ada' => false];

        // (a) realisasi manual bertanggal > 1 bulan dari hari ini
        $batas = date('Y-m-d', strtotime('+1 month'));
        $out['jauh'] = $db->query(
            "SELECT x.id, x.tanggal, x.uraian, x.nilai, p.nama AS pos
               FROM budget_realisasi x JOIN budget_pos p ON p.id = x.pos_id
              WHERE x.dept_id = ? AND x.tanggal > ? ORDER BY x.tanggal LIMIT 30", [$deptId, $batas]
        )->getResultArray();

        // total rencana & realisasi per pos untuk tahun yang relevan
        $tahunSet = array_values(array_filter([$tahun - 1, $tahun, $tahun + 1], fn ($t) => $t >= $mulai));
        $ren = $rea = [];
        $in = implode(',', array_map('intval', $tahunSet));
        $pos = array_column($db->table('budget_pos')->select('id, nama')->where('dept_id', $deptId)->get()->getResultArray(), 'nama', 'id');
        foreach ($db->query(
            "SELECT r.pos_id, r.tahun, r.bulan, r.mall, ROUND(r.jumlah,2) AS j FROM budget_rencana r
               JOIN budget_pos p ON p.id = r.pos_id WHERE p.dept_id = ? AND r.tahun IN ($in)", [$deptId]
        )->getResultArray() as $r) {
            $k = $r['bulan'] . '|' . $r['mall'];
            $ren[(int) $r['tahun']][(int) $r['pos_id']][$k] = ($ren[(int) $r['tahun']][(int) $r['pos_id']][$k] ?? 0) + (float) $r['j'];
        }
        foreach ($tahunSet as $t) {
            foreach ((new \App\Models\BudgetRealisasiModel())->totalTahun($deptId, $t) as $r) {
                $rea[$t][(int) $r['pos_id']] = ($rea[$t][(int) $r['pos_id']] ?? 0) + (float) $r['nilai'];
            }
        }
        $totRen = fn (int $t, int $p) => array_sum($ren[$t][$p] ?? []);

        // (b) rencana tahun ini kosong, rencana tahun sebelah terisi mirip realisasi tahun ini
        foreach ([$tahun + 1, $tahun - 1] as $b) {
            if (! in_array($b, $tahunSet, true)) continue;
            foreach ($pos as $pid => $nama) {
                $r0 = $totRen($tahun, $pid); $rb = $totRen($b, $pid); $re = $rea[$tahun][$pid] ?? 0;
                if ($r0 > 0.5 || $rb <= 0.5 || $re <= 0.5) continue;
                $rasio = $rb / $re;
                if ($rasio < self::MIRIP_MIN || $rasio > self::MIRIP_MAKS) continue;
                $out['tertukar'][] = ['pos_id' => $pid, 'pos' => $nama, 'tahun_kosong' => $tahun, 'tahun_isi' => $b,
                                      'rencana_isi' => $rb, 'realisasi' => $re];
            }
        }

        // (c) rencana dua tahun bersebelahan: identik per pos vs berbeda
        foreach ([[$tahun - 1, $tahun], [$tahun, $tahun + 1]] as [$a, $b]) {
            if (! in_array($a, $tahunSet, true) || ! in_array($b, $tahunSet, true)) continue;
            $identik = []; $beda = 0;
            foreach ($pos as $pid => $nama) {
                $x = $ren[$a][$pid] ?? []; $y = $ren[$b][$pid] ?? [];
                if (! $x && ! $y) continue;
                if (! $x || ! $y) { $beda++; continue; }
                ksort($x); ksort($y);
                if (self::samaMap($x, $y)) $identik[] = $nama; else $beda++;
            }
            if ($identik) {
                $out['salinan'][] = ['a' => $a, 'b' => $b, 'identik' => $identik, 'beda' => $beda, 'semua' => $beda === 0];
            }
        }

        $out['ada'] = $out['jauh'] || $out['tertukar'] || $out['salinan'];
        return $out;
    }

    private static function samaMap(array $x, array $y): bool
    {
        if (array_keys($x) !== array_keys($y)) return false;
        foreach ($x as $k => $v) if (abs($v - $y[$k]) > 0.5) return false;
        return true;
    }
}
