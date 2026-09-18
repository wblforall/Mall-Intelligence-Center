<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\ImageCompressor;
use App\Models\PestFindingModel;
use App\Models\PestFindingPhotoModel;
use App\Models\PestItemModel;
use App\Models\PestVisitModel;

/**
 * Pest Control — pencatatan temuan pest.
 *
 * Satuan simpan adalah SATU KUNJUNGAN pada SATU TANGGAL; nomor minggu tidak
 * pernah disimpan, melainkan dihitung saat menampilkan. Lihat
 * PESTCARE_DESIGN.md §2 — itulah yang membuat perbedaan bulan 4 dan 5 minggu
 * tidak pernah perlu diputuskan di tingkat data.
 */
class PestCtrl extends BaseController
{
    private const MENU = 'pest_control';

    /** Maksimal foto per temuan — sama dengan work report. */
    private const MAX_FOTO = 5;

    /** Batas ukuran satu foto (MB). Lebih longgar dari work report (5) karena
     *  foto di modul ini datang langsung dari kamera ponsel di lapangan;
     *  ImageCompressor mengecilkannya setelah tersimpan. */
    private const MAX_MB = 10;

    private PestVisitModel $visits;
    private PestFindingModel $findings;
    private PestFindingPhotoModel $photos;
    private PestItemModel $items;

    public function __construct()
    {
        $this->visits   = new PestVisitModel();
        $this->findings = new PestFindingModel();
        $this->photos   = new PestFindingPhotoModel();
        $this->items    = new PestItemModel();
    }

    private function uploadDir(int $visitId): string
    {
        return FCPATH . 'uploads/pest/' . $visitId . '/';
    }

    /** Respons JSON gagal + hash CSRF baru, supaya form di halaman tidak basi. */
    private function gagal(string $pesan, int $kode = 400)
    {
        return $this->response->setStatusCode($kode)
            ->setJSON(['ok' => false, 'msg' => $pesan, 'csrf' => csrf_hash()]);
    }

    // ── Tren mingguan ────────────────────────────────────────────────────

    public function index()
    {
        if (! $this->canViewMenu(self::MENU)) {
            // Seperti traffic: pemegang edit-tanpa-lihat langsung ke form input.
            if ($this->canEditMenu(self::MENU)) {
                return redirect()->to('/pest/input/ewalk/' . date('Y-m-d'));
            }
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $mall   = $this->request->getGet('mall');
        $mall   = isset(PestVisitModel::MALLS[$mall]) ? $mall : null;
        $minggu = max(4, min(52, (int) ($this->request->getGet('minggu') ?: 12)));

        // Rentang: $minggu minggu ISO terakhir, berakhir di Minggu minggu ini.
        $akhir = date('Y-m-d', strtotime('sunday this week'));
        $awal  = date('Y-m-d', strtotime($akhir . ' -' . ($minggu * 7 - 1) . ' days'));

        $items    = $this->items->aktif();
        $perMinggu = $this->visits->mingguanPerItem($awal, $akhir, $mall);
        $jmlKunjungan = $this->visits->mingguanJumlahKunjungan($awal, $akhir, $mall);

        // Susun daftar minggu ISO berurutan beserta rentang tanggalnya.
        $kolom = [];
        $cursor = strtotime('monday this week', strtotime($awal));
        $batas  = strtotime($akhir);
        while ($cursor <= $batas) {
            $senin  = date('Y-m-d', $cursor);
            $minggu7 = date('Y-m-d', strtotime($senin . ' +6 days'));
            $kolom[] = [
                'yw'      => date('oW', $cursor),
                'label'   => 'W' . date('W', $cursor),
                'senin'   => $senin,
                'minggu'  => $minggu7,
                'rentang' => $this->rentangSingkat($senin, $minggu7),
            ];
            $cursor = strtotime('+7 days', $cursor);
        }

        return view('pest/index', [
            'user'         => $this->currentUser(),
            'canEdit'      => $this->canEditMenu(self::MENU),
            'mall'         => $mall,
            'minggu'       => $minggu,
            'items'        => $items,
            'kolom'        => $kolom,
            'perMinggu'    => $perMinggu,
            'jmlKunjungan' => $jmlKunjungan,
        ]);
    }

    /** "14–20 Sep" atau "28 Sep–4 Okt" bila menyeberang bulan. */
    private function rentangSingkat(string $a, string $b): string
    {
        $ba = (int) date('n', strtotime($a));
        $bb = (int) date('n', strtotime($b));
        $na = substr(bulan_indo($ba), 0, 3);
        $nb = substr(bulan_indo($bb), 0, 3);
        return $ba === $bb
            ? date('j', strtotime($a)) . '–' . date('j', strtotime($b)) . ' ' . $nb
            : date('j', strtotime($a)) . ' ' . $na . '–' . date('j', strtotime($b)) . ' ' . $nb;
    }

    // ── Daftar kunjungan ─────────────────────────────────────────────────

    public function kunjungan()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $mall = $this->request->getGet('mall');
        $mall = isset(PestVisitModel::MALLS[$mall]) ? $mall : null;

        // 'semua' menampilkan juga baris hasil impor Excel bulanan.
        $sumber = $this->request->getGet('sumber') === 'semua' ? null : 'kunjungan';

        $hal   = max(1, (int) ($this->request->getGet('hal') ?: 1));
        $per   = 50;
        $total = $this->visits->hitungDaftar($mall, $sumber);

        return view('pest/kunjungan', [
            'user'    => $this->currentUser(),
            'canEdit' => $this->canEditMenu(self::MENU),
            'mall'    => $mall,
            'sumber'  => $sumber,
            'rows'    => $this->visits->daftar($mall, $sumber, $per, ($hal - 1) * $per),
            'hal'     => $hal,
            'per'     => $per,
            'total'   => $total,
        ]);
    }

