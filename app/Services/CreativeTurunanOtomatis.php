<?php

namespace App\Services;

use App\Libraries\ActivityLog;

/**
 * Turunan Design OTOMATIS dari item lain di Tema yang sama (Creative Tahap 2b).
 *
 * Aturan (sama untuk semua sumber):
 *  - SATU kebutuhan per (Design induk, jenis, ukuran): bukan per titik dan bukan per item.
 *    Ukuran berbeda -> kebutuhan berbeda; beberapa item/titik dengan jenis + ukuran sama -> satu kebutuhan
 *    dengan keterangan "Dipakai di N titik, M item cetak".
 *  - Tema belum punya Design -> dibuat otomatis (nama "Design"). Tema punya > 1 Design -> masuk ke Design pertama,
 *    kecuali item memilih Design induk lain (`design_ref`).
 *  - Kebutuhan yang tidak lagi dipakai: DIHAPUS bila belum dikerjakan (status belum, belum diajukan, tanpa berkas);
 *    bila sudah dikerjakan tetap ada dan diberi penanda "Tidak lagi dipakai ...".
 *  - Baris memakai tabel `creative_turunan` (sumber = otomatis, sumber_ref = "<sumber>:<kunci Tema>") dengan alur
 *    status / persetujuan lead yang sama seperti turunan manual.
 *
 * Sumber saat ini: 'cetak' (Media Cetak: jenis_media + ukuran). Tahap 3 menambah 'digital' (Konten Digital: format/ukuran)
 * cukup dengan menambah cabang pada kumpulkan() dan teks pada LABEL; pengelompokan / penanda / penghapusan tidak berubah.
 *
 * Idempoten: aman dipanggil berulang untuk Tema yang sama. Dipanggil setelah item sumber ditambah / diubah / dihapus /
 * dipindah dan setelah titik dipilih / dicabut.
 */
class CreativeTurunanOtomatis
{
    public const SUMBER_CETAK   = 'cetak';
    public const SUMBER_DIGITAL = 'digital';   // Tahap 3

    /** Nama sumber untuk teks penanda / keterangan. */
    private const LABEL = [
        self::SUMBER_CETAK   => 'media cetak',
        self::SUMBER_DIGITAL => 'konten digital',
    ];

