<?php

namespace App\Libraries;

/**
 * Realisasi Content Event dan Dekorasi & VM yang menautkan PO FlowStore (keputusan pemilik 5 Nov 2026, lanjutan Creative).
 *
 * Alur sama dengan Creative (CreativeFsPo): di form realisasi dept memilih barang BTB dari antrean FlowStore dept-nya
 * sendiri; nilai & tanggal entri mengikuti BTB, bukti tetap diunggah. Bedanya, di sini TIDAK ada budget_realisasi yang
 * ditulis — nilai entri itu sendiri yang terhitung:
 *  - Content Event → Budget Departemen E&P membaca event_content_realisasi langsung (pos & bulan dari item);
 *  - Dekorasi & VM → VM belum memakai Budget Departemen; anggarannya per item VM.
 * Baris BTB cukup ditandai 'dicatat' dengan tautan_modul + tautan_id supaya keluar dari antrean dan tidak dicatat dua kali.
 * Menghapus entri mengembalikan barisnya ke antrean "Belum diproses".
 */
class EventFsPo
{
    /** modul => [nama dept pemilik antrean, tabel entri, label] */
    const MODUL = [
        'content' => ['dept' => 'Event & Promotion', 'tabel' => 'event_content_realisasi', 'label' => 'Content Event'],
        'vm'      => ['dept' => 'Visual Merchandiser', 'tabel' => 'event_vm_realisasi', 'label' => 'Dekorasi & VM'],
    ];

