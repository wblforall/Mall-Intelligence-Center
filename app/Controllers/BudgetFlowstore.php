<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\BudgetFsRekomendasi;
use App\Libraries\BudgetFsSinkron;
use App\Libraries\BudgetRekap;
use App\Models\BudgetDeptSettingModel;
use App\Models\BudgetPosModel;
use App\Models\BudgetRealisasiModel;

/**
 * Penautan FlowStore — tab "FlowStore" di Budget Departemen.
 *
 * Keputusan pemilik 17 Okt 2026: pembelian dianggap realisasi SAAT BARANG DITERIMA (BTB);
 * pemetaan ke pos & bulan TIDAK otomatis (user memilih per item, sistem hanya merekomendasikan);
 * mode USULAN — tak ada angka masuk budget_realisasi tanpa user menekan "Catat".
 * Hak: lihat = boleh melihat dept (seperti Realisasi); tulis = admin atau dept sendiri;
 * pengaturan pemetaan divisi & kata kunci = admin.
 */
class BudgetFlowstore extends BudgetDept
{
    const STATUS = [
        'baru'           => 'Belum diproses',
        'dicatat'        => 'Sudah dicatat',
        'diabaikan'      => 'Diabaikan',
        'perlu_ditinjau' => 'Perlu ditinjau',
    ];
    const PER_HALAMAN = 100;
    const PER_HALAMAN_KELOMPOK = 300;   // tampilan dikelompokkan: halaman lebih besar supaya satu kelompok jarang terpotong

    /** Dept dibuka & boleh melihat tab ini? (punya pemetaan divisi, atau admin). */
    private function deptFs(): ?array
    {
        if (! $this->canViewMenu(self::MENU)) return null;
        $dept = $this->dept();
        if (! $dept) return null;
        if (! $this->isAdmin() && ! self::adaPemetaanDivisi((int) $dept['id'])) return null;
        return $dept;
    }

    public static function adaPemetaanDivisi(int $deptId): bool
    {
        return db_connect()->table('budget_fs_divisi_map')->where('dept_id', $deptId)->countAllResults() > 0;
    }

    private function deptFsUbah(): ?array
    {
        $dept = $this->deptFs();
        return $dept && $this->bolehUbah($dept) ? $dept : null;
    }

    private function kembali(array $dept, array $q = [])
    {
        $k = $this->request->getPost('kembali');
        if (is_string($k) && str_starts_with($k, base_url('budget-dept/flowstore'))) return redirect()->to($k);
        return redirect()->to($this->url('flowstore', $dept, $q));
    }

    /** Mal bawaan dari skema FlowStore (1 Pentacity, 2 Ewalk) — selain itu keduanya. */
    public static function malDariSkema(?int $idSkema, ?string $skema): string
    {
        $s = strtolower((string) $skema);
        if ($idSkema === 2 || str_contains($s, 'ewalk') || str_contains($s, 'e-walk')) return 'ewalk';
        if ($idSkema === 1 || str_contains($s, 'penta')) return 'pentacity';
        return 'both';
    }

    // ── Halaman ──────────────────────────────────────────────────────

    public function index()
    {
        $dept = $this->deptFs();
        if (! $dept) return redirect()->to(base_url('budget-dept'))->with('error', 'Tab FlowStore belum tersedia untuk departemen ini.');
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);
        $db     = db_connect();

        $status = (string) $this->request->getGet('status');
        if (! isset(self::STATUS[$status]) && $status !== 'semua') $status = 'baru';
        $bln   = (int) $this->request->getGet('bln'); if ($bln < 0 || $bln > 12) $bln = 0;
        $posF  = (int) $this->request->getGet('pos') ?: null;
        $sup   = trim((string) $this->request->getGet('sup'));
        $cari  = trim((string) $this->request->getGet('q'));
        $kep   = trim((string) $this->request->getGet('kep'));          // saring: satu keperluan persis
        $kel   = (string) $this->request->getGet('kel') === '1';        // tampilan dikelompokkan menurut keperluan
        $hal   = max(1, (int) $this->request->getGet('halaman'));

