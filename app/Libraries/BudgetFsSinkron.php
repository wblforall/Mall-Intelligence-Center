<?php

namespace App\Libraries;

/**
 * Sinkron cermin FlowStore (fs_btb_item) dan antrean pemetaan (budget_fs_pemetaan).
 *
 * Keterangan order (keperluan, spesifikasi, no_mr, keterangan_pr/po/btb) hanya memperbarui cermin — perubahannya
 * TIDAK PERNAH membuat item 'perlu_ditinjau' (itu hanya untuk nilai berubah / BTB batal / hilang).
 *
 * Aman diulang (idempoten) dan aman dari cron. TIDAK PERNAH mengubah budget_realisasi:
 *  - item baru, BTB final            → pemetaan 'baru' (antrean) dengan rekomendasi;
 *  - BTB kemudian batal/draft        → 'baru'/'diabaikan': keluar dari antrean; 'dicatat': 'perlu_ditinjau';
 *  - nilai berubah setelah dicatat   → 'perlu_ditinjau' (realisasi dibiarkan apa adanya).
 */
class BudgetFsSinkron
{
    private $db;
    private FlowstoreFeed $feed;
    private BudgetFsRekomendasi $rek;

    public function __construct(?FlowstoreFeed $feed = null, $db = null)
    {
        $this->db   = $db ?: db_connect();
        $this->feed = $feed ?: new FlowstoreFeed();
        $this->rek  = new BudgetFsRekomendasi($this->db);
    }

    public function feed(): FlowstoreFeed { return $this->feed; }

    // ── Tanggal mulai penarikan ──────────────────────────────────────

    const MULAI_BAWAAN = '2026-10-01';
    private ?array $mulaiCache = null;