    // ── Form input satu kunjungan ────────────────────────────────────────

    public function form(string $mall = 'ewalk', string $tanggal = '')
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/pest')->with('error', 'Akses ditolak.');
        }
        if (! isset(PestVisitModel::MALLS[$mall])) {
            return redirect()->to('/pest')->with('error', 'Mall tidak valid.');
        }

        $tanggal = $this->tanggalSah($tanggal);
        if ($tanggal === null) {
            return redirect()->to('/pest/input/' . $mall . '/' . date('Y-m-d'))
                ->with('error', 'Tanggal tidak valid.');
        }

        $visit = $this->visits->getByMallTanggal($mall, $tanggal);

        // Baris hasil impor tidak boleh disunting lewat form kunjungan — ia
        // rekap sebulan, bukan satu kunjungan.
        if ($visit && $visit['sumber'] === 'rekap_legacy') {
            return redirect()->to('/pest/kunjungan')
                ->with('error', 'Tanggal ini berisi rekap bulanan hasil impor, bukan kunjungan — tidak dapat disunting di sini.');
        }

        $temuan = $visit ? $this->findings->byVisit((int) $visit['id']) : [];

        return view('pest/form', [
            'user'    => $this->currentUser(),
            'mall'    => $mall,
            'tanggal' => $tanggal,
            'items'   => $this->items->aktif(),
            'visit'   => $visit,
            'temuan'  => $temuan,
            'maxFoto' => self::MAX_FOTO,
        ]);
    }

    /** Kembalikan tanggal Y-m-d yang sah, atau null. Kosong = hari ini. */
    private function tanggalSah(string $tanggal): ?string
    {
        if ($tanggal === '') return date('Y-m-d');
        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (! $d || $d->format('Y-m-d') !== $tanggal) return null;
        // Tanggal di masa depan tidak masuk akal untuk temuan yang sudah terjadi.
        if ($tanggal > date('Y-m-d')) return null;
        return $tanggal;
    }

    // ── Simpan satu baris temuan ─────────────────────────────────────────

    public function saveCell()
    {
        if (! $this->canEditMenu(self::MENU)) return $this->gagal('Akses ditolak.', 403);

        $post    = $this->request->getPost();
        $mall    = (string) ($post['mall'] ?? '');
        $tanggal = (string) ($post['tanggal'] ?? '');
        $itemId  = (int) ($post['item_id'] ?? 0);
        $jumlah  = (int) ($post['jumlah'] ?? -1);

        if (! isset(PestVisitModel::MALLS[$mall]))      return $this->gagal('Mall tidak valid.');
        if ($this->tanggalSah($tanggal) !== $tanggal)   return $this->gagal('Tanggal tidak valid.');
        if ($jumlah < 0)                                return $this->gagal('Jumlah tidak valid.');

        $item = $this->items->find($itemId);
        if (! $item || ! $item['aktif']) return $this->gagal('Item tidak valid.');

        $visit = $this->visits->getByMallTanggal($mall, $tanggal);
        if ($visit && $visit['sumber'] === 'rekap_legacy') {
            return $this->gagal('Rekap bulanan hasil impor tidak dapat disunting.', 403);
        }

        $userId = (int) $this->currentUser()['id'];

        // Kunjungan dibuat hanya kalau memang ada yang dicatat — mengisi 0 pada
        // tanggal kosong tidak boleh melahirkan kunjungan hantu.
        if (! $visit && $jumlah <= 0) {
            return $this->response->setJSON([
                'ok' => true, 'item_id' => $itemId, 'jumlah' => 0,
                'total' => 0, 'jml_foto' => 0, 'finding_id' => null,
                'csrf' => csrf_hash(),
            ]);
        }

        $visitId = $visit ? (int) $visit['id'] : $this->visits->pastikanAda($mall, $tanggal, $userId);

        $sebelumHapus = [];
        if ($jumlah <= 0) {
            $lama = $this->findings->getCell($visitId, $itemId);
            if ($lama) $sebelumHapus = $this->photos->fileNamesByFinding((int) $lama['id']);
        }

        $res = $this->findings->upsertCell($visitId, $itemId, $jumlah);
        $this->visits->sentuh($visitId);

        // Berkas fisik dihapus SETELAH baris DB hilang — kebalikan dari
        // urutan saat mengunggah, dan keduanya benar: yang tak bisa
        // dibatalkan selalu dikerjakan belakangan.
        if ($res['action'] === 'delete' && $sebelumHapus) {
            $dir = $this->uploadDir($visitId);
            foreach ($sebelumHapus as $f) @unlink($dir . $f);
        }

        if ($res['action'] !== 'none') {
            ActivityLog::captureBefore([$item['nama'] => $res['before'] ?? 0]);
            ActivityLog::captureAfter([$item['nama'] => $jumlah]);
            ActivityLog::write('update', 'pest', "{$mall}/{$tanggal}",
                'Pest ' . PestVisitModel::MALLS[$mall] . " — {$tanggal}", [
                    'mall' => $mall, 'tanggal' => $tanggal,
                    'item' => $item['nama'], 'nilai' => $jumlah, 'aksi' => $res['action'],
                ]);
        }

        $findingId = $res['id'] ?? null;
        if ($jumlah <= 0) $findingId = null;

        return $this->response->setJSON([
            'ok'         => true,
            'item_id'    => $itemId,
            'jumlah'     => $jumlah,
            'finding_id' => $findingId,
            'jml_foto'   => $findingId ? $this->photos->hitung((int) $findingId) : 0,
            'total'      => $this->findings->totalByVisit($visitId),
            'visit_id'   => $visitId,
            'csrf'       => csrf_hash(),
        ]);
    }

    // ── Nihil temuan ─────────────────────────────────────────────────────

    /**
     * Kunjungan tanpa satu pun temuan. Karena nol tidak pernah disimpan,
     * keberadaan baris kunjungan inilah yang membuktikan pemeriksaan benar
     * dilakukan — tanpa tombol ini "diperiksa, bersih" tak bisa dibedakan dari
     * "belum diinput", persis lubang yang ada di Excel lama.
     */
    public function nihil()
    {
        if (! $this->canEditMenu(self::MENU)) return $this->gagal('Akses ditolak.', 403);

        $mall    = (string) ($this->request->getPost('mall') ?? '');
        $tanggal = (string) ($this->request->getPost('tanggal') ?? '');

        if (! isset(PestVisitModel::MALLS[$mall]))    return $this->gagal('Mall tidak valid.');
        if ($this->tanggalSah($tanggal) !== $tanggal) return $this->gagal('Tanggal tidak valid.');

        $visit = $this->visits->getByMallTanggal($mall, $tanggal);
        if ($visit && $visit['sumber'] === 'rekap_legacy') {
            return $this->gagal('Rekap bulanan hasil impor tidak dapat disunting.', 403);
        }
        if ($visit && $this->findings->totalByVisit((int) $visit['id']) > 0) {
            return $this->gagal('Kunjungan ini sudah punya temuan. Kosongkan dulu jumlahnya.');
        }

        $userId  = (int) $this->currentUser()['id'];
        $visitId = $visit ? (int) $visit['id'] : $this->visits->pastikanAda($mall, $tanggal, $userId);

        ActivityLog::write('create', 'pest', "{$mall}/{$tanggal}",
            'Pest ' . PestVisitModel::MALLS[$mall] . " — {$tanggal}",
            ['mall' => $mall, 'tanggal' => $tanggal, 'aksi' => 'nihil temuan']);

        return $this->response->setJSON([
            'ok' => true, 'visit_id' => $visitId, 'csrf' => csrf_hash(),
        ]);
    }

    // ── Foto bukti ───────────────────────────────────────────────────────

    public function unggahFoto()
    {
        if (! $this->canEditMenu(self::MENU)) return $this->gagal('Akses ditolak.', 403);

        $findingId = (int) ($this->request->getPost('finding_id') ?? 0);
        $finding   = $this->findings->find($findingId);
        if (! $finding) return $this->gagal('Temuan tidak ditemukan — isi jumlahnya lebih dulu.');

        $visit = $this->visits->find((int) $finding['visit_id']);
        if (! $visit || $visit['sumber'] === 'rekap_legacy') {
            return $this->gagal('Rekap bulanan hasil impor tidak menerima foto.', 403);
        }

        $files = array_filter(
            $this->request->getFileMultiple('foto') ?? [],
            fn($f) => $f && $f->getError() !== UPLOAD_ERR_NO_FILE
        );
        if (! $files) return $this->gagal('Tidak ada berkas yang dikirim.');

        $sudah = $this->photos->hitung($findingId);
        if ($sudah + count($files) > self::MAX_FOTO) {
            return $this->gagal('Maksimal ' . self::MAX_FOTO . ' foto per temuan (sekarang ' . $sudah . ').');
        }

        // Semua divalidasi DULU — menolak di tengah jalan menyisakan sebagian
        // terunggah dan sebagian tidak.
        foreach ($files as $f) {
            if ($err = $this->validateUpload($f, self::MIME_IMAGE, self::MAX_MB)) {
                return $this->gagal('Foto: ' . $err);
            }
        }

        // Berkas disimpan ke disk SEBELUM ada tulisan DB: bila mkdir/move gagal
        // setelah baris dibuat, yang tersisa adalah temuan tanpa foto + error,
        // dan itu memancing kiriman ulang.
        $dir = $this->uploadDir((int) $finding['visit_id']);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return $this->gagal('Folder upload tidak dapat dibuat — hubungi admin (uploads/pest).', 500);
        }

        $tersimpan = [];
        try {
            foreach ($files as $f) {
                $nama = 'pest_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($f);
                $f->move($dir, $nama);
                $nama = ImageCompressor::normalizeUpload($dir, $nama);
                $tersimpan[$nama] = $f->getClientName();
            }
        } catch (\Throwable $e) {
            foreach (array_keys($tersimpan) as $n) @unlink($dir . $n);
            log_message('error', 'pest unggahFoto: ' . $e->getMessage());
            return $this->gagal('Gagal menyimpan foto — tidak ada yang tersimpan, silakan ulangi.', 500);
        }

        $baris = [];
        foreach ($tersimpan as $nama => $asli) {
            $baris[] = [
                'finding_id'    => $findingId,
                'file_name'     => $nama,
                'original_name' => mb_substr($asli, 0, 255),
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }
        $this->photos->insertBatch($baris);
        $this->visits->sentuh((int) $finding['visit_id']);

        ActivityLog::write('update', 'pest', $visit['mall'] . '/' . $visit['tanggal'],
            'Pest ' . PestVisitModel::MALLS[$visit['mall']] . ' — ' . $visit['tanggal'],
            ['aksi' => 'unggah foto', 'jumlah' => count($baris)]);

        return $this->response->setJSON([
            'ok'       => true,
            'jml_foto' => $this->photos->hitung($findingId),
            'foto'     => $this->fotoUntukJson($findingId, (int) $finding['visit_id']),
            'csrf'     => csrf_hash(),
        ]);
    }

    public function daftarFoto(int $findingId)
    {
        if (! $this->canViewMenu(self::MENU) && ! $this->canEditMenu(self::MENU)) {
            return $this->gagal('Akses ditolak.', 403);
        }
        $finding = $this->findings->find($findingId);
        if (! $finding) return $this->gagal('Temuan tidak ditemukan.', 404);

        return $this->response->setJSON([
            'ok'   => true,
            'foto' => $this->fotoUntukJson($findingId, (int) $finding['visit_id']),
            'csrf' => csrf_hash(),
        ]);
    }

    private function fotoUntukJson(int $findingId, int $visitId): array
    {
        return array_map(fn($p) => [
            'id'  => (int) $p['id'],
            'url' => base_url('uploads/pest/' . $visitId . '/' . $p['file_name']),
            'nama' => $p['original_name'],
        ], $this->photos->byFinding($findingId));
    }

    public function hapusFoto(int $id)
    {
        if (! $this->canEditMenu(self::MENU)) return $this->gagal('Akses ditolak.', 403);

        $foto = $this->photos->find($id);
        if (! $foto) return $this->gagal('Foto tidak ditemukan.', 404);

        $finding = $this->findings->find((int) $foto['finding_id']);
        if (! $finding) return $this->gagal('Temuan tidak ditemukan.', 404);

        $path = $this->uploadDir((int) $finding['visit_id']) . $foto['file_name'];

        // Baris dulu, berkas belakangan — bila hapus baris gagal, berkasnya
        // masih ada dan tidak ada yang hilang permanen.
        $this->photos->delete($id);
        @unlink($path);

        ActivityLog::write('delete', 'pest', null, 'Pest — hapus foto bukti',
            ['finding_id' => (int) $foto['finding_id'], 'file' => $foto['file_name']]);

        return $this->response->setJSON([
            'ok'       => true,
            'jml_foto' => $this->photos->hitung((int) $foto['finding_id']),
            'csrf'     => csrf_hash(),
        ]);
    }

    // ── Hapus kunjungan ──────────────────────────────────────────────────

    public function hapusKunjungan(int $id)
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/pest')->with('error', 'Akses ditolak.');
        }
        $visit = $this->visits->find($id);
        if (! $visit) return redirect()->to('/pest/kunjungan')->with('error', 'Kunjungan tidak ditemukan.');

        // Nama berkas dikumpulkan SEBELUM baris hilang — sesudah CASCADE
        // jalan, tidak ada lagi cara tahu berkas mana milik siapa.
        $files = $this->photos->fileNamesByVisit($id);
        $dir   = $this->uploadDir($id);

        $db = \Config\Database::connect();
        $db->transStart();
        $this->visits->delete($id);   // findings & photos ikut lewat FK CASCADE
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/pest/kunjungan')
                ->with('error', 'Gagal menghapus kunjungan — tidak ada yang berubah.');
        }

        // Berkas fisik hanya dihapus setelah commit berhasil.
        foreach ($files as $f) @unlink($dir . $f);
        @rmdir($dir);

        ActivityLog::write('delete', 'pest', $visit['mall'] . '/' . $visit['tanggal'],
            'Pest ' . PestVisitModel::MALLS[$visit['mall']] . ' — ' . $visit['tanggal'],
            ['mall' => $visit['mall'], 'tanggal' => $visit['tanggal'], 'foto_dihapus' => count($files)]);

        return redirect()->to('/pest/kunjungan')->with('success', 'Kunjungan dihapus.');
    }

    // ── Rekap bulanan & pembanding antar tahun ───────────────────────────

    public function summary()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $tahunAda = $this->visits->tahunTersedia();
        $tahun    = (int) ($this->request->getGet('tahun') ?: (date('Y')));
        if ($tahunAda && ! in_array($tahun, $tahunAda, true)) $tahun = $tahunAda[0];

        $mall = $this->request->getGet('mall');
        $mall = isset(PestVisitModel::MALLS[$mall]) ? $mall : null;

        $items = $this->items->aktif();

        // Rekap bulanan SEKALIGUS memuat rekap_legacy: di sini satuannya bulan,
        // dan data impor memang bulanan — sah, tidak ada yang dikarang.
        $bulanan = $this->visits->bulananPerItem($tahun . '-01', $tahun . '-12', $mall);

        return view('pest/summary', [
            'user'      => $this->currentUser(),
            'canEdit'   => $this->canEditMenu(self::MENU),
            'tahun'     => $tahun,
            'tahunAda'  => $tahunAda,
            'mall'      => $mall,
            'items'     => $items,
            'bulanan'   => $bulanan,
            'iniTahun'  => $this->visits->tahunanPerItem($tahun, $mall),
            'laluTahun' => $this->visits->tahunanPerItem($tahun - 1, $mall),
        ]);
    }

    // ── Laporan bulanan siap cetak ───────────────────────────────────────

    public function laporanBulanan()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $bulan = (string) ($this->request->getGet('bulan') ?: date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $mall = $this->request->getGet('mall');
        $mall = isset(PestVisitModel::MALLS[$mall]) ? $mall : null;

        $awal     = $bulan . '-01';
        $akhir    = date('Y-m-t', strtotime($awal));
        $prevBulan = date('Y-m', strtotime($awal . ' -1 month'));

        $items = $this->items->aktif();

        // Total bulan: SELALU tepat, karena satu kunjungan tidak pernah
        // membelah bulan. Termasuk rekap_legacy.
        $bulanIni  = $this->visits->bulananPerItem($bulan, $bulan, $mall)[$bulan] ?? [];
        $bulanLalu = $this->visits->bulananPerItem($prevBulan, $prevBulan, $mall)[$prevBulan] ?? [];

        // Rincian mingguan: DIPOTONG di batas bulan supaya jumlah barisnya
        // sama persis dengan total bulan (§2.2). Berbeda dari halaman tren
        // yang sengaja memakai minggu ISO penuh — dan bedanya disengaja.
        $harian = $this->visits->harianPerItem($awal, $akhir, $mall);
        $mingguan = [];
        foreach ($harian as $tgl => $perItem) {
            $senin = date('Y-m-d', strtotime('monday this week', strtotime($tgl)));
            $kunci = date('oW', strtotime($tgl));
            if (! isset($mingguan[$kunci])) {
                $mingguan[$kunci] = [
                    'label'    => 'W' . date('W', strtotime($tgl)),
                    'dari'     => max($senin, $awal),
                    'sampai'   => min(date('Y-m-d', strtotime($senin . ' +6 days')), $akhir),
                    'sebagian' => $senin < $awal || date('Y-m-d', strtotime($senin . ' +6 days')) > $akhir,
                    'items'    => [],
                    'total'    => 0,
                ];
            }
            foreach ($perItem as $itemId => $n) {
                $mingguan[$kunci]['items'][$itemId] = ($mingguan[$kunci]['items'][$itemId] ?? 0) + $n;
                $mingguan[$kunci]['total'] += $n;
            }
        }
        ksort($mingguan);

        return view('pest/laporan_bulanan', [
            'bulan'       => $bulan,
            'prevBulan'   => $prevBulan,
            'mall'        => $mall,
            'items'       => $items,
            'bulanIni'    => $bulanIni,
            'bulanLalu'   => $bulanLalu,
            'mingguan'    => $mingguan,
            'jmlKunjungan'=> $this->visits->hitungKunjungan($bulan, $mall),
            'jmlLegacy'   => $this->visits->hitungLegacy($bulan, $mall),
            'signatories' => \App\Libraries\ReportSignatories::resolve(self::MENU),
            'printedBy'   => $this->currentUser()['name'],
            'printedAt'   => date('d/m/Y H:i'),
        ]);
    }
}