        // Cakupan: diterima di tahun halaman DAN sejak tanggal mulai penarikan (sebelum itu sudah diisi dari Excel — keputusan
        // pemilik 17 Okt 2026; aturan "carry-over sejak 1 Desember tahun lalu" dihapus).
        $sink = new BudgetFsSinkron();
        $mulai = $sink->mulai($deptId);
        $awal = max("$tahun-01-01", $mulai); $akhir = "$tahun-12-31";
        $sebelumMulai = $akhir < $mulai;   // tahun halaman seluruhnya sebelum tanggal mulai
        $lingkup = 'p.dept_id = ? AND f.tanggal_diterima BETWEEN ? AND ?';
        $lingkupArg = [$deptId, $awal, $akhir];

        // KPI (seluruh tahun, tak terpengaruh saringan lain)
        $kpi = array_fill_keys(array_keys(self::STATUS), ['n' => 0, 'nilai' => 0.0]);
        foreach ($db->query("SELECT p.status, COUNT(*) n, COALESCE(SUM(f.nilai_diterima),0) nilai FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item WHERE $lingkup GROUP BY p.status", $lingkupArg)->getResultArray() as $r) {
            $kpi[$r['status']] = ['n' => (int) $r['n'], 'nilai' => (float) $r['nilai']];
        }
        $bulanKpi = $bln ?: ((int) date('Y') === $tahun ? (int) date('n') : 0);
        $nilaiBulan = (float) ($db->query("SELECT COALESCE(SUM(f.nilai_diterima),0) v FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item
            WHERE p.dept_id = ? AND p.status <> 'diabaikan' AND f.tanggal_diterima BETWEEN ? AND ?" . ($bulanKpi ? ' AND MONTH(f.tanggal_diterima) = ' . $bulanKpi : ''),
            [$deptId, $awal, $akhir])->getRowArray()['v'] ?? 0);

