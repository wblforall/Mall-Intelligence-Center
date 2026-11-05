<?php

namespace App\Libraries;

use App\Models\BudgetDeptSettingModel;
use App\Models\BudgetPosModel;

/**
 * Realisasi Creative yang menautkan PO FlowStore (keputusan pemilik 5 Nov 2026).
 *
 * Alur: di form realisasi Creative (event / non-event) CCD memilih baris BTB dari antrean FlowStore CCD yang belum diproses,
 * per baris memilih pos (bawaan: pilihan di tab FlowStore atau rekomendasi). Saat disimpan, dalam SATU transaksi:
 *  1. entri realisasi Creative dibuat (bukti tetap diunggah di sana); nilainya = jumlah nilai BTB, tanggal = tanggal terima terakhir;
 *  2. tiap baris BTB di-"Catat" ke Budget Departemen CCD lewat BudgetFsCatat (jalur yang sama dengan tab FlowStore),
 *     ditandai creative_realisasi_id = entri tadi.
 * Menghapus entri melepas baris BTB-nya: realisasi budget dihapus, baris kembali ke antrean "Belum diproses".
 */
class CreativeFsPo
{
    const MAKS_BARIS = 60;

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect();
    }

    public static function deptCcd(): ?array
    {
        $id = CreativeBiayaFs::deptCcdId();
        if (! $id) return null;
        return db_connect()->table('departments')->select('id, name')->where('id', $id)->get()->getRowArray() ?: null;
    }

    /**
     * Antrean BTB CCD yang bisa ditautkan + pilihan pos/mal.
     * @return array{ok:bool,pesan?:string,baris?:list<array>,pos?:list<array>,mal?:array,pisah?:bool}
     */
    public function antrean(string $cari = ''): array
    {
        $dept = self::deptCcd();
        if (! $dept) return ['ok' => false, 'pesan' => 'Departemen Creative Concept & Design tidak ditemukan.'];
        $deptId = (int) $dept['id'];
        $setting = new BudgetDeptSettingModel();
        $tahunMulai = $setting->tahunMulai($deptId, \App\Controllers\BudgetDept::TAHUN_MULAI);
        $rows = $this->barisAntrean($deptId, $cari, $tahunMulai . '-01-01');

        $rek = new BudgetFsRekomendasi($this->db);
        $posAktif = (new BudgetPosModel())->milikDept($deptId, true);
        $idAktif = array_map('intval', array_column($posAktif, 'id'));
        $out = [];
        foreach ($rows as $f) {
            $pos = (int) ($f['pem_pos'] ?? 0);
            if (! in_array($pos, $idAktif, true)) { $r = $rek->untuk($deptId, $f); $pos = (int) ($r['pos_id'] ?? 0); }
            if (! in_array($pos, $idAktif, true)) $pos = 0;
            $terima = (string) $f['tanggal_diterima'];
            $out[] = self::bentukBaris($f) + [
                'pos' => $pos,
                'mall' => (string) ($f['pem_mall'] ?: BudgetFsRekomendasi::malDariSkema($f['id_skema'] !== null ? (int) $f['id_skema'] : null, $f['skema'])),
                'bulan' => (string) ($f['bulan_beban'] && substr($f['bulan_beban'], 0, 4) === substr($terima, 0, 4) ? $f['bulan_beban'] : substr($terima, 0, 7)),
            ];
        }
        return [
            'ok' => true, 'baris' => $out,
            'pos' => array_map(fn ($p) => ['id' => (int) $p['id'], 'nama' => (string) $p['nama']], $posAktif),
            'pisah' => $setting->pisahMal($deptId), 'mal' => BudgetRekap::MAL_REALISASI,
        ];
    }

    /**
     * Baris antrean 'baru' satu dept yang bisa ditautkan (BTB tidak batal, nilai > 0, diterima sejak tanggal mulai penarikan).
     * Dipakai juga oleh EventFsPo (Content Event & Dekorasi/VM).
     */
    public function barisAntrean(int $deptId, string $cari, ?string $palingAwal = null): array
    {
        $mulai = (new BudgetFsSinkron(null, $this->db))->mulai($deptId);
        if ($palingAwal !== null && $palingAwal > $mulai) $mulai = $palingAwal;
        $where = "p.dept_id = ? AND p.status = 'baru' AND f.status_btb <> 'batal' AND f.nilai_diterima > 0 AND f.tanggal_diterima >= ?";
        $arg = [$deptId, $mulai];
        $cari = trim($cari);
        if ($cari !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cari) . '%';
            $where .= ' AND (f.no_po LIKE ? OR f.no_btb LIKE ? OR f.nama_barang LIKE ? OR f.nama_supplier LIKE ? OR f.keperluan LIKE ?)';
            array_push($arg, $like, $like, $like, $like, $like);
        }
        return $this->db->query("SELECT f.*, p.pos_id AS pem_pos, p.bulan_beban, p.mall AS pem_mall
                FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item
               WHERE $where ORDER BY f.tanggal_diterima DESC, f.no_po, f.id_btb_item LIMIT 300", $arg)->getResultArray();
    }

    /** Bentuk baris antrean untuk pemilih di layar (tanpa pos/mal). */
    public static function bentukBaris(array $f): array
    {
        return [
            'id' => (int) $f['id_btb_item'], 'no_po' => (string) $f['no_po'], 'no_btb' => (string) $f['no_btb'],
            'tanggal' => (string) $f['tanggal_diterima'], 'barang' => (string) $f['nama_barang'], 'supplier' => (string) $f['nama_supplier'],
            'qty' => rtrim(rtrim(number_format((float) $f['jumlah_diterima'], 2, ',', '.'), '0'), ','), 'satuan' => (string) $f['satuan'],
            'nilai' => (int) round((float) $f['nilai_diterima']),
            'keperluan' => mb_strimwidth(preg_replace('/\s+/u', ' ', (string) $f['keperluan']), 0, 160, '…'),
        ];
    }

    /**
     * Baca & periksa kiriman form (JSON [{id,pos,mall}]) sebelum entri dibuat.
     * @return array{0:?string,1:list<array>,2:int,3:?string} [pesan galat, baris siap catat, total nilai, tanggal terima terakhir]
     */
    public function siapkan(?string $json): array
    {
        $in = json_decode((string) $json, true);
        if (! is_array($in) || ! $in) return [null, [], 0, null];   // tanpa PO: realisasi manual biasa
        $dept = self::deptCcd();
        if (! $dept) return ['Departemen Creative Concept & Design tidak ditemukan.', [], 0, null];
        return $this->siapkanUntuk($dept, $in, 'CCD');
    }

    /** Periksa pilihan [{id,pos,mall}] terhadap antrean 'baru' dept ini. Bentuk hasil sama dengan siapkan(). */
    public function siapkanUntuk(array $dept, array $in, string $namaDept): array
    {
        $in = array_slice(array_values(array_filter($in, 'is_array')), 0, self::MAKS_BARIS);
        $ids = array_values(array_unique(array_filter(array_map(fn ($x) => (int) ($x['id'] ?? 0), $in))));
        if (! $ids) return ['Baris PO yang dipilih tidak dikenal.', [], 0, null];

        $rows = array_column($this->db->table('budget_fs_pemetaan p')->select('p.status, f.*')
            ->join('fs_btb_item f', 'f.id_btb_item = p.id_btb_item')
            ->where('p.dept_id', (int) $dept['id'])->whereIn('p.id_btb_item', $ids)->get()->getResultArray(), null, 'id_btb_item');
        $siap = []; $total = 0; $akhir = null;
        foreach ($in as $x) {
            $id = (int) ($x['id'] ?? 0);
            if (! isset($rows[$id])) return ['BTB #' . $id . ' tidak ada di antrean FlowStore ' . $namaDept . '.', [], 0, null];
            $f = $rows[$id];
            if ($f['status'] !== 'baru') return ['BTB ' . $f['no_btb'] . ' (' . $f['nama_barang'] . ') sudah diproses — muat ulang halaman.', [], 0, null];
            $terima = (string) ($f['tanggal_diterima'] ?: $f['tanggal_btb']);
            $siap[] = ['id' => $id, 'pos' => (int) ($x['pos'] ?? 0), 'mall' => (string) ($x['mall'] ?? ''), 'bulan' => substr($terima, 0, 7)];
            $total += (int) round((float) $f['nilai_diterima']);
            if ($akhir === null || $terima > $akhir) $akhir = $terima;
        }
        return [null, $siap, $total, $akhir];
    }

    /**
     * Catat baris yang sudah disiapkan sebagai milik entri realisasi Creative. Dipanggil DI DALAM transaksi pemanggil.
     * @return ?string pesan galat (null = semua tercatat)
     */
    public function catat(array $siap, string $scope, int $itemId, int $entryId, int $uid): ?string
    {
        $dept = self::deptCcd();
        if (! $dept) return 'Departemen Creative Concept & Design tidak ditemukan.';
        $catat = new BudgetFsCatat($this->db);
        foreach ($siap as $x) {
            [$ok, $pesan] = $catat->satu($dept, (int) substr($x['bulan'], 0, 4), $x, $uid, [$scope, $itemId], $entryId);
            if (! $ok) return $pesan;
        }
        return null;
    }

    /** Lepas baris BTB milik entri-entri ini: realisasi budget dihapus, baris kembali ke antrean. @return int jumlah baris */
    public function lepasEntri(string $scope, array $entryIds, ?int $uid = null): int
    {
        $entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds))));
        if (! $entryIds) return 0;
        try {
            $rows = $this->db->table('budget_fs_pemetaan')->select('realisasi_id')->where('creative_scope', $scope)
                ->whereIn('creative_realisasi_id', $entryIds)->where('realisasi_id IS NOT NULL')->get()->getResultArray();
        } catch (\Throwable $e) {
            return 0;   // migrasi belum jalan: belum ada entri ber-PO
        }
        $sink = new BudgetFsSinkron(null, $this->db);
        foreach ($rows as $r) {
            $rid = (int) $r['realisasi_id'];
            $lama = $this->db->table('budget_realisasi')->where('id', $rid)->get()->getRowArray();
            $this->db->table('budget_realisasi')->where('id', $rid)->delete();
            $sink->lepasRealisasi($rid, $uid);
            if ($lama) {
                ActivityLog::captureBefore($lama);
                ActivityLog::write('delete', 'budget_realisasi', (string) $rid, $lama['uraian'], ['sumber' => 'realisasi_creative_dihapus']);
            }
        }
        return count($rows);
    }
}