    private static function tglSah(?string $d): bool
    {
        return is_string($d) && (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false && date('Y-m-d', strtotime($d)) === $d;
    }

    /**
     * Tanggal mulai penarikan (tanggal_diterima >= ini). Urutan: baris khusus dept di budget_fs_setelan, baris semua dept
     * (dept_id 0), .env flowstore.mulai, MULAI_BAWAAN. Barang diterima sebelum tanggal ini sudah diisi dari Excel.
     */
    public function mulai(?int $deptId = null): string
    {
        if ($this->mulaiCache === null) {
            $this->mulaiCache = [];
            try {
                $this->mulaiCache = array_column($this->db->table('budget_fs_setelan')->get()->getResultArray(), 'mulai', 'dept_id');
            } catch (\Throwable $e) { /* tabel belum ada: pakai bawaan */ }
        }
        if ($deptId && isset($this->mulaiCache[$deptId])) return (string) $this->mulaiCache[$deptId];
        if (isset($this->mulaiCache[0])) return (string) $this->mulaiCache[0];
        $env = trim((string) env('flowstore.mulai', ''));
        return self::tglSah($env) ? $env : self::MULAI_BAWAAN;
    }

    /** True bila dept ini punya tanggal mulai sendiri (bukan ikut semua dept). */
    public function mulaiKhusus(int $deptId): bool
    {
        $this->mulai($deptId);
        return isset($this->mulaiCache[$deptId]);
    }

    /** Simpan tanggal mulai: $deptId 0 = semua dept; null $tanggal pada dept tertentu = hapus penimpaan. */
    public function simpanMulai(int $deptId, ?string $tanggal, ?int $oleh): void
    {
        $t = $this->db->table('budget_fs_setelan');
        if ($tanggal === null) { $t->where('dept_id', $deptId)->delete(); }
        else {
            $this->db->query('INSERT INTO budget_fs_setelan (dept_id, mulai, updated_by, updated_at) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE mulai = VALUES(mulai), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
                [$deptId, $tanggal, $oleh, date('Y-m-d H:i:s')]);
        }
        $this->mulaiCache = null;
    }

    /**
     * Buang dari cermin & antrean item yang diterima SEBELUM tanggal mulai dept-nya dan masih 'baru' (atau tak punya
     * pemetaan) tanpa realisasi. Item 'dicatat' / 'diabaikan' / 'perlu_ditinjau' tidak disentuh.
     * @return int jumlah item cermin yang dihapus
     */
    public function bersihkan(): int
    {
        $peta = $this->petaDivisi();
        $n = 0;
        $this->db->transStart();
        $q = $this->db->query("SELECT f.id, f.id_btb_item, f.id_divisi, f.tanggal_diterima, p.id AS pem_id, p.status, p.realisasi_id
                                 FROM fs_btb_item f LEFT JOIN budget_fs_pemetaan p ON p.id_btb_item = f.id_btb_item")->getResultArray();
        foreach ($q as $r) {
            if (! $r['tanggal_diterima']) continue;
            $dept = (int) ($peta[(int) $r['id_divisi']] ?? 0);
            if ($r['tanggal_diterima'] >= $this->mulai($dept ?: null)) continue;
            if ($r['pem_id'] && ($r['status'] !== 'baru' || $r['realisasi_id'])) continue;
            if ($r['pem_id']) $this->db->table('budget_fs_pemetaan')->where('id', $r['pem_id'])->delete();
            $this->db->table('fs_btb_item')->where('id', $r['id'])->delete();
            $n++;
        }
        $this->db->transComplete();
        return $n;
    }

    /** [id_divisi_fs => dept_id] (opsional hanya satu dept). */
    public function petaDivisi(?int $deptId = null): array
    {
        $q = $this->db->table('budget_fs_divisi_map');
        if ($deptId) $q->where('dept_id', $deptId);
        return array_column($q->get()->getResultArray(), 'dept_id', 'id_divisi_fs');
    }

    /** Catatan sinkron terakhir yang BERHASIL untuk dept (atau semua) dan percobaan terakhir. */
    public function terakhir(?int $deptId = null): array
    {
        $q = fn () => $this->db->table('budget_fs_sinkron');
        $ok = $q();   if ($deptId) $ok->groupStart()->where('dept_id', $deptId)->orWhere('dept_id', null)->groupEnd();
        $ok = $ok->where('ok', 1)->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
        $any = $q();  if ($deptId) $any->groupStart()->where('dept_id', $deptId)->orWhere('dept_id', null)->groupEnd();
        $any = $any->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
        return ['ok' => $ok ?: null, 'terakhir' => $any ?: null];
    }

    /**
     * @return array{ok:bool,galat:?string,jumlah:int,baru:int,diperbarui:int,batal:int,sumber:string}
     */
    public function jalankan(?string $dari = null, ?string $sampai = null, ?int $deptId = null, ?int $oleh = null): array
    {
        $hasil = ['ok' => false, 'galat' => null, 'jumlah' => 0, 'baru' => 0, 'diperbarui' => 0, 'batal' => 0, 'sumber' => $this->feed->mode(), 'dipangkas' => false, 'dihapus' => 0];
        $sampai = $sampai ?: date('Y-m-d');
        $dari   = $dari ?: $this->mulai($deptId);   // bawaan = tanggal mulai penarikan
        $ketat  = fn (string $d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        if (! $ketat($dari) || ! $ketat($sampai) || $dari > $sampai) {
            $hasil['galat'] = 'Rentang tanggal tidak sah.';
            return $hasil;
        }
        if (! $this->feed->siap()) {
            $hasil['galat'] = $this->feed->alasanBelumSiap();
            return $hasil;
        }

        $kunci = $this->db->query("SELECT GET_LOCK('mic_fs_sync', 0) AS k")->getRowArray();
        if ((int) ($kunci['k'] ?? 0) !== 1) {
            $hasil['galat'] = 'Sinkron lain sedang berjalan. Coba lagi sebentar lagi.';
            return $hasil;
        }

        $mulai = date('Y-m-d H:i:s');
        try {
            $peta = $this->petaDivisi($deptId);
            if (! $peta) {
                $hasil['galat'] = 'Belum ada divisi FlowStore yang dipetakan ke departemen ini.';
                return $hasil;
            }
            $hasil['dihapus'] = $this->bersihkan();   // item lama (sebelum tanggal mulai) yang masih 'baru' keluar dari cermin
            foreach ($peta as $idDivisi => $dept) {
                // `dari` selalu dipangkas ke tanggal mulai dept pemilik divisi ini (data sebelum itu sudah diisi dari Excel).
                $dariDiv = max($dari, $this->mulai((int) $dept));
                if ($dariDiv > $sampai) { $hasil['dipangkas'] = true; continue; }
                if ($dariDiv > $dari) $hasil['dipangkas'] = true;
                $items = $this->feed->btbItem($dariDiv, $sampai, (int) $idDivisi, true);   // tarikan PENUH rentang (semua halaman) atau lempar galat
                $this->terapkan($items, $this->petaDivisi(), $hasil);
                $this->tandaiHilang((int) $idDivisi, $dariDiv, $sampai, $items, $this->petaDivisi(), $hasil);   // hanya tercapai bila tarikan sukses penuh
            }
            $hasil['ok'] = true;
        } catch (\Throwable $e) {
            $hasil['galat'] = $e instanceof FlowstoreGalat ? $e->getMessage() : 'Sinkron gagal karena kesalahan tak terduga.';
            if (! $e instanceof FlowstoreGalat) log_message('error', 'mic:fs-sync gagal: ' . get_class($e) . ' ' . $e->getMessage());
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('mic_fs_sync')");
            $this->db->table('budget_fs_sinkron')->insert([
                'mulai' => $mulai, 'selesai' => date('Y-m-d H:i:s'), 'dari' => $dari, 'sampai' => $sampai, 'dept_id' => $deptId,
                'sumber' => $hasil['sumber'], 'ok' => $hasil['ok'] ? 1 : 0, 'jumlah' => $hasil['jumlah'], 'baru' => $hasil['baru'],
                'diperbarui' => $hasil['diperbarui'], 'batal' => $hasil['batal'], 'galat' => $hasil['galat'] ? mb_substr($hasil['galat'], 0, 255) : null, 'oleh' => $oleh,
            ]);
        }
        return $hasil;
    }

    /** Segarkan daftar divisi dari feed → fs_divisi. @return int jumlah divisi */
    public function segarkanDivisi(): int
    {
        $now = date('Y-m-d H:i:s');
        $n = 0;
        foreach ($this->feed->divisi() as $d) {
            $this->db->query(
                'INSERT INTO fs_divisi (id_divisi, divisi, kode, diperbarui_pada) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE divisi = VALUES(divisi), kode = VALUES(kode), diperbarui_pada = VALUES(diperbarui_pada)',
                [$d['id_divisi'], mb_substr($d['divisi'], 0, 100), $d['kode'] !== null ? mb_substr($d['kode'], 0, 20) : null, $now]
            );
            $n++;
        }
        return $n;
    }

    // ── inti ─────────────────────────────────────────────────────────

    /**
     * BTB yang dihapus di FlowStore tidak berstatus 'batal' — barisnya lenyap dari feed. Item cermin pada
     * divisi & rentang tanggal ini yang TIDAK ikut tarikan penuh dianggap hilang: dijadikan status 'batal'
     * di cermin (batal_sejak diisi); pemetaannya mengikuti aturan batal ('baru'/'diabaikan' keluar antrean,
     * 'dicatat' → 'perlu_ditinjau'). Realisasi tidak disentuh.
     */
    private function tandaiHilang(int $idDivisi, string $dari, string $sampai, array $items, array $petaDivisi, array &$hasil): void
    {
        $ada = [];
        foreach ($items as $it) $ada[(int) ($it['id_btb_item'] ?? 0)] = true;
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $q = $this->db->table('fs_btb_item')->where('id_divisi', $idDivisi)->where('status_btb !=', 'batal')
            ->where('tanggal_diterima >=', $dari)->where('tanggal_diterima <=', $sampai)->get()->getResultArray();
        foreach ($q as $m) {
            if (isset($ada[(int) $m['id_btb_item']])) continue;
            $this->db->table('fs_btb_item')->where('id', $m['id'])->update(['status_btb' => 'batal', 'batal_sejak' => $now, 'diperbarui_pada' => $now]);
            $hasil['batal']++;
            $m['status_btb'] = 'batal';
            $this->urusPemetaan($m, $petaDivisi, true);
        }
        $this->db->transComplete();
    }

    /** Cermin + pemetaan untuk satu kumpulan item dari feed. */
    private function terapkan(array $items, array $petaDivisi, array &$hasil): void
    {
        if (! $items) return;
        $now = date('Y-m-d H:i:s');
        $baris = [];
        foreach ($items as $it) {
            $b = self::normalisasi($it);
            if ($b) $baris[$b['id_btb_item']] = $b;
        }
        if (! $baris) return;

        $ada = [];
        foreach (array_chunk(array_keys($baris), 500) as $potong) {
            foreach ($this->db->table('fs_btb_item')->whereIn('id_btb_item', $potong)->get()->getResultArray() as $r) $ada[(int) $r['id_btb_item']] = $r;
        }

        $this->db->transStart();
        foreach ($baris as $id => $b) {
            $hasil['jumlah']++;
            $lama = $ada[$id] ?? null;
            if (! $lama) {
                $b['diambil_pada'] = $now; $b['diperbarui_pada'] = $now;
                $b['batal_sejak'] = $b['status_btb'] === 'batal' ? $now : null;
                $this->db->table('fs_btb_item')->insert($b);
                $hasil['baru']++;
                $baru = $b;
            } else {
                $beda = false;
                foreach ($b as $k => $v) {
                    if ((string) ($lama[$k] ?? '') !== (string) ($v ?? '')) { $beda = true; break; }
                }
                $upd = $b;
                if ($b['status_btb'] === 'batal') $upd['batal_sejak'] = $lama['batal_sejak'] ?: $now; else $upd['batal_sejak'] = null;
                if ($beda || ($lama['batal_sejak'] !== $upd['batal_sejak'])) {
                    $upd['diperbarui_pada'] = $now;
                    $this->db->table('fs_btb_item')->where('id_btb_item', $id)->update($upd);
                    $hasil['diperbarui']++;
                }
                if ($b['status_btb'] === 'batal' && $lama['status_btb'] !== 'batal') $hasil['batal']++;
                $baru = $b + ['id' => $lama['id']] + $lama;   // + $lama: field keterangan order yang tak dikirim feed lama tetap terpakai untuk rekomendasi
            }
            $this->urusPemetaan($baru, $petaDivisi);
        }
        $this->db->transComplete();
    }

    /** Selaraskan satu item cermin dengan antrean pemetaan. $fs = baris fs_btb_item (minimal kolom feed). */
    private function urusPemetaan(array $fs, array $petaDivisi, bool $hilang = false): void
    {
        $id   = (int) $fs['id_btb_item'];
        $dept = (int) ($petaDivisi[(int) ($fs['id_divisi'] ?? 0)] ?? 0);
        $final = ($fs['status_btb'] ?? '') !== 'batal';   // diterima = final ATAU draft (di FlowStore semua BTB 'draft'); hanya 'batal' yang tidak
        $p = $this->db->table('budget_fs_pemetaan')->where('id_btb_item', $id)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        // Diterima sebelum tanggal mulai penarikan (sudah diisi dari Excel): tidak masuk / keluar dari antrean 'baru'.
        $tgl = (string) ($fs['tanggal_diterima'] ?? '');
        if ($dept && $tgl !== '' && $tgl < $this->mulai($dept)) {
            if ($p && $p['status'] === 'baru' && empty($p['realisasi_id'])) $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->delete();
            if (! $p || $p['status'] === 'baru') return;
        }

        if (! $p) {
            if (! $final || ! $dept) return;
            $rek = $this->rek->untuk($dept, $fs);
            $this->db->table('budget_fs_pemetaan')->insert([
                'id_btb_item' => $id, 'dept_id' => $dept, 'status' => 'baru',
                'rekomendasi_pos_id' => $rek['pos_id'], 'rekomendasi_sumber' => $rek['sumber'], 'rekomendasi_skor' => $rek['skor'],
                'rekomendasi_alasan' => mb_substr($rek['alasan'], 0, 255), 'created_at' => $now, 'updated_at' => $now,
            ]);
            return;
        }

        if (! $final) {
            if (in_array($p['status'], ['baru', 'diabaikan'], true)) {
                $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->delete();
            } elseif ($p['status'] === 'dicatat') {
                $this->tandaiTinjau((int) $p['id'], $hilang ? 'BTB ' . ($fs['no_btb'] ?? '') . ' tidak lagi ada di FlowStore — realisasi yang sudah dicatat perlu dicek.'
                    : 'BTB ' . ($fs['no_btb'] ?? '') . ' kini berstatus batal di FlowStore — realisasi yang sudah dicatat perlu dicek.');
            }
            return;
        }

        // final
        if ($p['status'] === 'dicatat' && ! empty($p['realisasi_id'])) {
            $r = $this->db->table('budget_realisasi')->select('nilai')->where('id', $p['realisasi_id'])->get()->getRowArray();
            if ($r && abs((float) $r['nilai'] - (float) $fs['nilai_diterima']) > 0.5) {
                $this->tandaiTinjau((int) $p['id'], 'Nilai di FlowStore kini Rp ' . number_format((float) $fs['nilai_diterima'], 0, ',', '.')
                    . ', realisasi tercatat Rp ' . number_format((float) $r['nilai'], 0, ',', '.') . '.');
            }
            return;
        }
        if (in_array($p['status'], ['baru', 'diabaikan'], true)) {
            $upd = ['updated_at' => $now];
            if (! $dept) { $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->delete(); return; }   // divisinya tak lagi dipetakan
            if ((int) $p['dept_id'] !== $dept) { $upd['dept_id'] = $dept; $upd['pos_id'] = null; $upd['rekomendasi_pos_id'] = null; }
            if ($p['status'] === 'baru') {
                $rek = $this->rek->untuk($dept, $fs);
                $upd += ['rekomendasi_pos_id' => $rek['pos_id'], 'rekomendasi_sumber' => $rek['sumber'], 'rekomendasi_skor' => $rek['skor'],
                         'rekomendasi_alasan' => mb_substr($rek['alasan'], 0, 255)];
            }
            $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->update($upd);
        }
    }

    private function tandaiTinjau(int $pemetaanId, string $catatan): void
    {
        $this->db->table('budget_fs_pemetaan')->where('id', $pemetaanId)->update([
            'status' => 'perlu_ditinjau', 'catatan_tinjau' => mb_substr($catatan, 0, 255), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Setelah pemetaan divisi ↔ dept diubah admin: buat antrean untuk divisi yang baru dipetakan,
     * pindahkan/lepas item 'baru'/'diabaikan' yang divisinya berpindah/tak dipetakan. Tanpa menyentuh feed.
     */
    public function selaraskan(): void
    {
        $peta = $this->petaDivisi();
        $this->db->transStart();
        foreach ($this->db->table('fs_btb_item')->get()->getResultArray() as $fs) $this->urusPemetaan($fs, $peta);
        $this->db->transComplete();
    }

    /**
     * Hook penghapusan realisasi (BudgetDept::hapusRealisasi): bila realisasi itu hasil "Catat" FlowStore,
     * item kembali ke antrean 'baru' (atau keluar dari antrean bila BTB-nya sudah tak berlaku) dan ingatan
     * rekomendasi dikurangi. @return bool true bila realisasi itu memang berasal dari FlowStore
     */
    public function lepasRealisasi(int $realisasiId, ?int $oleh = null): bool
    {
        $p = $this->db->table('budget_fs_pemetaan')->where('realisasi_id', $realisasiId)->get()->getRowArray();
        if (! $p) return false;
        $f = $this->db->table('fs_btb_item')->where('id_btb_item', $p['id_btb_item'])->get()->getRowArray();
        if ($f && $p['pos_id']) $this->rek->lupakan((int) $p['dept_id'], $f, (int) $p['pos_id']);
        $peta = $this->petaDivisi();
        if (! $f || $f['status_btb'] === 'batal' || empty($peta[(int) $f['id_divisi']])) {
            $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->delete();
        } else {
            $this->db->table('budget_fs_pemetaan')->where('id', $p['id'])->update([
                'status' => 'baru', 'realisasi_id' => null, 'catatan_tinjau' => null, 'alasan_abaikan' => null,
                'diperbarui_oleh' => $oleh, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }

    /** Ubah item feed mentah → baris fs_btb_item (null bila tak sah). */
    public static function normalisasi(array $it): ?array
    {
        $id = (int) ($it['id_btb_item'] ?? 0);
        if ($id <= 0 || trim((string) ($it['nama_barang'] ?? '')) === '') return null;
        $tgl = function ($v) {
            $v = is_string($v) ? substr($v, 0, 10) : '';
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : null;
        };
        $tt = fn ($v, int $n) => ($v === null || $v === '') ? null : mb_substr(trim((string) $v), 0, $n);
        $int = fn ($v) => ($v === null || $v === '') ? null : (int) $v;
        // Teks bebas dari FlowStore (keterangan order): disimpan apa adanya (tanpa mengubah isi), hanya dipangkas rapi.
        $teks = fn ($v, int $n) => ($v === null || ! is_scalar($v) || trim((string) $v) === '') ? null : mb_substr(trim((string) $v), 0, $n);
        $dec = fn ($v) => ($v === null || $v === '') ? null : number_format((float) $v, 2, '.', '');
        $status = in_array($it['status_btb'] ?? '', ['final', 'draft', 'batal'], true) ? $it['status_btb'] : 'draft';
        $dibuat = isset($it['dibuat_pada']) ? date('Y-m-d H:i:s', strtotime((string) $it['dibuat_pada']) ?: 0) : null;

        return [
            'id_btb_item' => $id, 'id_btb' => $int($it['id_btb'] ?? null), 'no_btb' => $tt($it['no_btb'] ?? null, 60),
            'tanggal_btb' => $tgl($it['tanggal_btb'] ?? null), 'tanggal_diterima' => $tgl($it['tanggal_diterima'] ?? null) ?: $tgl($it['tanggal_btb'] ?? null),
            'status_btb' => $status, 'id_po' => $int($it['id_po'] ?? null), 'no_po' => $tt($it['no_po'] ?? null, 60), 'tanggal_po' => $tgl($it['tanggal_po'] ?? null),
            'id_supplier' => $int($it['id_supplier'] ?? null), 'nama_supplier' => $tt($it['nama_supplier'] ?? null, 255),
            'id_skema' => $int($it['id_skema'] ?? null), 'skema' => $tt($it['skema'] ?? null, 40),
            'id_pr' => $int($it['id_pr'] ?? null), 'no_pr' => $tt($it['no_pr'] ?? null, 100),
            'id_divisi' => $int($it['id_divisi'] ?? null), 'divisi' => $tt($it['divisi'] ?? null, 100), 'kode_divisi' => $tt($it['kode_divisi'] ?? null, 20),
            'kode_barang' => $tt($it['kode_barang'] ?? null, 60), 'nama_barang' => mb_substr(trim((string) $it['nama_barang']), 0, 255),
            'kategori' => $tt($it['kategori'] ?? null, 100), 'satuan' => $tt($it['satuan'] ?? null, 60),
            'jumlah_diterima' => $dec($it['jumlah_diterima'] ?? 0) ?? '0.00', 'harga_satuan_po' => $dec($it['harga_satuan_po'] ?? null),
            'nilai_diterima' => $dec($it['nilai_diterima'] ?? 0) ?? '0.00', 'nilai_sebelum_ppn' => $dec($it['nilai_sebelum_ppn'] ?? null),
            'ppn_rupiah' => $dec($it['ppn_rupiah'] ?? null), 'sumber_nilai' => $tt($it['sumber_nilai'] ?? null, 60),
            'dibuat_pada' => $dibuat,
        ] + self::keteranganOrder($it, $tt, $teks);
    }

    /**
     * Keterangan order dari feed. Feed LAMA (field tak dikirim sama sekali) → kunci tidak disertakan, jadi cermin yang
     * sudah berisi keperluan tidak terhapus; field ada tetapi kosong → null (memang dikosongkan di FlowStore).
     */
    private static function keteranganOrder(array $it, callable $tt, callable $teks): array
    {
        $out = [];
        $peta = ['keperluan' => [$teks, 500], 'spesifikasi' => [$teks, 500], 'no_mr' => [$tt, 100],
                 'keterangan_pr' => [$teks, 2000], 'keterangan_po' => [$teks, 2000], 'keterangan_btb' => [$teks, 2000]];
        foreach ($peta as $k => [$fn, $n]) if (array_key_exists($k, $it)) $out[$k] = $fn($it[$k], $n);
        return $out;
    }
}
