<?php

namespace App\Libraries;

/**
 * RAB (Rencana Anggaran Biaya) Event & Promotion — murni hitung, tanpa DB.
 *
 * RAB bukan data tersendiri: isinya item Event Content (modul Events) yang
 * jatuh di periode RAB. Saat diajukan, isinya dibekukan jadi snapshot (array
 * di bawah) supaya PDF yang ditandatangani e-Sign tidak berubah walau event
 * diedit kemudian. Revisi = snapshot baru; perbedaannya dengan versi
 * sebelumnya ditandai baru / berubah / dihapus.
 *
 * Aturan (keputusan 14 Okt 2026):
 *  - bulan beban item = bulan tanggal item, atau bulan mulai event bila item tak bertanggal;
 *  - mal item = mal item, atau mal event; event "keduanya" tanpa mal item → 'both' (dibagi 50:50);
 *  - pos item = pos item, atau pos bawaan event;
 *  - total item = qty × freq × harga satuan bila harga satuan diisi, selain itu nilai budget lama.
 */
class RabSusun
{
    const MAL = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'Keduanya'];
    const BLN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // ── Aturan per item ──────────────────────────────────────────────

    public static function akhirEvent(array $event): string
    {
        return date('Y-m-d', strtotime($event['start_date'] . ' +' . max(0, (int) $event['event_days'] - 1) . ' days'));
    }

    public static function bulanItem(array $item, array $event): string
    {
        return substr((string) (! empty($item['tanggal']) ? $item['tanggal'] : $event['start_date']), 0, 7);
    }

    public static function mallItem(array $item, array $event): string
    {
        $m = (string) ($item['mall'] ?? '');
        if (in_array($m, ['ewalk', 'pentacity'], true)) return $m;
        $e = (string) ($event['mall'] ?? '');
        return in_array($e, ['ewalk', 'pentacity'], true) ? $e : 'both';
    }

    public static function posItem(array $item, array $event): ?int
    {
        $p = (int) ($item['pos_id'] ?? 0) ?: (int) ($event['pos_id'] ?? 0);
        return $p ?: null;
    }

    public static function totalItem(array $item): float
    {
        if (isset($item['harga_satuan']) && $item['harga_satuan'] !== null && $item['harga_satuan'] !== '') {
            $qty  = ($item['qty'] ?? null) === null || $item['qty'] === '' ? 1 : (float) $item['qty'];
            $freq = ($item['freq'] ?? null) === null || $item['freq'] === '' ? 1 : (float) $item['freq'];
            return round($qty * $freq * (float) $item['harga_satuan']);
        }
        return (float) ($item['budget'] ?? 0);
    }

    /** ['2026-11', '2026-12'] */
    public static function daftarBulan(string $mulai, string $selesai): array
    {
        $out = [];
        for ($t = strtotime($mulai . '-01'); date('Y-m', $t) <= $selesai; $t = strtotime('+1 month', $t)) $out[] = date('Y-m', $t);
        return $out;
    }

    public static function labelBulan(string $ym): string
    {
        return self::BLN[(int) substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4);
    }

    public static function labelPeriode(string $mulai, string $selesai): string
    {
        if ($mulai === $selesai) return self::labelBulan($mulai);
        $a = self::BLN[(int) substr($mulai, 5, 2)];
        return substr($mulai, 0, 4) === substr($selesai, 0, 4)
            ? $a . ' – ' . self::labelBulan($selesai)
            : self::labelBulan($mulai) . ' – ' . self::labelBulan($selesai);
    }

    // ── Snapshot ─────────────────────────────────────────────────────

    /** Satu item Event Content dalam bentuk baris RAB (bulan, mal, pos sudah diturunkan dari event). */
    public static function barisItem(array $it, array $ev): array
    {
        $num = fn ($k) => isset($it[$k]) && $it[$k] !== null && $it[$k] !== '' ? (float) $it[$k] : null;
        return [
            'id'           => (int) ($it['id'] ?? 0),
            'event_id'     => (int) ($it['event_id'] ?? $ev['id'] ?? 0),
            'bulan'        => self::bulanItem($it, $ev),
            'mall'         => self::mallItem($it, $ev),
            'pos_id'       => self::posItem($it, $ev),
            'kelompok'     => trim((string) ($it['kelompok'] ?? '')),
            'nama'         => trim((string) ($it['nama'] ?? '')),
            'tanggal'      => $it['tanggal'] ?? null,
            'qty'          => $num('qty'),
            'satuan'       => (string) ($it['satuan'] ?? ''),
            'freq'         => $num('freq'),
            'satuan_freq'  => (string) ($it['satuan_freq'] ?? ''),
            'harga_satuan' => $num('harga_satuan'),
            'total'        => self::totalItem($it),
            'keterangan'   => trim((string) ($it['keterangan'] ?? '')),
            'urutan'       => (int) ($it['urutan'] ?? 0),
        ];
    }

    /**
     * Bekukan isi RAB satu periode.
     *
     * @param array $pos     [['id','nama','urutan'], …] pos dept ini
     * @param array $budget  baris budget_rencana periode ini: [['pos_id','tahun','bulan','mall','jumlah'], …]
     * @param array $events  event yang bersinggungan dengan periode (+ 'lokasi' teks opsional)
     * @param array $items   item Event Content milik event-event itu
     */
    public static function snapshot(array $pos, array $budget, array $events, array $items, string $mulai, string $selesai): array
    {
        $bulan  = self::daftarBulan($mulai, $selesai);
        $evById = array_column($events, null, 'id');
        $posIds = array_map('intval', array_column($pos, 'id'));

        $isi = [];
        foreach ($items as $it) {
            $ev = $evById[$it['event_id']] ?? null;
            if (! $ev) continue;
            $baris = self::barisItem($it, $ev);
            if (! in_array($baris['bulan'], $bulan, true)) continue;
            if ($baris['pos_id'] !== null && ! in_array($baris['pos_id'], $posIds, true)) continue;   // pos dept lain → bukan RAB dept ini
            $isi[] = $baris;
        }
        usort($isi, fn ($a, $b) => [$a['bulan'], $a['mall'], $a['event_id'], $a['urutan'], $a['id']]
                               <=> [$b['bulan'], $b['mall'], $b['event_id'], $b['urutan'], $b['id']]);

        $dipakai = array_unique(array_column($isi, 'event_id'));
        $ev = [];
        foreach ($events as $e) {
            $akhir = self::akhirEvent($e);
            // Event tanpa item di periode tetap dicantumkan bila jadwalnya di periode (mis. "tbc").
            $singgung = substr($e['start_date'], 0, 7) <= $selesai && substr($akhir, 0, 7) >= $mulai;
            if (! in_array((int) $e['id'], $dipakai, false) && ! $singgung) continue;
            $ev[(int) $e['id']] = [
                'id'     => (int) $e['id'],
                'nama'   => trim((string) $e['name']),
                'jenis'  => (string) ($e['jenis'] ?? 'event'),
                'mall'   => (string) ($e['mall'] ?? ''),
                'mulai'  => $e['start_date'],
                'akhir'  => $akhir,
                'lokasi' => (string) ($e['lokasi'] ?? ''),
                'pos_id' => (int) ($e['pos_id'] ?? 0) ?: null,
            ];
        }

        $bud = [];
        foreach ($budget as $r) {
            $b = sprintf('%04d-%02d', $r['tahun'], $r['bulan']);
            if (! in_array($b, $bulan, true) || ! in_array((int) $r['pos_id'], $posIds, true)) continue;
            $k = $b . '|' . ($r['mall'] ?: 'both') . '|' . (int) $r['pos_id'];
            $bud[$k] = ($bud[$k] ?? 0) + (float) $r['jumlah'];
        }

        return [
            'periode' => ['mulai' => $mulai, 'selesai' => $selesai],
            'pos'     => array_map(fn ($p) => ['id' => (int) $p['id'], 'nama' => (string) $p['nama']], array_values($pos)),
            'budget'  => $bud,
            'events'  => $ev,
            'items'   => $isi,
        ];
    }

    // ── Ringkasan budget vs RAB ──────────────────────────────────────

    /**
     * Per bulan × mal × pos: budget, rab, sisa — dan total per bulan×mal & seluruhnya.
     * Item 'both' dibagi 50:50 ke eWalk & Pentacity — pembulatannya sama dengan
     * BudgetRekap::porsi(), jadi angka RAB & Budget Departemen selalu cocok.
     */
    public static function ringkasan(array $snap): array
    {
        $bulan = self::daftarBulan($snap['periode']['mulai'], $snap['periode']['selesai']);
        $sel = [];
        $kosong = fn () => ['budget' => 0.0, 'rab' => 0.0];
        foreach ($bulan as $b) foreach (['ewalk', 'pentacity'] as $m) foreach ($snap['pos'] as $p) $sel[$b][$m][$p['id']] = $kosong();

        foreach ($snap['budget'] as $k => $v) {
            [$b, $m, $p] = explode('|', $k);
            foreach (self::bagiMal($m, (float) $v) as $mm => $vv) {
                $sel[$b][$mm][(int) $p] ??= $kosong();
                $sel[$b][$mm][(int) $p]['budget'] += $vv;
            }
        }
        $tanpaPos = 0.0;
        foreach ($snap['items'] as $it) {
            if ($it['pos_id'] === null) { $tanpaPos += $it['total']; continue; }
            foreach (self::bagiMal($it['mall'], $it['total']) as $mm => $vv) {
                $sel[$it['bulan']][$mm][$it['pos_id']] ??= $kosong();
                $sel[$it['bulan']][$mm][$it['pos_id']]['rab'] += $vv;
            }
        }

        $total = $kosong(); $perPos = []; $perBM = [];
        foreach ($sel as $b => $mal) foreach ($mal as $m => $poss) foreach ($poss as $p => $v) {
            $perBM[$b][$m] ??= $kosong();
            $perPos[$p] ??= $kosong();
            foreach (['budget', 'rab'] as $k) { $perBM[$b][$m][$k] += $v[$k]; $perPos[$p][$k] += $v[$k]; $total[$k] += $v[$k]; }
        }
        return ['sel' => $sel, 'perBulanMal' => $perBM, 'perPos' => $perPos, 'total' => $total, 'tanpaPos' => $tanpaPos];
    }

    public static function bagiMal(string $mall, float $nilai): array
    {
        if ($mall !== 'both') return [$mall => $nilai];
        $e = round($nilai / 2, 2);
        return ['ewalk' => $e, 'pentacity' => round($nilai - $e, 2)];
    }

    // ── Pemeriksaan sebelum diajukan ─────────────────────────────────

    /** Daftar masalah yang MENGHALANGI pengajuan, dan catatan yang sekadar info. */
    public static function periksa(array $snap): array
    {
        $halang = []; $info = [];
        $ev = $snap['events'];
        foreach ($snap['items'] as $it) {
            $nama = ($ev[$it['event_id']]['nama'] ?? 'Event') . ' — ' . $it['nama'];
            if ($it['pos_id'] === null) $halang[] = "$nama: belum ada pos budget";
            if ($it['total'] <= 0)      $halang[] = "$nama: total biaya masih 0";
        }
        if (! $snap['items']) $halang[] = 'Belum ada item biaya di periode ini.';
        $adaItem = array_unique(array_column($snap['items'], 'event_id'));
        foreach ($ev as $e) {
            if (! in_array($e['id'], $adaItem, true)) $info[] = $e['nama'] . ': belum ada biaya (tercantum sebagai info / tbc)';
        }
        $r = self::ringkasan($snap);
        foreach ($r['sel'] as $b => $mal) foreach ($mal as $m => $poss) foreach ($poss as $p => $v) {
            if ($v['rab'] > $v['budget'] + 0.5) {
                $info[] = sprintf('%s %s · %s: RAB %s melebihi budget %s', self::labelBulan($b), self::MAL[$m], self::namaPos($snap, $p),
                    number_format($v['rab'], 0, ',', '.'), number_format($v['budget'], 0, ',', '.'));
            }
        }
        return ['halang' => $halang, 'info' => $info];
    }

    public static function namaPos(array $snap, ?int $posId): string
    {
        foreach ($snap['pos'] as $p) if ($p['id'] === $posId) return $p['nama'];
        return '(tanpa pos)';
    }

    // ── Revisi ───────────────────────────────────────────────────────

    /**
     * Beda dua snapshot: ['tanda' => [item_id => 'baru'|'berubah'], 'dihapus' => [item lama, …]].
     * "Berubah" = angka, pos, mal, bulan, nama, atau kelompok berbeda.
     */
    public static function beda(?array $lama, array $baru): array
    {
        if (! $lama) return ['tanda' => [], 'dihapus' => []];
        $kunci = ['bulan', 'mall', 'pos_id', 'kelompok', 'nama', 'qty', 'freq', 'harga_satuan', 'total', 'satuan', 'satuan_freq'];
        $l = array_column($lama['items'], null, 'id');
        $tanda = [];
        foreach ($baru['items'] as $it) {
            if (! isset($l[$it['id']])) { $tanda[$it['id']] = 'baru'; continue; }
            foreach ($kunci as $k) {
                if (($l[$it['id']][$k] ?? null) != ($it[$k] ?? null)) { $tanda[$it['id']] = 'berubah'; break; }
            }
        }
        $ada = array_column($baru['items'], 'id');
        $dihapus = array_values(array_filter($lama['items'], fn ($x) => ! in_array($x['id'], $ada, true)));
        return ['tanda' => $tanda, 'dihapus' => $dihapus];
    }

    // ── Laporan realisasi akhir bulan ────────────────────────────────

    /**
     * RAB (snapshot yang disetujui) vs realisasi satu bulan.
     *
     * @param array|null $rab       snapshot RAB berlaku (boleh null bila belum ada RAB)
     * @param array      $itemsLive snapshot-gaya item bulan ini dari data sekarang (RabSusun::snapshot(...)['items'])
     * @param array      $real      [content_item_id => total realisasi bulan beban ini]
     * @return array ['baris' => [...], 'perPos' => [...], 'total' => [...]]
     */
    public static function laporan(?array $rab, array $itemsLive, array $real, string $bulan): array
    {
        $diRab = [];
        foreach ($rab['items'] ?? [] as $it) if ($it['bulan'] === $bulan) $diRab[$it['id']] = $it;
        $live = array_column($itemsLive, null, 'id');

        $baris = [];
        foreach ($diRab as $id => $it) {
            $r = (float) ($real[$id] ?? 0);
            $baris[] = $it + ['rab' => $it['total'], 'realisasi' => $r, 'sisa' => $it['total'] - $r, 'luar' => false];
        }
        // Item yang belum ada di RAB yang disetujui (ditambah sesudahnya) — "di luar RAB".
        foreach ($live as $id => $it) {
            if (isset($diRab[$id]) || $it['bulan'] !== $bulan) continue;
            $r = (float) ($real[$id] ?? 0);
            $baris[] = $it + ['rab' => 0.0, 'realisasi' => $r, 'sisa' => -$r, 'luar' => true];
        }
        usort($baris, fn ($a, $b) => [$a['mall'], $a['event_id'], $a['luar'], $a['urutan'], $a['id']] <=> [$b['mall'], $b['event_id'], $b['luar'], $b['urutan'], $b['id']]);

        $perPos = []; $total = ['rab' => 0.0, 'realisasi' => 0.0];
        foreach ($baris as $x) {
            $k = $x['pos_id'] ?? 0;
            $perPos[$k] ??= ['rab' => 0.0, 'realisasi' => 0.0];
            $perPos[$k]['rab'] += $x['rab']; $perPos[$k]['realisasi'] += $x['realisasi'];
            $total['rab'] += $x['rab']; $total['realisasi'] += $x['realisasi'];
        }
        return ['baris' => $baris, 'perPos' => $perPos, 'total' => $total];
    }
}