        // Daftar
        $where = $lingkup; $arg = $lingkupArg;
        if ($status !== 'semua') { $where .= ' AND p.status = ?'; $arg[] = $status; }
        if ($bln)  { $where .= ' AND MONTH(f.tanggal_diterima) = ? AND YEAR(f.tanggal_diterima) = ?'; array_push($arg, $bln, $tahun); }
        if ($posF) { $where .= ' AND p.pos_id = ?'; $arg[] = $posF; }
        if ($sup !== '') { $where .= ' AND f.nama_supplier = ?'; $arg[] = $sup; }
        if ($cari !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cari) . '%';
            $where .= ' AND (f.nama_barang LIKE ? OR f.kode_barang LIKE ? OR f.no_btb LIKE ? OR f.no_po LIKE ? OR f.nama_supplier LIKE ?'
                . ' OR f.keperluan LIKE ? OR f.spesifikasi LIKE ? OR f.no_mr LIKE ? OR f.no_pr LIKE ?)';
            array_push($arg, $like, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        // Chip "Keperluan": kelompok keperluan yang sama (dihitung SEBELUM saringan kep, supaya chip lain tetap terlihat)
        $kelompok = $db->query("SELECT f.keperluan AS kep, COUNT(*) n, COALESCE(SUM(f.nilai_diterima),0) nilai, COUNT(DISTINCT f.no_mr) n_mr, MIN(f.no_mr) mr
                FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item
               WHERE $where AND f.keperluan IS NOT NULL AND f.keperluan <> '' GROUP BY f.keperluan HAVING COUNT(*) >= 2
               ORDER BY n DESC, nilai DESC LIMIT 40", $arg)->getResultArray();
        if ($kep !== '') { $where .= ' AND f.keperluan = ?'; $arg[] = $kep; }
        $total = (int) $db->query("SELECT COUNT(*) n FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item WHERE $where", $arg)->getRowArray()['n'];
        $perHal = $kel ? self::PER_HALAMAN_KELOMPOK : self::PER_HALAMAN;
        $halMaks = max(1, (int) ceil($total / $perHal));
        $hal = min($hal, $halMaks);
        $rows = $db->query("SELECT f.*, p.id AS pem_id, p.status AS pem_status, p.pos_id AS pem_pos, p.bulan_beban, p.mall AS pem_mall, p.realisasi_id,
                    p.alasan_abaikan, p.catatan_tinjau, x.tanggal AS real_tanggal
               FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item
               LEFT JOIN budget_realisasi x ON x.id = p.realisasi_id
              WHERE $where ORDER BY " . ($kel ? "COALESCE(f.keperluan,'') , f.no_mr, " : '') . "f.tanggal_diterima DESC, f.id_btb_item DESC LIMIT " . $perHal . ' OFFSET ' . (($hal - 1) * $perHal), $arg)->getResultArray();

        $pos = (new BudgetPosModel())->milikDept($deptId);
        $posNama = array_column($pos, 'nama', 'id');
        $rek = new BudgetFsRekomendasi($db);
        $pisahMal = (new BudgetDeptSettingModel())->pisahMal($deptId);
        foreach ($rows as &$r) {
            $r['mal_bawaan'] = self::malDariSkema($r['id_skema'] !== null ? (int) $r['id_skema'] : null, $r['skema']);
            $r['rek'] = $r['pem_status'] === 'baru' ? $rek->untuk($deptId, $r) : null;
            $r['bln_bawaan'] = substr((string) $r['tanggal_diterima'], 0, 4) === (string) $tahun ? substr((string) $r['tanggal_diterima'], 0, 7) : $tahun . '-01';
        }
        unset($r);

        $suppliers = array_column($db->query("SELECT DISTINCT f.nama_supplier s FROM budget_fs_pemetaan p JOIN fs_btb_item f ON f.id_btb_item = p.id_btb_item
            WHERE $lingkup AND f.nama_supplier IS NOT NULL AND f.nama_supplier <> '' ORDER BY s LIMIT 300", $lingkupArg)->getResultArray(), 's');

        $admin = $this->isAdmin();
        $extra = [];
        if ($admin) {
            $extra = [
                'divisiFs'   => $db->table('fs_divisi')->orderBy('divisi')->get()->getResultArray(),
                'petaSemua'  => array_column($db->table('budget_fs_divisi_map m')->select('m.id_divisi_fs, m.dept_id, d.name AS dept_nama')->join('departments d', 'd.id = m.dept_id')->get()->getResultArray(), null, 'id_divisi_fs'),
                'kataKunci'  => $db->table('budget_fs_aturan')->where('dept_id', $deptId)->where('jenis', 'kata_kunci')->orderBy('sasaran', 'ASC')->orderBy('urutan')->orderBy('id')->get()->getResultArray(),
            ];
        }

        return view('budget_dept/flowstore', $this->konteks($dept, $tahun, 'flowstore') + $extra + [
            'status' => $status, 'bln' => $bln, 'posF' => $posF, 'sup' => $sup, 'cari' => $cari, 'kep' => $kep, 'kel' => $kel, 'kelompok' => $kelompok, 'hal' => $hal, 'halMaks' => $halMaks, 'total' => $total,
            'kpi' => $kpi, 'nilaiBulan' => $nilaiBulan, 'bulanKpi' => $bulanKpi,
            'rows' => $rows, 'pos' => $pos, 'posNama' => $posNama, 'suppliers' => $suppliers, 'pisahMal' => $pisahMal,
            'sinkron' => $sink->terakhir($deptId), 'mulai' => $mulai, 'mulaiKhusus' => $sink->mulaiKhusus($deptId), 'sebelumMulai' => $sebelumMulai, 'sumber' => $sink->feed()->mode(), 'siap' => $sink->feed()->siap(),
            'adaCermin' => (bool) $db->table('fs_btb_item')->countAllResults(),
            'divisiDept' => array_keys($sink->petaDivisi($deptId)),
            'url' => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    // ── Sinkron ──────────────────────────────────────────────────────

    public function sinkron()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->tolak();
        $tahun = $this->tahun($dept);
        $sink   = new BudgetFsSinkron();
        $dari   = max($tahun . '-01-01', $sink->mulai((int) $dept['id']));   // tak pernah lebih awal dari tanggal mulai penarikan
        $sampai = min(date('Y-m-d'), $tahun . '-12-31');
        $uid = (int) session()->get('user_id');
        $back = $this->kembali($dept);

        if ($dari > $sampai) return $back->with('warning', $tahun . '-12-31' < $dari ? 'Tahun ' . $tahun . ' tidak ditarik dari FlowStore: penarikan mulai ' . $dari . ' (sebelum itu dicatat manual/Excel).' : 'Tahun ' . $tahun . ' belum berjalan atau belum sampai tanggal mulai penarikan (' . $dari . ') — belum ada barang diterima untuk disinkronkan.');
        $h = $sink->jalankan($dari, $sampai, (int) $dept['id'], $uid);
        ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Sinkron FlowStore ' . $dept['name'], [
            'ok' => $h['ok'], 'jumlah' => $h['jumlah'], 'baru' => $h['baru'], 'diperbarui' => $h['diperbarui'], 'batal' => $h['batal'], 'sumber' => $h['sumber']]);
        if (! $h['ok']) {
            return $back->with('error', 'Sinkron dari FlowStore gagal: ' . esc($h['galat']) . ' Data terakhir yang tersimpan tetap ditampilkan.');
        }
        return $back->with('success', 'Sinkron selesai: ' . $h['jumlah'] . ' item dibaca, ' . $h['baru'] . ' baru, ' . $h['diperbarui'] . ' diperbarui'
            . ($h['batal'] ? ', ' . $h['batal'] . ' tidak lagi berlaku (dibatalkan/hilang)' : '') . '.');
    }

    // ── Draf (simpan otomatis pilihan pos/bulan/mal) ─────────────────

    public function draf()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->json(403, ['ok' => false, 'pesan' => 'Akses ditolak.']);
        $tahun = $this->tahun($dept);
        $id = (int) $this->request->getPost('id');
        $db = db_connect();
        $p = $db->table('budget_fs_pemetaan')->where('id_btb_item', $id)->where('dept_id', (int) $dept['id'])->get()->getRowArray();
        if (! $p) return $this->json(404, ['ok' => false, 'pesan' => 'Item tidak ditemukan.']);
        if ($p['status'] !== 'baru') return $this->json(409, ['ok' => false, 'pesan' => 'Item sudah diproses.']);

        $upd = ['diperbarui_oleh' => (int) session()->get('user_id'), 'updated_at' => date('Y-m-d H:i:s')];
        // Hanya kolom yang dikirim yang diubah (klien mengirim yang disentuh user), supaya rekomendasi yang belum
        // disentuh tidak "terkunci" sebagai pilihan user.
        if ($this->request->getPost('pos') !== null) {
            $posId = (int) $this->request->getPost('pos');
            if ($posId) {
                if (! (new BudgetPosModel())->milik($posId, (int) $dept['id'])) return $this->json(422, ['ok' => false, 'pesan' => 'Pos bukan milik departemen ini.']);
                $upd['pos_id'] = $posId;
            } else $upd['pos_id'] = null;
        }
        $bulan = (string) $this->request->getPost('bulan');
        if ($bulan !== '') {
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) || (int) substr($bulan, 0, 4) !== $tahun) return $this->json(422, ['ok' => false, 'pesan' => 'Bulan harus di tahun ' . $tahun . '.']);
            $upd['bulan_beban'] = $bulan;
        }
        $mal = (string) $this->request->getPost('mall');
        if ($mal !== '') {
            if (! isset(BudgetRekap::MAL_REALISASI[$mal])) return $this->json(422, ['ok' => false, 'pesan' => 'Mal tidak dikenal.']);
            $upd['mall'] = $mal;
        }
        $db->table('budget_fs_pemetaan')->where('id', $p['id'])->where('status', 'baru')->update($upd);
        return $this->json(200, ['ok' => true]);
    }

    private function json(int $kode, array $isi)
    {
        return $this->response->setStatusCode($kode)->setJSON($isi + ['csrf' => csrf_hash()]);
    }

    // ── Catat ────────────────────────────────────────────────────────

    public function catat()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->tolak();
        $tahun = $this->tahun($dept);
        $back = $this->kembali($dept);
        $in = json_decode((string) $this->request->getPost('data'), true);
        if (! is_array($in) || ! $in) return $back->with('error', 'Tidak ada item yang dikirim.');
        $in = array_slice($in, 0, 200);

        $ok = 0; $lewat = [];
        foreach ($in as $x) {
            if (! is_array($x)) continue;
            [$berhasil, $pesan] = $this->catatSatu($dept, $tahun, $x);
            if ($berhasil) $ok++; else $lewat[] = $pesan;
        }
        $kembali = $back;
        if ($lewat) {
            $ringkas = implode(' ', array_slice(array_unique($lewat), 0, 4)) . (count($lewat) > 4 ? ' …' : '');
            $kembali = $kembali->with('warning', ($ok ? $ok . ' item dicatat. ' : '') . count($lewat) . ' item dilewati: ' . esc($ringkas));
        } else {
            $kembali = $kembali->with('success', $ok . ' item dicatat ke realisasi ' . $dept['name'] . ' ' . $tahun . '.');
        }
        return $kembali;
    }