    /**
     * @param string $temaKey 't5' (Tema Bulanan) | 'e13' (Event)
     * @param string $alasan  teks pendek bila ada kebutuhan yang ditandai (mis. "ukuran berubah", "item dihapus")
     * @return array{dibuat:int,diubah:int,dihapus:int,ditandai:int}
     */
    public static function sinkron(string $temaKey, string $sumber = self::SUMBER_CETAK, string $alasan = ''): array
    {
        $hasil = ['dibuat' => 0, 'diubah' => 0, 'dihapus' => 0, 'ditandai' => 0];
        if (! preg_match('/^([te])(\d+)$/', $temaKey, $m)) return $hasil;
        [$kind, $tid] = [$m[1], (int) $m[2]];
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $uid = (int) (session()->get('user_id') ?? 0) ?: null;
        $ref = $sumber . ':' . $temaKey;

        $butuh   = self::kumpulkan($sumber, $kind, $tid);
        $designs = self::designs($kind, $tid);
        if ($butuh && ! $designs) $designs = [self::buatDesign($kind, $tid, $uid)];

        // ── kelompokkan: (design, jenis, ukuran) -> satu kebutuhan ──
        $grup = [];
        $byRef = [];
        foreach ($designs as $d) $byRef[$d['scope'] . ':' . $d['id']] = $d;
        foreach ($butuh as $b) {
            $d  = $byRef[$b['design_ref'] ?? ''] ?? ($designs[0] ?? null);
            if (! $d) continue;
            $nu = CreativeTitikService::normUkuran($b['ukuran']);
            $nj = mb_strtolower(trim(preg_replace('/\s+/', ' ', $b['jenis'])));
            $key = $d['scope'] . '|' . $d['id'] . '|oto-' . $sumber . '|' . md5($nj . '|' . $nu) . '|0';
            if (! isset($grup[$key])) $grup[$key] = ['design' => $d, 'jenis' => trim($b['jenis']), 'ukuran' => trim($b['ukuran']), 'titik' => 0, 'item' => 0, 'master' => (bool) $b['master']];
            $grup[$key]['titik'] += (int) $b['titik'];
            $grup[$key]['item']++;
        }

        // ── baris otomatis yang sudah ada untuk Tema ini ──
        $ada = [];
        foreach ($db->table('creative_turunan')->where('sumber', 'otomatis')->where('sumber_ref', $ref)->get()->getResultArray() as $r) $ada[$r['kunci']] = $r;

        $katalog = self::idKatalog();
        foreach ($grup as $key => $g) {
            $ket = self::keterangan($sumber, $g['titik'], $g['item'], $g['master']);
            if (isset($ada[$key])) {
                $r = $ada[$key];
                if ((string) $r['keterangan'] !== $ket || $r['penanda'] !== null) {
                    $db->table('creative_turunan')->where('id', (int) $r['id'])->update(['keterangan' => $ket, 'penanda' => null, 'updated_at' => $now]);
                    $hasil['diubah']++;
                }
                continue;
            }
            $db->table('creative_turunan')->insert([
                'scope' => $g['design']['scope'], 'item_id' => (int) $g['design']['id'], 'jenis_id' => $katalog[mb_strtolower($g['jenis'])] ?? null,
                'nama' => mb_substr($g['jenis'], 0, 160), 'kelompok' => $sumber === self::SUMBER_DIGITAL ? 'digital' : 'cetak',
                'ukuran' => mb_substr($g['ukuran'], 0, 160), 'durasi_detik' => null,
                'status' => 'belum', 'approval_status' => 'belum', 'sumber' => 'otomatis', 'sumber_ref' => $ref,
                'keterangan' => $ket, 'penanda' => null, 'kunci' => $key,
                'dibuat_oleh' => $uid, 'created_at' => $now, 'updated_at' => $now,
            ]);
            CreativeTemaService::tandaiDikerjakan($g['design']['scope'], (int) $g['design']['id']);
            $hasil['dibuat']++;
        }

        // ── kebutuhan yang tidak lagi dipakai ──
        foreach ($ada as $key => $r) {
            if (isset($grup[$key])) continue;
            $berkas = $db->table('creative_turunan_file')->where('turunan_id', (int) $r['id'])->countAllResults();
            if ($r['status'] === 'belum' && $r['approval_status'] === 'belum' && $berkas === 0) {
                CreativeTurunanService::hapusTurunanIds([(int) $r['id']]);
                $hasil['dihapus']++;
                continue;
            }
            $tanda = 'Tidak lagi dipakai ' . (self::LABEL[$sumber] ?? $sumber) . ($alasan !== '' ? ' (' . $alasan . ')' : '');
            if ((string) $r['penanda'] === '') {   // penanda pertama (alasan awal) dipertahankan
                $db->table('creative_turunan')->where('id', (int) $r['id'])->update(['penanda' => mb_substr($tanda, 0, 160), 'updated_at' => $now]);
                $hasil['ditandai']++;
            }
        }

        if (array_sum($hasil) > 0) {
            ActivityLog::write('update', 'creative_turunan', $ref, 'Sinkron turunan otomatis', ['aksi' => 'sinkron_' . $sumber] + $hasil);
        }
        return $hasil;
    }

    // ───────────────────────────── Bantu ─────────────────────────────

    private static function keterangan(string $sumber, int $titik, int $item, bool $master = true): string
    {
        if ($sumber === self::SUMBER_CETAK) {
            if ($titik > 0) return "Dipakai di {$titik} titik, {$item} item cetak";
            return $master ? "Dipakai di {$item} item cetak (belum ada titik)" : "Dipakai di {$item} item cetak";
        }
        return "Dipakai di {$item} item konten digital";
    }