    private $db;
    private static array $dept = [];

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect();
    }

    public static function dept(string $modul): ?array
    {
        if (! isset(self::MODUL[$modul])) return null;
        if (! array_key_exists($modul, self::$dept)) {
            self::$dept[$modul] = db_connect()->table('departments')->select('id, name')->where('name', self::MODUL[$modul]['dept'])->get()->getRowArray() ?: null;
        }
        return self::$dept[$modul];
    }

    /** Antrean BTB dept modul ini (tanpa pilihan pos: pos & bulan mengikuti item realisasinya). */
    public function antrean(string $modul, string $cari = ''): array
    {
        $dept = self::dept($modul);
        if (! $dept) return ['ok' => false, 'pesan' => 'Departemen ' . (self::MODUL[$modul]['dept'] ?? $modul) . ' tidak ditemukan.'];
        $rows = (new CreativeFsPo($this->db))->barisAntrean((int) $dept['id'], $cari);
        return ['ok' => true, 'tanpa_pos' => true, 'pos' => [], 'pisah' => false, 'mal' => [],
                'baris' => array_map(fn ($f) => CreativeFsPo::bentukBaris($f) + ['pos' => 0, 'mall' => ''], $rows)];
    }

    /** @return array{0:?string,1:list<array>,2:int,3:?string} [pesan error, baris siap, total nilai, tanggal terima terakhir] */
    public function siapkan(string $modul, ?string $json): array
    {
        $in = json_decode((string) $json, true);
        if (! is_array($in) || ! $in) return [null, [], 0, null];   // tanpa PO: realisasi manual biasa
        $dept = self::dept($modul);
        if (! $dept) return ['Departemen ' . (self::MODUL[$modul]['dept'] ?? $modul) . ' tidak ditemukan.', [], 0, null];
        return (new CreativeFsPo($this->db))->siapkanUntuk($dept, $in, $dept['name']);
    }

    /**
     * Tandai baris siap sebagai milik entri ini. Dipanggil DI DALAM transaksi pemanggil.
     * $ctx: pos_id (Content: pos item/event, informasi saja), bulan (YYYY-MM, bawaan bulan terima), mall.
     * @return ?string pesan error (null = semua tertaut)
     */
    public function tautkan(string $modul, array $siap, int $entryId, int $uid, array $ctx = []): ?string
    {
        $dept = self::dept($modul);
        if (! $dept) return 'Departemen ' . (self::MODUL[$modul]['dept'] ?? $modul) . ' tidak ditemukan.';
        $now = date('Y-m-d H:i:s');
        foreach ($siap as $x) {
            $f = $this->db->table('fs_btb_item')->where('id_btb_item', (int) $x['id'])->get()->getRowArray();
            if (! $f || $f['status_btb'] === 'batal') return 'BTB #' . (int) $x['id'] . ' dibatalkan di FlowStore — muat ulang halaman.';
            $this->db->table('budget_fs_pemetaan')
                ->where('id_btb_item', (int) $x['id'])->where('dept_id', (int) $dept['id'])->where('status', 'baru')
                ->update([
                    'status' => 'dicatat', 'pos_id' => ! empty($ctx['pos_id']) ? (int) $ctx['pos_id'] : null,
                    'bulan_beban' => $ctx['bulan'] ?? $x['bulan'],
                    'mall' => $ctx['mall'] ?? BudgetFsRekomendasi::malDariSkema($f['id_skema'] !== null ? (int) $f['id_skema'] : null, $f['skema']),
                    'tautan_modul' => $modul, 'tautan_id' => $entryId,
                    'catatan_tinjau' => null, 'alasan_abaikan' => null, 'diperbarui_oleh' => $uid, 'updated_at' => $now,
                ]);
            // Klik ganda / dua tab: baris yang sudah diambil entri lain tidak ikut berubah → batalkan semuanya.
            if ($this->db->affectedRows() !== 1) return 'BTB ' . $f['no_btb'] . ' (' . $f['nama_barang'] . ') sudah diproses — muat ulang halaman.';
        }
        ActivityLog::write('update', 'budget_fs_pemetaan', (string) $entryId, self::MODUL[$modul]['label'] . ' menautkan ' . count($siap) . ' barang PO FlowStore',
            ['modul' => $modul, 'id_btb_item' => array_column($siap, 'id')]);
        return null;
    }

    /** Lepas baris BTB milik entri-entri ini: kembali ke antrean 'baru' (atau keluar bila BTB-nya tak berlaku lagi). @return int jumlah baris */
    public function lepas(string $modul, array $entryIds, ?int $uid = null): int
    {
        $entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds))));
        if (! $entryIds || ! isset(self::MODUL[$modul])) return 0;
        try {
            $rows = $this->db->table('budget_fs_pemetaan p')->select('p.id, f.id_divisi, f.status_btb')
                ->join('fs_btb_item f', 'f.id_btb_item = p.id_btb_item', 'left')
                ->where('p.tautan_modul', $modul)->whereIn('p.tautan_id', $entryIds)->get()->getResultArray();
        } catch (\Throwable $e) {
            return 0;   // migrasi belum jalan: belum ada entri ber-PO
        }
        if (! $rows) return 0;
        $peta = (new BudgetFsSinkron(null, $this->db))->petaDivisi();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            if ($r['status_btb'] === null || $r['status_btb'] === 'batal' || empty($peta[(int) $r['id_divisi']])) {
                $this->db->table('budget_fs_pemetaan')->where('id', $r['id'])->delete();
            } else {
                $this->db->table('budget_fs_pemetaan')->where('id', $r['id'])->update([
                    'status' => 'baru', 'pos_id' => null, 'bulan_beban' => null, 'mall' => null, 'tautan_modul' => null, 'tautan_id' => null,
                    'catatan_tinjau' => null, 'diperbarui_oleh' => $uid, 'updated_at' => $now,
                ]);
            }
        }
        ActivityLog::write('update', 'budget_fs_pemetaan', implode(',', $entryIds), self::MODUL[$modul]['label'] . ' melepas ' . count($rows) . ' barang PO FlowStore',
            ['modul' => $modul]);
        return count($rows);
    }

    /** Baris BTB per entri: [entryId => list baris] — bentuk sama dengan CreativeBiayaFs::poEntri (dipakai _creative/_po_entri). */
    public function poEntri(string $modul, array $entryIds): array
    {
        $entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds))));
        if (! $entryIds) return [];
        try {
            $rows = $this->db->table('budget_fs_pemetaan p')
                ->select('p.tautan_id AS rid, p.status, p.catatan_tinjau, f.nilai_diterima AS nilai, bp.nama AS pos_nama,
                          f.id_btb_item, f.no_btb, f.no_po, f.nama_supplier, f.nama_barang, f.tanggal_diterima, f.jumlah_diterima, f.satuan')
                ->join('fs_btb_item f', 'f.id_btb_item = p.id_btb_item')
                ->join('budget_pos bp', 'bp.id = p.pos_id', 'left')
                ->where('p.tautan_modul', $modul)->whereIn('p.tautan_id', $entryIds)
                ->orderBy('f.no_po')->orderBy('f.id_btb_item')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) $out[(int) $r['rid']][] = $r;
        return $out;
    }

    /** Sisipkan 'po' ke tiap entri pada hasil berbentuk [key => ['entries' => [...]]] (EventVMRealisasiModel). */
    public static function tandaiGrup(string $modul, array $grouped): array
    {
        $ids = [];
        foreach ($grouped as $g) foreach (($g['entries'] ?? []) as $e) $ids[] = (int) $e['id'];
        $po = (new self())->poEntri($modul, $ids);
        if (! $po) return $grouped;
        foreach ($grouped as $k => $g) {
            foreach (($g['entries'] ?? []) as $i => $e) if (isset($po[(int) $e['id']])) $grouped[$k]['entries'][$i]['po'] = $po[(int) $e['id']];
        }
        return $grouped;
    }
}