    /**
     * Isi realisasi.keterangan (kolom TEXT): "Untuk: <keperluan> | Supplier: <nama> | MR: <no_mr>" — bagian yang kosong dilewati.
     * Keperluan dipotong rapi (…) pada 600 karakter agar keterangan tetap terbaca di tab Realisasi.
     */
    public static function keteranganCatat(array $f): ?string
    {
        $bagian = [];
        $kep = trim((string) ($f['keperluan'] ?? ''));
        if ($kep !== '') $bagian[] = 'Untuk: ' . mb_strimwidth(preg_replace('/\s+/u', ' ', $kep), 0, 600, '…');
        $sup = trim((string) ($f['nama_supplier'] ?? ''));
        if ($sup !== '') $bagian[] = 'Supplier: ' . $sup;
        $mr = trim((string) ($f['no_mr'] ?? ''));
        if ($mr !== '') $bagian[] = 'MR: ' . $mr;
        return $bagian ? implode(' | ', $bagian) : null;
    }

    /** @return array{0:bool,1:string} */
    private function catatSatu(array $dept, int $tahun, array $x): array
    {
        $deptId = (int) $dept['id'];
        $id = (int) ($x['id'] ?? 0);
        $uid = (int) session()->get('user_id');
        $db = db_connect();
        $pisah = (new BudgetDeptSettingModel())->pisahMal($deptId);

        $posId = (int) ($x['pos'] ?? 0);
        $pos = $posId ? (new BudgetPosModel())->milik($posId, $deptId) : null;
        if (! $pos) return [false, '#' . $id . ': pilih pos dulu.'];
        if (empty($pos['aktif'])) return [false, '#' . $id . ': pos "' . $pos['nama'] . '" nonaktif.'];
        $bulan = (string) ($x['bulan'] ?? '');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) return [false, '#' . $id . ': bulan pembebanan tidak sah.'];
        if ((int) substr($bulan, 0, 4) !== $tahun) return [false, '#' . $id . ': bulan pembebanan harus di tahun budget ' . $tahun . '.'];
        if ($tahun < $this->tahunMulai($dept)) return [false, '#' . $id . ': tahun ' . $tahun . ' belum dibuka untuk dept ini.'];
        $mal = (string) ($x['mall'] ?? '');
        if ($pisah && ! isset(BudgetRekap::MAL_REALISASI[$mal])) return [false, '#' . $id . ': mal tidak sah.'];
        if (! $pisah) $mal = '';