    /** Kebutuhan mentah dari item sumber: [jenis, ukuran, design_ref, titik, master]. Item "Rencana" (belum dikerjakan) tidak menimbulkan kebutuhan. */
    private static function kumpulkan(string $sumber, string $kind, int $tid): array
    {
        if ($sumber !== self::SUMBER_CETAK) return [];   // Tahap 3: Konten Digital
        $db = db_connect();
        $rows = [];

        $s = $db->table('creative_items')->select('id,jenis_media,ukuran,design_ref')->where('tipe', 'cetak')->where('dari_paket', 0);
        $kind === 'e' ? $s->where('event_id', $tid) : $s->where('tema_id', $tid);
        foreach ($s->get()->getResultArray() as $r) $rows[] = $r + ['scope' => 'standalone'];

        if ($kind === 'e') {
            $e = $db->table('event_creative_items')->select('id,jenis_media,ukuran,design_ref')->where('tipe', 'cetak')->where('dari_paket', 0)->where('event_id', $tid);
            foreach ($e->get()->getResultArray() as $r) $rows[] = $r + ['scope' => 'event'];
        }

        $out = [];
        foreach ($rows as $r) {
            $jenis = trim((string) $r['jenis_media']); $uk = trim((string) $r['ukuran']);
            if ($jenis === '' || $uk === '') continue;
            $n = $db->table('creative_cetak_titik')->where('scope', $r['scope'])->where('item_id', (int) $r['id'])->countAllResults();
            $out[] = ['jenis' => $jenis, 'ukuran' => $uk, 'design_ref' => $r['design_ref'], 'titik' => $n, 'master' => CreativeTitikService::tipeSpot($jenis) !== null];
        }
        return $out;
    }

    /** Design induk Tema (urutan tampil: item event dulu untuk Event, lalu item standalone). @return array<int,array{scope:string,id:int,nama:string}> */
    public static function designs(string $kind, int $tid): array
    {
        $db = db_connect();
        $out = [];
        if ($kind === 'e') {
            foreach ($db->table('event_creative_items')->select('id,nama')->where('event_id', $tid)->where('tipe', 'master_design')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $r) {
                $out[] = ['scope' => 'event', 'id' => (int) $r['id'], 'nama' => $r['nama']];
            }
        }
        $b = $db->table('creative_items')->select('id,nama')->where('tipe', 'master_design')->orderBy('urutan')->orderBy('id');
        $kind === 'e' ? $b->where('event_id', $tid) : $b->where('tema_id', $tid);
        foreach ($b->get()->getResultArray() as $r) $out[] = ['scope' => 'standalone', 'id' => (int) $r['id'], 'nama' => $r['nama']];
        return $out;
    }

    /** Daftar Design induk Tema untuk pilihan di formulir. */
    public static function designsTema(string $temaKey): array
    {
        if (! preg_match('/^([te])(\d+)$/', $temaKey, $m)) return [];
        return array_map(fn($d) => ['ref' => $d['scope'] . ':' . $d['id'], 'nama' => $d['nama']], self::designs($m[1], (int) $m[2]));
    }

    private static function buatDesign(string $kind, int $tid, ?int $uid): array
    {
        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $row = ['tipe' => 'master_design', 'nama' => 'Design', 'budget' => 0, 'status' => 'draft', 'urutan' => 0, 'dari_paket' => 0,
                'created_by' => $uid, 'created_at' => $now, 'updated_at' => $now];
        if ($kind === 'e') {
            $db->table('event_creative_items')->insert($row + ['event_id' => $tid]);
            $scope = 'event';
        } else {
            $db->table('creative_items')->insert($row + ['tema_id' => $tid]);
            $scope = 'standalone';
        }
        $id = (int) $db->insertID();
        ActivityLog::write('create', $scope === 'event' ? 'creative' : 'creative_standalone', (string) $id, 'Design', ['aksi' => 'design_otomatis', 'tema' => $kind . $tid]);
        return ['scope' => $scope, 'id' => $id, 'nama' => 'Design'];
    }

    /** nama jenis katalog (huruf kecil) -> id, untuk mengisi jenis_id turunan otomatis. */
    private static function idKatalog(): array
    {
        $out = [];
        foreach (db_connect()->table('creative_turunan_jenis')->select('id,nama')->where('induk', 'design')->get()->getResultArray() as $r) {
            $out[mb_strtolower($r['nama'])] = (int) $r['id'];
        }
        return $out;
    }
}