        $db->transStart();
        // Kunci baris pemetaan: klik ganda / POST ulang menunggu di sini lalu mendapati status sudah 'dicatat'.
        $p = $db->query('SELECT * FROM budget_fs_pemetaan WHERE id_btb_item = ? AND dept_id = ? FOR UPDATE', [$id, $deptId])->getRowArray();
        $f = $p ? $db->table('fs_btb_item')->where('id_btb_item', $id)->get()->getRowArray() : null;
        $galat = null;
        if (! $p || ! $f) $galat = '#' . $id . ': tidak ada di antrean departemen ini.';
        elseif ($p['status'] !== 'baru') $galat = '#' . $id . ': sudah ' . strtolower(self::STATUS[$p['status']]) . ', tidak dicatat ulang.';
        elseif ($f['status_btb'] === 'batal') $galat = '#' . $id . ': BTB dibatalkan di FlowStore.';
        elseif ((float) $f['nilai_diterima'] <= 0) $galat = '#' . $id . ' ' . $f['nama_barang'] . ': nilai Rp 0 — gunakan Abaikan.';

        if ($galat === null) {
            $terima = (string) ($f['tanggal_diterima'] ?: $f['tanggal_btb']);
            $bulanTerima = substr($terima, 0, 7);
            $tanggal = $bulan === $bulanTerima ? $terima : date('Y-m-t', strtotime($bulan . '-01'));
            $qty = rtrim(rtrim(number_format((float) $f['jumlah_diterima'], 2, ',', ''), '0'), ',');
            $akhiran = ' ×' . $qty . ($f['satuan'] ? ' ' . $f['satuan'] : '') . ' — BTB ' . $f['no_btb'] . ' / PO ' . $f['no_po'];
            $uraian = mb_substr($f['nama_barang'], 0, max(20, 255 - mb_strlen($akhiran))) . $akhiran;
            $data = [
                'dept_id' => $deptId, 'pos_id' => (int) $pos['id'], 'tanggal' => $tanggal,
                'periode_asal' => $bulanTerima !== $bulan ? $bulanTerima : null,
                'uraian' => mb_substr($uraian, 0, 255), 'keterangan' => self::keteranganCatat($f),
                'nilai' => $f['nilai_diterima'], 'mall' => $mal,
                'referensi' => mb_substr('FlowStore BTB ' . $f['no_btb'] . ' #' . $id, 0, 100),
                'created_by' => $uid, 'updated_by' => $uid,
            ];
            $rek = (new BudgetFsRekomendasi($db))->untuk($deptId, $f);
            $rid = (new BudgetRealisasiModel())->insert($data);
            $db->table('budget_fs_pemetaan')->where('id', $p['id'])->update([
                'status' => 'dicatat', 'pos_id' => (int) $pos['id'], 'bulan_beban' => $bulan, 'mall' => $mal !== '' ? $mal : self::malDariSkema($f['id_skema'] !== null ? (int) $f['id_skema'] : null, $f['skema']),
                'realisasi_id' => $rid, 'catatan_tinjau' => null, 'alasan_abaikan' => null, 'diperbarui_oleh' => $uid, 'dibuat_oleh' => $p['dibuat_oleh'] ?: $uid,
                'rekomendasi_pos_id' => $rek['pos_id'], 'rekomendasi_sumber' => $rek['sumber'], 'rekomendasi_skor' => $rek['skor'],
                'rekomendasi_alasan' => mb_substr($rek['alasan'], 0, 255), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            (new BudgetFsRekomendasi($db))->ingat($deptId, $f, (int) $pos['id']);
        }
        $db->transComplete();
        if ($galat === null && ! $db->transStatus()) $galat = '#' . $id . ': gagal menyimpan.';
        if ($galat !== null) return [false, $galat];

        ActivityLog::captureAfter($data);
        ActivityLog::write('create', 'budget_realisasi', (string) $rid, $data['uraian'], ['sumber' => 'flowstore', 'id_btb_item' => $id]);
        return [true, ''];
    }

    // ── Abaikan / pulihkan / tinjau ──────────────────────────────────

    public function abaikan()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->tolak();
        $back = $this->kembali($dept);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('ids')))));
        $alasan = trim(mb_substr((string) $this->request->getPost('alasan'), 0, 255));
        if (! $ids) return $back->with('error', 'Tidak ada item yang dipilih.');
        if ($alasan === '') return $back->with('error', 'Alasan mengabaikan wajib diisi (mis. bukan beban departemen ini).');
        $db = db_connect();
        $db->table('budget_fs_pemetaan')->where('dept_id', (int) $dept['id'])->where('status', 'baru')->whereIn('id_btb_item', array_slice($ids, 0, 300))
            ->update(['status' => 'diabaikan', 'alasan_abaikan' => $alasan, 'diperbarui_oleh' => (int) session()->get('user_id'), 'updated_at' => date('Y-m-d H:i:s')]);
        $n = $db->affectedRows();
        ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Abaikan ' . $n . ' item FlowStore', ['alasan' => $alasan, 'ids' => array_slice($ids, 0, 50)]);
        return $back->with('success', $n . ' item diabaikan' . ($n < count($ids) ? ' (sisanya sudah diproses)' : '') . '. Bisa dibatalkan di filter "Diabaikan".');
    }

    public function pulihkan()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->tolak();
        $back = $this->kembali($dept);
        $id = (int) $this->request->getPost('id');
        $db = db_connect();
        $db->table('budget_fs_pemetaan')->where('dept_id', (int) $dept['id'])->where('status', 'diabaikan')->where('id_btb_item', $id)
            ->update(['status' => 'baru', 'alasan_abaikan' => null, 'diperbarui_oleh' => (int) session()->get('user_id'), 'updated_at' => date('Y-m-d H:i:s')]);
        $n = $db->affectedRows();
        if ($n) ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Batalkan abaikan item FlowStore #' . $id);
        return $back->with($n ? 'success' : 'error', $n ? 'Item dikembalikan ke antrean "Belum diproses".' : 'Item tidak ditemukan atau bukan item yang diabaikan.');
    }

    /** "Perlu ditinjau" → sudah ditinjau, realisasi dipertahankan apa adanya. */
    public function tinjau()
    {
        $dept = $this->deptFsUbah();
        if (! $dept) return $this->tolak();
        $back = $this->kembali($dept);
        $id = (int) $this->request->getPost('id');
        $db = db_connect();
        $db->table('budget_fs_pemetaan')->where('dept_id', (int) $dept['id'])->where('status', 'perlu_ditinjau')->where('id_btb_item', $id)->where('realisasi_id IS NOT NULL')
            ->update(['status' => 'dicatat', 'catatan_tinjau' => null, 'diperbarui_oleh' => (int) session()->get('user_id'), 'updated_at' => date('Y-m-d H:i:s')]);
        $n = $db->affectedRows();
        if ($n) ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Item FlowStore #' . $id . ' ditandai sudah ditinjau (realisasi dipertahankan)');
        return $back->with($n ? 'success' : 'error', $n ? 'Ditandai sudah ditinjau; realisasi dipertahankan.' : 'Item tidak ditemukan.');
    }

    // ── Pengaturan admin ─────────────────────────────────────────────

    public function simpanDivisi()
    {
        if (! $this->isAdmin()) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $db = db_connect();
        $back = redirect()->to($this->url('flowstore', $dept));
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('divisi')))));
        $sah = array_column($db->table('fs_divisi')->get()->getResultArray(), 'divisi', 'id_divisi');
        $peta = array_column($db->table('budget_fs_divisi_map')->get()->getResultArray(), 'dept_id', 'id_divisi_fs');
        foreach ($ids as $i) {
            if (! isset($sah[$i])) return $back->with('error', 'Divisi FlowStore tidak dikenal.');
            if (isset($peta[$i]) && (int) $peta[$i] !== $deptId) return $back->with('error', 'Divisi "' . esc($sah[$i]) . '" sudah dipetakan ke departemen lain; lepaskan dulu di sana.');
        }
        $lama = array_keys(array_filter($peta, fn ($d) => (int) $d === $deptId));
        $db->transStart();
        $db->table('budget_fs_divisi_map')->where('dept_id', $deptId)->delete();
        foreach ($ids as $i) $db->table('budget_fs_divisi_map')->insert(['dept_id' => $deptId, 'id_divisi_fs' => $i, 'created_at' => date('Y-m-d H:i:s')]);
        $db->transComplete();
        (new BudgetFsSinkron())->selaraskan();
        ActivityLog::captureBefore(['divisi' => $lama]); ActivityLog::captureAfter(['divisi' => $ids]);
        ActivityLog::write('update', 'budget_fs', (string) $deptId, 'Pemetaan divisi FlowStore ' . $dept['name']);
        return $back->with('success', 'Pemetaan divisi disimpan (' . count($ids) . ' divisi). Jalankan sinkron untuk menarik datanya.');
    }

    public function segarkanDivisi()
    {
        if (! $this->isAdmin()) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $back = redirect()->to($this->url('flowstore', $dept));
        try {
            $n = (new BudgetFsSinkron())->segarkanDivisi();
        } catch (\Throwable $e) {
            return $back->with('error', 'Gagal menyegarkan daftar divisi: ' . esc($e->getMessage()));
        }
        return $back->with('success', 'Daftar divisi FlowStore disegarkan (' . $n . ' divisi).');
    }

    /** Admin: tanggal mulai penarikan — untuk semua dept (dept_id 0) atau khusus dept yang dibuka; 'reset' menghapus penimpaan dept. */
    public function simpanMulai()
    {
        if (! $this->isAdmin()) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $back = redirect()->to($this->url('flowstore', $dept));
        $sink = new BudgetFsSinkron();
        $uid = (int) session()->get('user_id');
        $lingkup = $this->request->getPost('lingkup') === 'semua' ? 0 : (int) $dept['id'];
        if ($this->request->getPost('reset') !== null) {
            if ($lingkup === 0) return $back->with('error', 'Pilih lingkup "departemen ini" untuk menghapus penimpaan.');
            $sink->simpanMulai($lingkup, null, $uid);
            ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Tanggal mulai penarikan FlowStore ' . $dept['name'] . ' kembali mengikuti bawaan');
            return $back->with('success', 'Penimpaan dihapus; ' . $dept['name'] . ' kini mulai ' . $sink->mulai((int) $dept['id']) . '.');
        }
        $tgl = trim((string) $this->request->getPost('mulai'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) || date('Y-m-d', strtotime($tgl) ?: 0) !== $tgl || $tgl < '2020-01-01' || $tgl > '2100-12-31') {
            return $back->with('error', 'Tanggal mulai tidak sah.');
        }
        $lama = $sink->mulai((int) $dept['id']);
        $sink->simpanMulai($lingkup, $tgl, $uid);
        $dibuang = $sink->bersihkan();
        ActivityLog::captureBefore(['mulai' => $lama]); ActivityLog::captureAfter(['mulai' => $tgl, 'lingkup' => $lingkup ?: 'semua']);
        ActivityLog::write('update', 'budget_fs', (string) $dept['id'], 'Tanggal mulai penarikan FlowStore ' . ($lingkup ? $dept['name'] : 'semua dept') . ': ' . $tgl);
        return $back->with('success', 'Penarikan FlowStore mulai ' . $tgl . ($lingkup ? ' untuk ' . esc($dept['name']) : ' untuk semua departemen') . '.'
            . ($dibuang ? ' ' . $dibuang . ' item lama yang belum diproses dibuang dari antrean.' : '') . ($tgl < $lama ? ' Jalankan sinkron untuk menarik data yang baru masuk cakupan.' : ''));
    }

    public function tambahKata()
    {
        if (! $this->isAdmin()) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $back = redirect()->to($this->url('flowstore', $dept));
        $pola = trim((string) $this->request->getPost('pola'));
        if ($pola === '' || mb_strlen($pola) > 191) return $back->with('error', 'Kata kunci wajib diisi (maks. 191 karakter).');
        if (strlen($pola) > 2 && $pola[0] === '/' && substr($pola, -1) === '/' && @preg_match($pola . 'iu', '') === false) return $back->with('error', 'Pola regex tidak sah.');
        $posId = (int) $this->request->getPost('pos');
        $posNama = '';
        if ($posId) {
            $pos = (new BudgetPosModel())->milik($posId, $deptId);
            if (! $pos) return $back->with('error', 'Pos bukan milik departemen ini.');
            $posNama = $pos['nama'];
        }
        $db = db_connect();
        $sasaran = $this->request->getPost('sasaran') === 'keperluan' ? 'keperluan' : 'nama';
        $maks = (int) ($db->table('budget_fs_aturan')->selectMax('urutan')->where('dept_id', $deptId)->where('jenis', 'kata_kunci')->where('sasaran', $sasaran)->get()->getRowArray()['urutan'] ?? 0);
        $db->table('budget_fs_aturan')->insert(['dept_id' => $deptId, 'jenis' => 'kata_kunci', 'sasaran' => $sasaran, 'kunci' => $pola, 'pos_id' => null, 'pos_nama' => $posNama,
            'urutan' => $maks + 10, 'dipakai_berapa_kali' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('create', 'budget_fs', (string) $deptId, 'Kata kunci FlowStore (' . $sasaran . ') ' . $dept['name'] . ': ' . $pola . ' → ' . ($posNama ?: '(tanpa pos)'));
        return $back->with('success', 'Kata kunci ditambahkan.');
    }

    public function hapusKata(int $id)
    {
        if (! $this->isAdmin()) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $db = db_connect();
        $db->table('budget_fs_aturan')->where('id', $id)->where('dept_id', (int) $dept['id'])->where('jenis', 'kata_kunci')->delete();
        ActivityLog::write('delete', 'budget_fs', (string) $dept['id'], 'Hapus kata kunci FlowStore #' . $id);
        return redirect()->to($this->url('flowstore', $dept))->with('success', 'Kata kunci dihapus.');
    }
}
