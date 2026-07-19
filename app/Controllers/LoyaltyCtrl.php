<?php

namespace App\Controllers;

use App\Models\LoyaltyProgramModel;
use App\Models\LoyaltyRealisasiModel;
use App\Models\LoyaltyHadiahItemModel;
use App\Models\LoyaltyHadiahRealisasiModel;
use App\Models\LoyaltyVoucherItemModel;
use App\Models\LoyaltyVoucherRealisasiModel;
use App\Models\EventLoyaltyModel;
use App\Models\EventLoyaltyRealisasiModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Libraries\ActivityLog;
use App\Models\StockBarangModel;
use App\Models\StockBarangLogModel;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherKodeModel;
use App\Models\StockVoucherLogModel;
use App\Models\TenantModel;
use App\Models\LoyaltySummaryAnalysisModel;

class LoyaltyCtrl extends BaseController
{
    private function mergePrograms(): array
    {
        $standalone = (new LoyaltyProgramModel())->getAll();
        foreach ($standalone as &$p) { $p['source'] = 'standalone'; $p['event_name'] = null; }
        unset($p);

        $eventPrograms = (new EventLoyaltyModel())->getAllWithEvent();
        foreach ($eventPrograms as &$p) {
            $p['source'] = 'event';
            $p['status'] = $p['completion_id'] ? 'inactive' : 'active';
        }
        unset($p);

        return array_merge($standalone, $eventPrograms);
    }

    private function mergeRealisasi(array $programs): array
    {
        $standaloneIds = [];
        $eventIds      = [];
        foreach ($programs as $p) {
            if ($p['source'] === 'standalone') $standaloneIds[] = $p['id'];
            else                               $eventIds[]      = $p['id'];
        }

        $sRealisasi = (new LoyaltyRealisasiModel())->getGroupedByPrograms($standaloneIds);
        $eRealisasi = (new EventLoyaltyRealisasiModel())->getGroupedByPrograms($eventIds);

        $merged = [];
        foreach ($sRealisasi as $id => $data) { $merged['s_' . $id] = $data; }
        foreach ($eRealisasi as $id => $data) { $merged['e_' . $id] = $data; }
        return $merged;
    }

    private function syncBudget(int $programId): void
    {
        $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $programId)->findAll();
        $hadiahItems  = (new LoyaltyHadiahItemModel())->where('program_id', $programId)->findAll();

        $budget = 0;
        foreach ($voucherItems as $vi) { $budget += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher']; }
        foreach ($hadiahItems  as $hi) { $budget += (int)$hi['stok']              * (int)$hi['nilai_satuan'];  }

        (new LoyaltyProgramModel())->update($programId, ['budget' => $budget]);
    }

    public function index()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $programs  = $this->mergePrograms();
        $realisasi = $this->mergeRealisasi($programs);

        $standaloneProgIds = array_column(
            array_filter($programs, fn($p) => $p['source'] === 'standalone'),
            'id'
        );

        $hadiahItems     = (new LoyaltyHadiahItemModel())->getByPrograms($standaloneProgIds);
        $allHadiahIds    = array_merge(...array_map(fn($items) => array_column($items, 'id'), $hadiahItems ?: [[]]));
        $hadiahRealisasi = (new LoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        $voucherItems     = (new LoyaltyVoucherItemModel())->getByPrograms($standaloneProgIds);
        $allVoucherIds    = array_merge(...array_map(fn($items) => array_column($items, 'id'), $voucherItems ?: [[]]));
        $voucherRealisasi = (new LoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);

        // KPI: member from realisasi.jumlah, voucher terpakai from per-item data
        $totalMemberKpi   = 0;
        $targetMemberKpi  = 0;
        $totalTerpakaiKpi = 0;
        foreach ($programs as $p) {
            if ($p['status'] !== 'active') continue;
            $key = ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
            $totalMemberKpi  += (int)($realisasi[$key]['total'] ?? 0);
            $targetMemberKpi += (int)($p['target_peserta'] ?? 0);
        }
        foreach ($standaloneProgIds as $pid) {
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $totalTerpakaiKpi += (int)($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0);
            }
        }

        $totalBudget = array_sum(array_column($programs, 'budget'));
        $activeCount = count(array_filter($programs, fn($p) => $p['status'] === 'active'));

        return view('loyalty_program/index', [
            'user'             => $this->currentUser(),
            'programs'         => $programs,
            'realisasi'        => $realisasi,
            'hadiahItems'      => $hadiahItems,
            'hadiahRealisasi'  => $hadiahRealisasi,
            'voucherItems'     => $voucherItems,
            'voucherRealisasi' => $voucherRealisasi,
            'totalBudget'      => $totalBudget,
            'activeCount'      => $activeCount,
            'totalMemberKpi'   => $totalMemberKpi,
            'targetMemberKpi'  => $targetMemberKpi,
            'totalTerpakaiKpi' => $totalTerpakaiKpi,
            'canEdit'          => $this->canEditMenu('loyalty_main'),
            'stockBarang'      => (new StockBarangModel())->getAll(),
            'stockVoucherBatch'=> (new StockVoucherBatchModel())->getAvailable(),
            'tenants'          => (new TenantModel())->getActive(),
        ]);
    }

    // ── Hadiah items ─────────────────────────────────────────────────────────

    private function assertNotLocked(int $programId): bool
    {
        return ! (new LoyaltyProgramModel())->isLocked($programId);
    }

    public function storeHadiahItem(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $hadiahModel = new LoyaltyHadiahItemModel();
        $barangId   = ($post['barang_id'] ?? '') !== '' ? (int)$post['barang_id'] : null;
        $batchId    = ($post['batch_id']  ?? '') !== '' ? (int)$post['batch_id']  : null;
        $namaHadiah = $post['nama_hadiah'] ?? null;
        $nilaiSatuan = $clean($post['nilai_satuan']);
        $stok        = (int)($post['stok'] ?? 0);
        if ($barangId) {
            $barang      = (new StockBarangModel())->find($barangId);
            $namaHadiah  = $barang['nama_barang'] ?? $namaHadiah;
            $nilaiSatuan = (int)($barang['nilai_satuan'] ?? $nilaiSatuan);
            if ($stok <= 0) $stok = max(0, (int)$barang['stok_tersedia'] - (int)$barang['stok_reserved']);
        } elseif ($batchId) {
            $batch       = (new StockVoucherBatchModel())->find($batchId);
            $namaHadiah  = $batch['nama_voucher'] ?? $namaHadiah;
            $nilaiSatuan = (int)($batch['nilai_voucher'] ?? $nilaiSatuan);
            if ($stok <= 0) $stok = (int)($batch['sisa_kode'] ?? 0);
        }
        $hadiahModel->insert([
            'program_id'   => $programId,
            'barang_id'    => $barangId,
            'batch_id'     => $batchId,
            'nama_hadiah'  => $namaHadiah,
            'stok'         => $stok,
            'nilai_satuan' => $nilaiSatuan,
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        if ($barangId && $stok > 0) (new StockBarangModel())->reserveStock($barangId, $stok);
        if ($batchId  && $stok > 0) (new StockVoucherBatchModel())->reserveSisa($batchId, $stok);
        $this->syncBudget($programId);
        ActivityLog::write('create', 'loyalty_hadiah_item', (string)$hadiahModel->getInsertID(), $namaHadiah, ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Item hadiah berhasil ditambahkan.');
    }

    public function updateHadiahItem(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $hadiahModel = new LoyaltyHadiahItemModel();
        $old = $hadiahModel->find($itemId);
        if (! $old) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Item tidak ditemukan.');
        $post        = $this->request->getPost();
        $clean       = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $newStok     = (int)($post['stok'] ?? (int)$old['stok']);
        $namaHadiah  = $post['nama_hadiah'] ?? $old['nama_hadiah'];
        $nilaiSatuan = ($post['nilai_satuan'] ?? '') !== '' ? $clean($post['nilai_satuan']) : (int)$old['nilai_satuan'];
        ActivityLog::captureBefore($old);
        $hiData = [
            'nama_hadiah'  => $namaHadiah,
            'stok'         => $newStok,
            'nilai_satuan' => $nilaiSatuan,
            'catatan'      => $post['catatan'] ?? null,
        ];
        $hadiahModel->update($itemId, $hiData);
        ActivityLog::captureAfter($hiData);
        if (! empty($old['barang_id'])) {
            $delta = $newStok - (int)$old['stok'];
            if ($delta > 0) (new StockBarangModel())->reserveStock((int)$old['barang_id'], $delta);
            elseif ($delta < 0) (new StockBarangModel())->releaseStock((int)$old['barang_id'], abs($delta));
        }
        if (! empty($old['batch_id'])) {
            $delta = $newStok - (int)$old['stok'];
            if ($delta > 0) (new StockVoucherBatchModel())->reserveSisa((int)$old['batch_id'], $delta);
            elseif ($delta < 0) (new StockVoucherBatchModel())->releaseSisa((int)$old['batch_id'], abs($delta));
        }
        $this->syncBudget($programId);
        ActivityLog::write('update', 'loyalty_hadiah_item', (string)$itemId, $namaHadiah, ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Item hadiah berhasil diperbarui.');
    }

    public function deleteHadiahItem(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $hadiahModel = new LoyaltyHadiahItemModel();
        $item = $hadiahModel->find($itemId);
        if ($item) {
            $realized = (int)(db_connect()->table('loyalty_hadiah_realisasi')
                ->selectSum('jumlah_dibagikan')->where('item_id', $itemId)
                ->get()->getRowArray()['jumlah_dibagikan'] ?? 0);
            $unrealized = max(0, (int)$item['stok'] - $realized);
            if ($unrealized > 0 && ! empty($item['barang_id'])) (new StockBarangModel())->releaseStock((int)$item['barang_id'], $unrealized);
            if ($unrealized > 0 && ! empty($item['batch_id']))  (new StockVoucherBatchModel())->releaseSisa((int)$item['batch_id'], $unrealized);
        }
        (new LoyaltyHadiahRealisasiModel())->where('item_id', $itemId)->delete();
        $hadiahModel->delete($itemId);
        $this->syncBudget($programId);
        ActivityLog::write('delete', 'loyalty_hadiah_item', (string)$itemId, $item['nama_hadiah'] ?? '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Item hadiah dihapus.');
    }

    // Upload foto bukti realisasi (wajib untuk barang & voucher fisik). Return [filename|null, error|null].
    private function uploadRealisasiFoto(): array
    {
        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return [null, 'Foto bukti wajib diupload sebelum menyimpan realisasi.'];
        }
        if ($err = $this->validateUpload($file, self::MIME_IMAGE, 10)) {
            return [null, $err];
        }
        $name = 'lr_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $this->safeExt($file);
        $file->move(FCPATH . 'uploads/loyalty-realisasi', $name);
        return [$name, null];
    }

    public function storeHadiahRealisasi(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to('/loyalty')->with('error', 'Tanggal wajib diisi.');
        [$fotoName, $fotoErr] = $this->uploadRealisasiFoto();
        if ($fotoErr) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', $fotoErr);
        $userId  = $this->currentUser()['id'];
        $jumlah  = (int)($post['jumlah_dibagikan'] ?? 0);
        $kodeId  = ($post['kode_id'] ?? '') !== '' ? (int)$post['kode_id'] : null;
        $hrModel = new LoyaltyHadiahRealisasiModel();
        $hrModel->insert([
            'program_id'       => $programId,
            'item_id'          => $itemId,
            'nama_penerima'    => ($post['nama_penerima'] ?? '') ?: null,
            'kode_id'          => $kodeId,
            'tanggal'          => $post['tanggal'],
            'jumlah_dibagikan' => $kodeId ? 1 : $jumlah,
            'catatan'          => $post['catatan'] ?? null,
            'foto'             => $fotoName,
            'created_by'       => $userId,
        ]);
        $rid = $hrModel->getInsertID();

        $item = (new LoyaltyHadiahItemModel())->find($itemId);
        if ($kodeId) {
            // Assign kode voucher fisik
            (new StockVoucherKodeModel())->assign($kodeId, $post['nama_penerima'] ?? '', 'standalone', $programId, $itemId, (int)$rid);
            if (! empty($item['batch_id'])) {
                $svBatch = new StockVoucherBatchModel();
                $svSisa  = (int)($svBatch->find((int)$item['batch_id'])['sisa_kode'] ?? 0);
                $svBatch->deductSisa((int)$item['batch_id']);
                $svBatch->releaseSisa((int)$item['batch_id']); // reservation terpenuhi
                (new StockVoucherLogModel())->record((int)$item['batch_id'], 'keluar', 1, $svSisa, 'program', $programId, $this->currentUser()['id'], 'Distribusi via program');
            }
        } elseif (! empty($item['barang_id']) && $jumlah > 0) {
            // Kurangi stok barang fisik + release reservation (terealisasi)
            $barangModel = new StockBarangModel();
            $barang      = $barangModel->find((int)$item['barang_id']);
            $barangModel->deductStock((int)$item['barang_id'], $jumlah);
            $barangModel->releaseStock((int)$item['barang_id'], $jumlah);
            (new StockBarangLogModel())->writeKeluar((int)$item['barang_id'], $jumlah, (int)$barang['stok_tersedia'], 'loyalty_program', $programId, $post['tanggal'], $userId, $post['catatan'] ?? null);
        }

        ActivityLog::write('create', 'loyalty_hadiah_realisasi', (string)$rid, $post['tanggal'], ['program_id' => $programId, 'item_id' => $itemId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi disimpan.');
    }

    public function deleteHadiahRealisasi(int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $hrModel = new LoyaltyHadiahRealisasiModel();
        $entry   = $hrModel->find($rid);
        $hrModel->delete($rid);

        if ($entry) {
            if (! empty($entry['foto'])) { $f = FCPATH . 'uploads/loyalty-realisasi/' . $entry['foto']; if (is_file($f)) @unlink($f); }
            $item = (new LoyaltyHadiahItemModel())->find($itemId);
            if (! empty($entry['kode_id'])) {
                // Kembalikan kode voucher fisik
                (new StockVoucherKodeModel())->unassign((int)$entry['kode_id']);
                if (! empty($item['batch_id'])) {
                    $svBatch = new StockVoucherBatchModel();
                    $svSisa  = (int)($svBatch->find((int)$item['batch_id'])['sisa_kode'] ?? 0);
                    $svBatch->restoreSisa((int)$item['batch_id']);
                    $svBatch->reserveSisa((int)$item['batch_id']); // restore reservation
                    (new StockVoucherLogModel())->record((int)$item['batch_id'], 'retur', 1, $svSisa, 'program_batal', $programId, $this->currentUser()['id'], 'Batal realisasi program');
                }
            } elseif (! empty($item['barang_id']) && (int)($entry['jumlah_dibagikan'] ?? 0) > 0) {
                // Kembalikan stok barang fisik + restore reservation
                $jumlah      = (int)$entry['jumlah_dibagikan'];
                $barangModel = new StockBarangModel();
                $barang      = $barangModel->find((int)$item['barang_id']);
                $barangModel->restoreStock((int)$item['barang_id'], $jumlah);
                $barangModel->reserveStock((int)$item['barang_id'], $jumlah);
                (new StockBarangLogModel())->writeMasuk((int)$item['barang_id'], $jumlah, (int)$barang['stok_tersedia'], $entry['tanggal'], $this->currentUser()['id'], 'Rollback realisasi #'.$rid);
            }
        }

        ActivityLog::write('delete', 'loyalty_hadiah_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Voucher items ─────────────────────────────────────────────────────────

    public function storeVoucherItem(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post       = $this->request->getPost();
        $clean      = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $penyerapan = ($post['target_penyerapan'] ?? '') !== '' ? (float)$post['target_penyerapan'] : null;
        $voucherModel = new LoyaltyVoucherItemModel();
        $batchId    = ($post['batch_id'] ?? '') !== '' ? (int)$post['batch_id'] : null;
        $namaVoucher = $post['nama_voucher'] ?? null;
        $nilaiVoucher = $clean($post['nilai_voucher']);
        if ($batchId) {
            $batch       = (new StockVoucherBatchModel())->find($batchId);
            $namaVoucher  = $batch['nama_voucher'] ?? $namaVoucher;
            $nilaiVoucher = $batch['nilai_voucher'] ?? $nilaiVoucher;
        }
        $voucherModel->insert([
            'program_id'        => $programId,
            'batch_id'          => $batchId,
            'nama_voucher'      => $namaVoucher,
            'nilai_voucher'     => $nilaiVoucher,
            'total_diterbitkan' => $batchId ? ($batch['sisa_kode'] ?? 0) : (int)($post['total_diterbitkan'] ?? 0),
            'target_penyerapan' => $penyerapan,
            'catatan'           => $post['catatan'] ?? null,
            'created_by'        => $this->currentUser()['id'],
        ]);
        $this->syncBudget($programId);
        ActivityLog::write('create', 'loyalty_voucher_item', (string)$voucherModel->getInsertID(), $post['nama_voucher'], ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function deleteVoucherItem(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $voucherModel = new LoyaltyVoucherItemModel();
        $item = $voucherModel->find($itemId);
        (new LoyaltyVoucherRealisasiModel())->where('item_id', $itemId)->delete();
        $voucherModel->delete($itemId);
        $this->syncBudget($programId);
        ActivityLog::write('delete', 'loyalty_voucher_item', (string)$itemId, $item['nama_voucher'] ?? '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Voucher dihapus.');
    }

    public function storeVoucherRealisasi(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to('/loyalty')->with('error', 'Tanggal wajib diisi.');
        // Foto wajib hanya untuk voucher FISIK (punya batch). E-voucher tidak perlu.
        $vItem    = (new LoyaltyVoucherItemModel())->find($itemId);
        $fotoName = null;
        if (! empty($vItem['batch_id'])) {
            [$fotoName, $fotoErr] = $this->uploadRealisasiFoto();
            if ($fotoErr) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', $fotoErr);
        }
        $userId  = $this->currentUser()['id'];
        $kodeId  = ($post['kode_id'] ?? '') !== '' ? (int)$post['kode_id'] : null;
        $vrModel = new LoyaltyVoucherRealisasiModel();
        $vrModel->insert([
            'program_id'    => $programId,
            'item_id'       => $itemId,
            'kode_id'       => $kodeId,
            'nama_penerima' => ($post['nama_penerima'] ?? '') ?: null,
            'tanggal'       => $post['tanggal'],
            'tersebar'      => $kodeId ? 1 : (int)($post['tersebar'] ?? 0),
            'terpakai'      => (int)($post['terpakai'] ?? 0),
            'catatan'       => $post['catatan'] ?? null,
            'foto'          => $fotoName,
            'created_by'    => $userId,
        ]);
        $rid = $vrModel->getInsertID();

        // Assign kode dan kurangi sisa batch
        if ($kodeId) {
            $item = (new LoyaltyVoucherItemModel())->find($itemId);
            (new StockVoucherKodeModel())->assign($kodeId, $post['nama_penerima'] ?? '', 'standalone', $programId, $itemId, (int)$rid);
            if (! empty($item['batch_id'])) {
                (new StockVoucherBatchModel())->deductSisa((int)$item['batch_id']);
            }
        }

        ActivityLog::write('create', 'loyalty_voucher_realisasi', (string)$rid, $post['tanggal'], ['program_id' => $programId, 'item_id' => $itemId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi voucher disimpan.');
    }

    public function deleteVoucherRealisasi(int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $vrModel = new LoyaltyVoucherRealisasiModel();
        $entry   = $vrModel->find($rid);
        $vrModel->delete($rid);

        if ($entry && ! empty($entry['foto'])) { $f = FCPATH . 'uploads/loyalty-realisasi/' . $entry['foto']; if (is_file($f)) @unlink($f); }
        // Kembalikan kode ke available
        if ($entry && ! empty($entry['kode_id'])) {
            (new StockVoucherKodeModel())->unassign((int)$entry['kode_id']);
            $item = (new LoyaltyVoucherItemModel())->find($itemId);
            if (! empty($item['batch_id'])) {
                (new StockVoucherBatchModel())->restoreSisa((int)$item['batch_id']);
            }
        }

        ActivityLog::write('delete', 'loyalty_voucher_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Program CRUD ──────────────────────────────────────────────────────────

    public function storeProgram()
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        $post        = $this->request->getPost();
        $progModel   = new LoyaltyProgramModel();
        $jenis      = in_array($post['jenis'] ?? '', ['internal', 'tenant']) ? $post['jenis'] : 'internal';
        $tenantId   = ($jenis === 'tenant' && ! empty($post['tenant_id'])) ? (int)$post['tenant_id'] : null;
        $progModel->insert([
            'nama_program'    => $post['nama_program'],
            'jenis'           => $jenis,
            'mall'            => in_array($post['mall'] ?? '', ['ewalk', 'pentacity', 'both']) ? $post['mall'] : null,
            'tenant_id'       => $tenantId,
            'tanggal_mulai'   => $post['tanggal_mulai']  ?? null ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?? null ?: null,
            'jam_mulai'       => $post['jam_mulai']       ?? null ?: null,
            'jam_selesai'     => $post['jam_selesai']     ?? null ?: null,
            'mekanisme'       => $post['mekanisme']       ?? null,
            'target_peserta'  => ($post['target_peserta'] ?? '') !== '' ? (int)$post['target_peserta'] : null,
            'budget'          => 0,
            'status'          => 'active',
            'catatan'         => $post['catatan'] ?? null,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_program', (string)$progModel->getInsertID(), $post['nama_program']);
        return redirect()->to('/loyalty')->with('success', 'Program loyalty berhasil ditambahkan.');
    }

    public function updateProgram(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci.');
        $post        = $this->request->getPost();
        $loyaltyModel = new LoyaltyProgramModel();
        ActivityLog::captureBefore($loyaltyModel->find($id));
        $jenis    = in_array($post['jenis'] ?? '', ['internal', 'tenant']) ? $post['jenis'] : 'internal';
        $tenantId = ($jenis === 'tenant' && ! empty($post['tenant_id'])) ? (int)$post['tenant_id'] : null;
        $loyaltyData = [
            'nama_program'    => $post['nama_program'],
            'jenis'           => $jenis,
            'mall'            => in_array($post['mall'] ?? '', ['ewalk', 'pentacity', 'both']) ? $post['mall'] : null,
            'tenant_id'       => $tenantId,
            'tanggal_mulai'   => $post['tanggal_mulai']  ?? null ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?? null ?: null,
            'jam_mulai'       => $post['jam_mulai']       ?? null ?: null,
            'jam_selesai'     => $post['jam_selesai']     ?? null ?: null,
            'mekanisme'       => $post['mekanisme']       ?? null,
            'target_peserta'      => ($post['target_peserta'] ?? '') !== '' ? (int)$post['target_peserta'] : null,
            'target_member_aktif' => ($post['target_member_aktif'] ?? '') !== '' ? (int)$post['target_member_aktif'] : null,
            'catatan'             => $post['catatan'] ?? null,
        ];
        $loyaltyModel->update($id, $loyaltyData);
        ActivityLog::captureAfter($loyaltyData);
        ActivityLog::write('update', 'loyalty_program', (string)$id, $post['nama_program']);
        return redirect()->to('/loyalty')->with('success', 'Program berhasil diperbarui.');
    }

    public function deleteProgram(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci.');
        $prog = (new LoyaltyProgramModel())->find($id);
        (new LoyaltyRealisasiModel())->where('program_id', $id)->delete();
        $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $id)->findAll();
        foreach ($voucherItems as $vi) { (new LoyaltyVoucherRealisasiModel())->where('item_id', $vi['id'])->delete(); }
        (new LoyaltyVoucherItemModel())->where('program_id', $id)->delete();
        $hadiahItems = (new LoyaltyHadiahItemModel())->where('program_id', $id)->findAll();
        foreach ($hadiahItems as $hi) { (new LoyaltyHadiahRealisasiModel())->where('item_id', $hi['id'])->delete(); }
        (new LoyaltyHadiahItemModel())->where('program_id', $id)->delete();
        (new LoyaltyProgramModel())->delete($id);
        ActivityLog::write('delete', 'loyalty_program', (string)$id, $prog['nama_program'] ?? '');
        return redirect()->to('/loyalty')->with('success', 'Program berhasil dihapus.');
    }

    public function toggleStatus(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        $pm = new LoyaltyProgramModel();
        if ($pm->isLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci, tidak bisa diubah.');
        ActivityLog::captureBefore($pm->find($id));
        $pm->toggleStatus($id);
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'toggle_status']);
        return redirect()->to('/loyalty')->with('success', 'Status program diperbarui.');
    }

    public function lock(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        $post       = $this->request->getPost();
        $evalStatus = in_array($post['eval_status'] ?? '', ['berhasil', 'sebagian', 'gagal']) ? $post['eval_status'] : null;
        $pm = new LoyaltyProgramModel();
        ActivityLog::captureBefore($pm->find($id));
        $db = \Config\Database::connect();
        $db->transStart();
        $pm->lock($id, $this->currentUser()['id']);
        if ($evalStatus !== null) {
            $pm->update($id, [
                'eval_status'      => $evalStatus,
                'eval_kendala'     => ($post['eval_kendala'] ?? '') ?: null,
                'eval_rekomendasi' => ($post['eval_rekomendasi'] ?? '') ?: null,
            ]);
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Gagal mengunci program.');
        }
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'lock', 'eval_status' => $evalStatus]);
        return redirect()->to('/loyalty#program-s-'.$id)->with('success', 'Program berhasil dikunci.');
    }

    public function unlock(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Hanya admin yang bisa membuka kunci.');
        $pm = new LoyaltyProgramModel();
        ActivityLog::captureBefore($pm->find($id));
        $pm->unlock($id);
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'unlock']);
        return redirect()->to('/loyalty#program-s-'.$id)->with('success', 'Kunci program berhasil dibuka.');
    }

    public function duplikat(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        $orig = (new LoyaltyProgramModel())->find($id);
        if (! $orig) return redirect()->to('/loyalty')->with('error', 'Program tidak ditemukan.');
        $progModel = new LoyaltyProgramModel();
        $progModel->insert([
            'nama_program'        => $orig['nama_program'] . ' — Kopi',
            'jenis'               => $orig['jenis'],
            'tenant_id'           => $orig['tenant_id'],
            'tanggal_mulai'       => null,
            'tanggal_selesai'     => null,
            'jam_mulai'           => $orig['jam_mulai'],
            'jam_selesai'         => $orig['jam_selesai'],
            'mekanisme'           => $orig['mekanisme'],
            'target_peserta'      => $orig['target_peserta'],
            'target_member_aktif' => $orig['target_member_aktif'],
            'target_type'         => $orig['target_type'],
            'target_penyerapan'   => $orig['target_penyerapan'],
            'total_voucher'       => $orig['total_voucher'],
            'nilai_voucher'       => $orig['nilai_voucher'],
            'biaya_per_member'    => $orig['biaya_per_member'],
            'budget'              => 0,
            'status'              => 'active',
            'catatan'             => $orig['catatan'],
            'created_by'          => $this->currentUser()['id'],
        ]);
        $newId = $progModel->getInsertID();
        if (! $newId) return redirect()->to('/loyalty')->with('error', 'Gagal menduplikat program.');
        ActivityLog::write('create', 'loyalty_program', (string)$newId, $orig['nama_program'] . ' — Kopi', ['duplikat_dari' => $id]);
        return redirect()->to('/loyalty#program-s-' . $newId)->with('success', 'Program berhasil diduplikat. Perbarui tanggal dan item reward sesuai kebutuhan.');
    }

    public function storeRealisasi(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (! (new LoyaltyProgramModel())->find($programId) || empty($post['tanggal'])) {
            return redirect()->to('/loyalty')->with('error', 'Data tidak valid.');
        }
        $realModel = new LoyaltyRealisasiModel();
        $realModel->insert([
            'program_id' => $programId,
            'tanggal'    => $post['tanggal'],
            'jumlah'       => (int)($post['jumlah'] ?? 0),
            'member_aktif' => (int)($post['member_aktif'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_realisasi', (string)$realModel->getInsertID(), $post['tanggal'], ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi berhasil disimpan.');
    }

    public function deleteRealisasi(int $programId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        (new LoyaltyRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'loyalty_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function summary()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $programs  = $this->mergePrograms();
        $programMap = [];
        foreach ($programs as $p) {
            $key = ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
            $programMap[$key] = $p;
        }

        $standaloneIds = array_column(array_filter($programs, fn($p) => $p['source'] === 'standalone'), 'id');
        $eventIds      = array_column(array_filter($programs, fn($p) => $p['source'] === 'event'), 'id');

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $sModel  = new LoyaltyRealisasiModel();
        $eModel  = new EventLoyaltyRealisasiModel();
        $vrModel = new LoyaltyVoucherRealisasiModel();
        $evrModel = new EventLoyaltyVoucherRealisasiModel();
        $ehrModel = new EventLoyaltyHadiahRealisasiModel();

        // Voucher items — standalone
        $voucherItemsGrouped = (new LoyaltyVoucherItemModel())->getByPrograms($standaloneIds);
        $allVoucherIds = [];
        foreach ($voucherItemsGrouped as $items) {
            foreach ($items as $vi) { $allVoucherIds[] = $vi['id']; }
        }

        // Voucher items — event programs
        $evoucherItemsGrouped = (new EventLoyaltyVoucherItemModel())->getByPrograms($eventIds);
        $allEvoucherIds = [];
        foreach ($evoucherItemsGrouped as $items) {
            foreach ($items as $vi) { $allEvoucherIds[] = $vi['id']; }
        }

        // Hadiah items — standalone
        $hadiahItemsGrouped = (new LoyaltyHadiahItemModel())->getByPrograms($standaloneIds);
        $allHadiahIds = [];
        foreach ($hadiahItemsGrouped as $items) {
            foreach ($items as $hi) { $allHadiahIds[] = $hi['id']; }
        }

        // Hadiah items — event programs
        $ehadiahItemsGrouped = (new EventLoyaltyHadiahItemModel())->getByPrograms($eventIds);
        $allEhadiahIds = [];
        foreach ($ehadiahItemsGrouped as $items) {
            foreach ($items as $hi) { $allEhadiahIds[] = $hi['id']; }
        }

        $sMonths = array_column($sModel->getAvailableMonths(), 'bulan');
        $eMonths = array_column($eModel->getAvailableMonths(), 'bulan');
        $monthList = array_unique(array_merge($sMonths, $eMonths));
        if (! in_array($bulan, $monthList)) $monthList[] = $bulan;
        rsort($monthList);

        // Monthly per-program (selected month)
        $sMonthly = $sModel->getMonthlyByPrograms($bulan, $standaloneIds);
        $eMonthly = $eModel->getMonthlyByPrograms($bulan, $eventIds);

        $monthlyData = [];
        foreach ($sMonthly as $id => $data) { $monthlyData['s_' . $id] = $data; }
        foreach ($eMonthly as $id => $data) { $monthlyData['e_' . $id] = $data; }

        // Standalone voucher & hadiah — monthly
        $vMonthly = $vrModel->getMonthlyByItems($bulan, $allVoucherIds);
        $hrModel  = new LoyaltyHadiahRealisasiModel();
        $hMonthly = $hrModel->getMonthlyByItems($bulan, $allHadiahIds);
        $hAllTime = [];
        foreach ($hrModel->getGroupedByItems($allHadiahIds) as $iid => $d) {
            $hAllTime[$iid] = (int)$d['total'];
        }

        // Event voucher & hadiah — monthly
        $evMonthly = $evrModel->getMonthlyByItems($bulan, $allEvoucherIds);
        $ehMonthly = $ehrModel->getMonthlyByItems($bulan, $allEhadiahIds);
        $ehAllTime = [];
        foreach ($ehrModel->getGroupedByItems($allEhadiahIds) as $iid => $d) {
            $ehAllTime[$iid] = (int)$d['total'];
        }

        // Aggregate voucher by program_id (for per-program table)
        $voucherByProgram = [];
        foreach ($voucherItemsGrouped as $progId => $items) {
            $voucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $vMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $voucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $voucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }
        $evoucherByProgram = [];
        foreach ($evoucherItemsGrouped as $progId => $items) {
            $evoucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $evMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $evoucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $evoucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }

        // Aggregate hadiah by program_id (for per-program table)
        $hadiahByProgram = [];
        foreach ($hadiahItemsGrouped as $progId => $items) {
            $hadiahByProgram[$progId] = 0;
            foreach ($items as $hi) {
                $hadiahByProgram[$progId] += (int)($hMonthly[$hi['id']] ?? 0);
            }
        }
        $ehadiahByProgram = [];
        foreach ($ehadiahItemsGrouped as $progId => $items) {
            $ehadiahByProgram[$progId] = 0;
            foreach ($items as $hi) {
                $ehadiahByProgram[$progId] += (int)($ehMonthly[$hi['id']] ?? 0);
            }
        }

        // Cumulative totals up to selected month (for progress-vs-target across multi-month programs)
        $sCumulative  = $sModel->getCumulativeByPrograms($bulan, $standaloneIds);
        $eCumulative  = $eModel->getCumulativeByPrograms($bulan, $eventIds);
        $vCumulative  = $vrModel->getCumulativeByItems($bulan, $allVoucherIds);
        $evCumulative = $evrModel->getCumulativeByItems($bulan, $allEvoucherIds);

        $cumulativeData = [];
        foreach ($sCumulative as $id => $data) { $cumulativeData['s_' . $id] = $data; }
        foreach ($eCumulative as $id => $data) { $cumulativeData['e_' . $id] = $data; }

        $voucherCumByProgram = [];
        foreach ($voucherItemsGrouped as $progId => $items) {
            $voucherCumByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $vCumulative[$vi['id']] ?? null;
                if ($vd) {
                    $voucherCumByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $voucherCumByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }
        $evoucherCumByProgram = [];
        foreach ($evoucherItemsGrouped as $progId => $items) {
            $evoucherCumByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $evCumulative[$vi['id']] ?? null;
                if ($vd) {
                    $evoucherCumByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $evoucherCumByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }

        // KPIs for selected month
        $kpiMember      = 0;
        $kpiMemberAktif = 0;
        $kpiTersebar    = 0;
        $kpiTerpakai    = 0;
        $kpiHadiah      = 0;
        foreach ($monthlyData as $data) {
            $kpiMember      += (int)($data['total_jumlah']       ?? 0);
            $kpiMemberAktif += (int)($data['total_member_aktif'] ?? 0);
        }
        foreach ($vMonthly as $vd) {
            $kpiTersebar += (int)$vd['total_tersebar'];
            $kpiTerpakai += (int)$vd['total_terpakai'];
        }
        foreach ($evMonthly as $vd) {
            $kpiTersebar += (int)$vd['total_tersebar'];
            $kpiTerpakai += (int)$vd['total_terpakai'];
        }
        foreach ($hMonthly as $n)  { $kpiHadiah += (int)$n; }
        foreach ($ehMonthly as $n) { $kpiHadiah += (int)$n; }

        // Monthly trend — all months
        $sTotals  = $sModel->getAllMonthlyTotals($standaloneIds);
        $eTotals  = $eModel->getAllMonthlyTotals($eventIds);
        $vTotals  = $vrModel->getAllMonthlyTotals($allVoucherIds);
        $hTotals  = $hrModel->getAllMonthlyTotals($allHadiahIds);
        $evTotals = $evrModel->getAllMonthlyTotals($allEvoucherIds);
        $ehTotals = $ehrModel->getAllMonthlyTotals($allEhadiahIds);

        $empty = ['bulan' => '', 'total_jumlah' => 0, 'total_member_aktif' => 0, 'total_tersebar' => 0, 'total_terpakai' => 0, 'total_hadiah' => 0];
        $allMonthlyTotals = [];
        foreach ($sTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_jumlah']       += (int)$row['total_jumlah'];
            $allMonthlyTotals[$m]['total_member_aktif'] += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($eTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_jumlah']       += (int)$row['total_jumlah'];
            $allMonthlyTotals[$m]['total_member_aktif'] += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($vTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $allMonthlyTotals[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($evTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $allMonthlyTotals[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($hTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        foreach ($ehTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        ksort($allMonthlyTotals);
        $allMonthlyMap    = $allMonthlyTotals;                 // semua bulan (untuk delta lintas tahun)
        $trendYear        = substr($bulan, 0, 4);              // tren ikut tahun bulan yang dipilih
        $allMonthlyTotals = array_filter($allMonthlyTotals, fn($row) => str_starts_with($row['bulan'], $trendYear));

        // Daily data for selected month
        $sDailyRows  = $sModel->getDailyForMonth($bulan, $standaloneIds);
        $eDailyRows  = $eModel->getDailyForMonth($bulan, $eventIds);
        $vDailyRows  = $vrModel->getDailyForMonth($bulan, $allVoucherIds);
        $evDailyRows = $evrModel->getDailyForMonth($bulan, $allEvoucherIds);

        $daysInMonth   = (int)date('t', strtotime($bulan . '-01'));
        $chartDates    = [];
        $dailyMember   = array_fill(0, $daysInMonth, 0);
        $dailyTersebar = array_fill(0, $daysInMonth, 0);
        $dailyTerpakai = array_fill(0, $daysInMonth, 0);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartDates[] = str_pad($d, 2, '0', STR_PAD_LEFT);
        }
        foreach ($sDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyMember[$idx] += (int)$row['jumlah'];
        }
        foreach ($eDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyMember[$idx] += (int)$row['jumlah'];
        }
        foreach ($vDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyTersebar[$idx] += (int)$row['tersebar'];
            $dailyTerpakai[$idx] += (int)$row['terpakai'];
        }
        foreach ($evDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyTersebar[$idx] += (int)$row['tersebar'];
            $dailyTerpakai[$idx] += (int)$row['terpakai'];
        }

        $totalBudget       = array_sum(array_column($programs, 'budget'));
        $totalBudgetActive = array_sum(array_column(array_filter($programs, fn($p) => $p['status'] === 'active'), 'budget'));

        // ── Nilai realisasi (Rp) & serapan budget ─────────────────────────
        $voucherNilai = $evoucherNilai = $hadiahNilai = $ehadiahNilai = [];
        foreach ($voucherItemsGrouped as $items)  foreach ($items as $vi) $voucherNilai[$vi['id']]  = (float)($vi['nilai_voucher'] ?? 0);
        foreach ($evoucherItemsGrouped as $items) foreach ($items as $vi) $evoucherNilai[$vi['id']] = (float)($vi['nilai_voucher'] ?? 0);
        foreach ($hadiahItemsGrouped as $items)   foreach ($items as $hi) $hadiahNilai[$hi['id']]   = (float)($hi['nilai_satuan'] ?? 0);
        foreach ($ehadiahItemsGrouped as $items)  foreach ($items as $hi) $ehadiahNilai[$hi['id']]  = (float)($hi['nilai_satuan'] ?? 0);

        $calcNilai = function (array $vMon, array $evMon, array $hMon, array $ehMon)
            use ($voucherNilai, $evoucherNilai, $hadiahNilai, $ehadiahNilai): array {
            $v = 0.0;
            foreach ($vMon  as $id => $d) $v += (int)($d['total_terpakai'] ?? 0) * ($voucherNilai[$id]  ?? 0);
            foreach ($evMon as $id => $d) $v += (int)($d['total_terpakai'] ?? 0) * ($evoucherNilai[$id] ?? 0);
            $h = 0.0;
            foreach ($hMon  as $id => $n) $h += (int)$n * ($hadiahNilai[$id]  ?? 0);
            foreach ($ehMon as $id => $n) $h += (int)$n * ($ehadiahNilai[$id] ?? 0);
            return [$v, $h];
        };
        [$nilaiVoucher, $nilaiHadiah] = $calcNilai($vMonthly, $evMonthly, $hMonthly, $ehMonthly);
        $nilaiRealisasi = $nilaiVoucher + $nilaiHadiah;
        $serapanPct     = $totalBudgetActive > 0 ? round($nilaiRealisasi / $totalBudgetActive * 100, 1) : 0;

        // ── Delta vs bulan sebelumnya ─────────────────────────────────────
        $prevBulan = date('Y-m', strtotime($bulan . '-01 -1 month'));
        $prevRow   = $allMonthlyMap[$prevBulan] ?? $empty;
        $kpiMemberPrev      = (int)$prevRow['total_jumlah'];
        $kpiMemberAktifPrev = (int)$prevRow['total_member_aktif'];
        $kpiTersebarPrev    = (int)$prevRow['total_tersebar'];
        $kpiTerpakaiPrev    = (int)$prevRow['total_terpakai'];
        $kpiHadiahPrev      = (int)$prevRow['total_hadiah'];
        [$nvPrev, $nhPrev]  = $calcNilai(
            $vrModel->getMonthlyByItems($prevBulan, $allVoucherIds),
            $evrModel->getMonthlyByItems($prevBulan, $allEvoucherIds),
            $hrModel->getMonthlyByItems($prevBulan, $allHadiahIds),
            $ehrModel->getMonthlyByItems($prevBulan, $allEhadiahIds)
        );
        $nilaiRealisasiPrev = $nvPrev + $nhPrev;

        return view('loyalty_program/summary', [
            'user'                 => $this->currentUser(),
            'programs'             => $programs,
            'programMap'           => $programMap,
            'bulan'                => $bulan,
            'monthList'            => $monthList,
            'monthlyData'          => $monthlyData,
            'voucherByProgram'     => $voucherByProgram,
            'evoucherByProgram'    => $evoucherByProgram,
            'evoucherItemsGrouped' => $evoucherItemsGrouped,
            'evMonthly'            => $evMonthly,
            'voucherItemsGrouped'  => $voucherItemsGrouped,
            'vMonthly'             => $vMonthly,
            'hadiahItemsGrouped'   => $hadiahItemsGrouped,
            'hMonthly'             => $hMonthly,
            'hAllTime'             => $hAllTime,
            'ehadiahItemsGrouped'  => $ehadiahItemsGrouped,
            'ehMonthly'            => $ehMonthly,
            'ehAllTime'            => $ehAllTime,
            'allMonthlyTotals'     => array_values($allMonthlyTotals),
            'kpiMember'            => $kpiMember,
            'kpiMemberAktif'       => $kpiMemberAktif,
            'kpiTersebar'          => $kpiTersebar,
            'kpiTerpakai'          => $kpiTerpakai,
            'kpiHadiah'            => $kpiHadiah,
            'hadiahByProgram'      => $hadiahByProgram,
            'ehadiahByProgram'     => $ehadiahByProgram,
            'totalBudget'          => $totalBudget,
            'totalBudgetActive'    => $totalBudgetActive,
            'nilaiRealisasi'       => $nilaiRealisasi,
            'nilaiVoucher'         => $nilaiVoucher,
            'nilaiHadiah'          => $nilaiHadiah,
            'serapanPct'           => $serapanPct,
            'nilaiRealisasiPrev'   => $nilaiRealisasiPrev,
            'cumulativeData'       => $cumulativeData,
            'voucherCumByProgram'  => $voucherCumByProgram,
            'evoucherCumByProgram' => $evoucherCumByProgram,
            'analisaMap'           => (new LoyaltySummaryAnalysisModel())->getMapByMonth($bulan),
            'prevAnalisaMap'       => (new LoyaltySummaryAnalysisModel())->getMapByMonth($prevBulan),
            'canEdit'              => $this->canEditMenu('loyalty_main'),
            'trendYear'            => $trendYear,
            'kpiMemberPrev'        => $kpiMemberPrev,
            'kpiMemberAktifPrev'   => $kpiMemberAktifPrev,
            'kpiTersebarPrev'      => $kpiTersebarPrev,
            'kpiTerpakaiPrev'      => $kpiTerpakaiPrev,
            'kpiHadiahPrev'        => $kpiHadiahPrev,
            'chartDates'           => $chartDates,
            'dailyMember'          => $dailyMember,
            'dailyTersebar'        => $dailyTersebar,
            'dailyTerpakai'        => $dailyTerpakai,
        ]);
    }

    // Simpan analisa per program (AJAX) — dipakai di halaman summary
    public function saveAnalisa()
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Akses ditolak.']);
        }
        $bulan     = (string)$this->request->getPost('bulan');
        $source    = (string)$this->request->getPost('source');
        $programId = (int)$this->request->getPost('program_id');
        $analisa      = trim((string)$this->request->getPost('analisa'));
        $highlight    = trim((string)$this->request->getPost('highlight'));
        $kendala      = trim((string)$this->request->getPost('kendala'));
        $tindakLanjut = trim((string)$this->request->getPost('tindak_lanjut'));

        if (! preg_match('/^\d{4}-\d{2}$/', $bulan) || ! in_array($source, ['s', 'e'], true) || $programId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Data tidak valid.', 'csrf' => csrf_hash()]);
        }

        (new LoyaltySummaryAnalysisModel())->saveAnalisa($bulan, $source, $programId, $analisa, $this->currentUser()['id'], $highlight, $kendala, $tindakLanjut);
        ActivityLog::write('update', 'loyalty_analisa', $source . '_' . $programId, 'Analisa program — ' . $bulan, ['bulan' => $bulan]);

        $filled = $analisa !== '' || $highlight !== '' || $kendala !== '' || $tindakLanjut !== '';
        return $this->response->setJSON(['ok' => true, 'filled' => $filled, 'csrf' => csrf_hash()]);
    }

    public function printSummary()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $programs      = $this->mergePrograms();
        $standaloneIds = array_column(array_filter($programs, fn($p) => $p['source'] === 'standalone'), 'id');
        $eventIds      = array_column(array_filter($programs, fn($p) => $p['source'] === 'event'), 'id');

        $sModel   = new LoyaltyRealisasiModel();
        $eModel   = new EventLoyaltyRealisasiModel();
        $vrModel  = new LoyaltyVoucherRealisasiModel();
        $evrModel = new EventLoyaltyVoucherRealisasiModel();
        $hrModel  = new LoyaltyHadiahRealisasiModel();
        $ehrModel = new EventLoyaltyHadiahRealisasiModel();

        $voucherItemsGrouped  = (new LoyaltyVoucherItemModel())->getByPrograms($standaloneIds);
        $evoucherItemsGrouped = (new EventLoyaltyVoucherItemModel())->getByPrograms($eventIds);
        $hadiahItemsGrouped   = (new LoyaltyHadiahItemModel())->getByPrograms($standaloneIds);
        $ehadiahItemsGrouped  = (new EventLoyaltyHadiahItemModel())->getByPrograms($eventIds);

        $allVoucherIds  = array_merge(...array_map(fn($g) => array_column($g, 'id'), $voucherItemsGrouped ?: [[]]));
        $allEvoucherIds = array_merge(...array_map(fn($g) => array_column($g, 'id'), $evoucherItemsGrouped ?: [[]]));
        $allHadiahIds   = array_merge(...array_map(fn($g) => array_column($g, 'id'), $hadiahItemsGrouped  ?: [[]]));
        $allEhadiahIds  = array_merge(...array_map(fn($g) => array_column($g, 'id'), $ehadiahItemsGrouped ?: [[]]));

        $sMonthly  = $sModel->getMonthlyByPrograms($bulan, $standaloneIds);
        $eMonthly  = $eModel->getMonthlyByPrograms($bulan, $eventIds);
        $vMonthly  = $vrModel->getMonthlyByItems($bulan, $allVoucherIds);
        $evMonthly = $evrModel->getMonthlyByItems($bulan, $allEvoucherIds);
        $hMonthly  = $hrModel->getMonthlyByItems($bulan, $allHadiahIds);
        $ehMonthly = $ehrModel->getMonthlyByItems($bulan, $allEhadiahIds);

        $monthlyData = [];
        foreach ($sMonthly as $id => $data) { $monthlyData['s_' . $id] = $data; }
        foreach ($eMonthly as $id => $data) { $monthlyData['e_' . $id] = $data; }

        $voucherByProgram = [];
        foreach ($voucherItemsGrouped as $progId => $items) {
            $voucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $vMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $voucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $voucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }
        $evoucherByProgram = [];
        foreach ($evoucherItemsGrouped as $progId => $items) {
            $evoucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $evMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $evoucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $evoucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }
        $hadiahByProgram = [];
        foreach ($hadiahItemsGrouped as $progId => $items) {
            $hadiahByProgram[$progId] = 0;
            foreach ($items as $hi) { $hadiahByProgram[$progId] += (int)($hMonthly[$hi['id']] ?? 0); }
        }
        $ehadiahByProgram = [];
        foreach ($ehadiahItemsGrouped as $progId => $items) {
            $ehadiahByProgram[$progId] = 0;
            foreach ($items as $hi) { $ehadiahByProgram[$progId] += (int)($ehMonthly[$hi['id']] ?? 0); }
        }

        $kpiMember = $kpiMemberAktif = $kpiTersebar = $kpiTerpakai = $kpiHadiah = 0;
        foreach ($monthlyData as $data) {
            $kpiMember      += (int)($data['total_jumlah']       ?? 0);
            $kpiMemberAktif += (int)($data['total_member_aktif'] ?? 0);
        }
        foreach (array_merge($vMonthly, $evMonthly) as $vd) {
            $kpiTersebar += (int)$vd['total_tersebar'];
            $kpiTerpakai += (int)$vd['total_terpakai'];
        }
        foreach (array_merge($hMonthly, $ehMonthly) as $n) { $kpiHadiah += (int)$n; }

        // ── Pembanding bulan lalu & kumulatif per program (program multi-bulan) ──
        $prevBulan = date('Y-m', strtotime($bulan . '-01 -1 month'));

        $aggVoucherByProgram = function (array $itemsGrouped, array $perItem): array {
            $out = [];
            foreach ($itemsGrouped as $progId => $items) {
                $out[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
                foreach ($items as $vi) {
                    $vd = $perItem[$vi['id']] ?? null;
                    if ($vd) {
                        $out[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                        $out[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                    }
                }
            }
            return $out;
        };
        $aggHadiahByProgram = function (array $itemsGrouped, array $perItem): array {
            $out = [];
            foreach ($itemsGrouped as $progId => $items) {
                $out[$progId] = 0;
                foreach ($items as $hi) { $out[$progId] += (int)($perItem[$hi['id']] ?? 0); }
            }
            return $out;
        };

        $prevMonthlyData = [];
        foreach ($sModel->getMonthlyByPrograms($prevBulan, $standaloneIds) as $id => $d) { $prevMonthlyData['s_' . $id] = $d; }
        foreach ($eModel->getMonthlyByPrograms($prevBulan, $eventIds) as $id => $d)      { $prevMonthlyData['e_' . $id] = $d; }

        $cumulativeData = [];
        foreach ($sModel->getCumulativeByPrograms($bulan, $standaloneIds) as $id => $d) { $cumulativeData['s_' . $id] = $d; }
        foreach ($eModel->getCumulativeByPrograms($bulan, $eventIds) as $id => $d)      { $cumulativeData['e_' . $id] = $d; }

        $vPrevM  = $vrModel->getMonthlyByItems($prevBulan, $allVoucherIds);
        $evPrevM = $evrModel->getMonthlyByItems($prevBulan, $allEvoucherIds);
        $hPrevM  = $hrModel->getMonthlyByItems($prevBulan, $allHadiahIds);
        $ehPrevM = $ehrModel->getMonthlyByItems($prevBulan, $allEhadiahIds);

        $prevVoucherByProgram   = $aggVoucherByProgram($voucherItemsGrouped,  $vPrevM);
        $prevEvoucherByProgram  = $aggVoucherByProgram($evoucherItemsGrouped, $evPrevM);
        $prevHadiahByProgram    = $aggHadiahByProgram($hadiahItemsGrouped,    $hPrevM);
        $prevEhadiahByProgram   = $aggHadiahByProgram($ehadiahItemsGrouped,   $ehPrevM);

        $voucherCumByProgram    = $aggVoucherByProgram($voucherItemsGrouped,  $vrModel->getCumulativeByItems($bulan, $allVoucherIds));
        $evoucherCumByProgram   = $aggVoucherByProgram($evoucherItemsGrouped, $evrModel->getCumulativeByItems($bulan, $allEvoucherIds));
        $hadiahCumByProgram     = $aggHadiahByProgram($hadiahItemsGrouped,    $hrModel->getCumulativeByItems($bulan, $allHadiahIds));
        $ehadiahCumByProgram    = $aggHadiahByProgram($ehadiahItemsGrouped,   $ehrModel->getCumulativeByItems($bulan, $allEhadiahIds));

        // ── Nilai realisasi (Rp) & serapan budget bulan ini ──────────────
        $voucherNilai = $evoucherNilai = $hadiahNilai = $ehadiahNilai = [];
        foreach ($voucherItemsGrouped as $items)  foreach ($items as $vi) $voucherNilai[$vi['id']]  = (float)($vi['nilai_voucher'] ?? 0);
        foreach ($evoucherItemsGrouped as $items) foreach ($items as $vi) $evoucherNilai[$vi['id']] = (float)($vi['nilai_voucher'] ?? 0);
        foreach ($hadiahItemsGrouped as $items)   foreach ($items as $hi) $hadiahNilai[$hi['id']]   = (float)($hi['nilai_satuan'] ?? 0);
        foreach ($ehadiahItemsGrouped as $items)  foreach ($items as $hi) $ehadiahNilai[$hi['id']]  = (float)($hi['nilai_satuan'] ?? 0);

        $nilaiRealisasi = 0.0;
        foreach ($vMonthly  as $id => $d) $nilaiRealisasi += (int)($d['total_terpakai'] ?? 0) * ($voucherNilai[$id]  ?? 0);
        foreach ($evMonthly as $id => $d) $nilaiRealisasi += (int)($d['total_terpakai'] ?? 0) * ($evoucherNilai[$id] ?? 0);
        foreach ($hMonthly  as $id => $n) $nilaiRealisasi += (int)$n * ($hadiahNilai[$id]  ?? 0);
        foreach ($ehMonthly as $id => $n) $nilaiRealisasi += (int)$n * ($ehadiahNilai[$id] ?? 0);
        $totalBudgetActive = array_sum(array_column(array_filter($programs, fn($p) => $p['status'] === 'active'), 'budget'));
        $serapanPct        = $totalBudgetActive > 0 ? round($nilaiRealisasi / $totalBudgetActive * 100, 1) : 0;

        // ── Program baru per mall (mulai di bulan terpilih) ───────────────
        $mallCounts = ['ewalk' => 0, 'pentacity' => 0, 'both' => 0, 'unset' => 0];
        foreach ($programs as $p) {
            $mulai = $p['source'] === 'standalone' ? ($p['tanggal_mulai'] ?? '') : ($p['event_start_date'] ?? '');
            if (! $mulai || substr($mulai, 0, 7) !== $bulan) continue;
            $mall = $p['source'] === 'standalone' ? ($p['mall'] ?? '') : ($p['event_mall'] ?? '');
            $mallCounts[isset($mallCounts[$mall]) ? $mall : 'unset']++;
        }

        // ── Tren 6 bulan terakhir (s/d bulan terpilih) untuk grafik ───────
        $empty = ['total_jumlah' => 0, 'total_member_aktif' => 0, 'total_tersebar' => 0, 'total_terpakai' => 0, 'total_hadiah' => 0];
        $trendMap = [];
        $addTo = function (string $m, string $k, int $v) use (&$trendMap, $empty) {
            if (! isset($trendMap[$m])) $trendMap[$m] = $empty;
            $trendMap[$m][$k] += $v;
        };
        foreach ($sModel->getAllMonthlyTotals($standaloneIds) as $r) { $addTo($r['bulan'], 'total_jumlah', (int)$r['total_jumlah']); $addTo($r['bulan'], 'total_member_aktif', (int)($r['total_member_aktif'] ?? 0)); }
        foreach ($eModel->getAllMonthlyTotals($eventIds) as $r)      { $addTo($r['bulan'], 'total_jumlah', (int)$r['total_jumlah']); $addTo($r['bulan'], 'total_member_aktif', (int)($r['total_member_aktif'] ?? 0)); }
        foreach ($vrModel->getAllMonthlyTotals($allVoucherIds) as $r)   { $addTo($r['bulan'], 'total_tersebar', (int)$r['total_tersebar']); $addTo($r['bulan'], 'total_terpakai', (int)$r['total_terpakai']); }
        foreach ($evrModel->getAllMonthlyTotals($allEvoucherIds) as $r) { $addTo($r['bulan'], 'total_tersebar', (int)$r['total_tersebar']); $addTo($r['bulan'], 'total_terpakai', (int)$r['total_terpakai']); }
        foreach ($hrModel->getAllMonthlyTotals($allHadiahIds) as $r)    { $addTo($r['bulan'], 'total_hadiah', (int)$r['total_dibagikan']); }
        foreach ($ehrModel->getAllMonthlyTotals($allEhadiahIds) as $r)  { $addTo($r['bulan'], 'total_hadiah', (int)$r['total_dibagikan']); }

        $trendMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime($bulan . '-01 -' . $i . ' month'));
            $trendMonths[] = ['bulan' => $m] + ($trendMap[$m] ?? $empty);
        }

        // ── Aktivitas harian bulan terpilih untuk grafik ──────────────────
        $daysInMonth   = (int)date('t', strtotime($bulan . '-01'));
        $dailyMember   = array_fill(0, $daysInMonth, 0);
        $dailyTersebar = array_fill(0, $daysInMonth, 0);
        $dailyTerpakai = array_fill(0, $daysInMonth, 0);
        foreach (array_merge($sModel->getDailyForMonth($bulan, $standaloneIds), $eModel->getDailyForMonth($bulan, $eventIds)) as $row) {
            $dailyMember[(int)date('j', strtotime($row['tanggal'])) - 1] += (int)$row['jumlah'];
        }
        foreach (array_merge($vrModel->getDailyForMonth($bulan, $allVoucherIds), $evrModel->getDailyForMonth($bulan, $allEvoucherIds)) as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyTersebar[$idx] += (int)$row['tersebar'];
            $dailyTerpakai[$idx] += (int)$row['terpakai'];
        }

        // ── Insight otomatis (rule-based) untuk blok Analisa ──────────────
        $kpiMemberPrev = $kpiTerpakaiPrev = $kpiTersebarPrev = $kpiHadiahPrev = 0;
        foreach ($prevMonthlyData as $d) { $kpiMemberPrev += (int)($d['total_jumlah'] ?? 0); }
        foreach (array_merge($vPrevM, $evPrevM) as $d) { $kpiTersebarPrev += (int)$d['total_tersebar']; $kpiTerpakaiPrev += (int)$d['total_terpakai']; }
        foreach (array_merge($hPrevM, $ehPrevM) as $n) { $kpiHadiahPrev += (int)$n; }

        $fmtDelta = function (int $now, int $prev): string {
            if ($prev <= 0) return $now > 0 ? 'naik dari 0 bulan lalu' : 'sama dengan bulan lalu (0)';
            $pct = round(($now - $prev) / $prev * 100);
            return ($pct >= 0 ? 'naik ' : 'turun ') . abs($pct) . '% dari bulan lalu (' . number_format($prev) . ')';
        };

        // Program paling aktif bulan ini (skor = member + voucher terpakai + hadiah)
        $topName = null; $topScore = 0;
        $noActivity = 0;
        foreach ($programs as $p) {
            $key   = ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
            $vd    = $p['source'] === 'standalone' ? ($voucherByProgram[$p['id']] ?? []) : ($evoucherByProgram[$p['id']] ?? []);
            $score = (int)(($monthlyData[$key]['total_jumlah'] ?? 0))
                   + (int)($vd['total_terpakai'] ?? 0)
                   + (int)($p['source'] === 'standalone' ? ($hadiahByProgram[$p['id']] ?? 0) : ($ehadiahByProgram[$p['id']] ?? 0));
            if ($score > $topScore) { $topScore = $score; $topName = $p['nama_program'] ?? null; }
            if ($score === 0 && (int)(($monthlyData[$key]['total_member_aktif'] ?? 0)) === 0 && $p['status'] === 'active') $noActivity++;
        }

        $insights = [];
        $insights[] = 'Akuisisi member baru bulan ini ' . number_format($kpiMember) . ' — ' . $fmtDelta($kpiMember, $kpiMemberPrev) . '.';
        $insights[] = 'Voucher terpakai ' . number_format($kpiTerpakai) . ' (' . $fmtDelta($kpiTerpakai, $kpiTerpakaiPrev) . '); hadiah dibagikan ' . number_format($kpiHadiah) . ' (' . $fmtDelta($kpiHadiah, $kpiHadiahPrev) . ').';
        if ($topName) $insights[] = 'Program paling aktif bulan ini: ' . $topName . '.';
        if ($noActivity > 0) $insights[] = $noActivity . ' program berstatus aktif belum mencatat realisasi apa pun bulan ini — perlu ditindaklanjuti.';
        $insights[] = 'Nilai realisasi bulan ini Rp ' . number_format($nilaiRealisasi, 0, ',', '.')
            . ($totalBudgetActive > 0 ? ' (' . $serapanPct . '% dari total budget program aktif).' : '.');

        return view('loyalty_program/print_summary', [
            'trendMonths'       => $trendMonths,
            'dailyMember'       => $dailyMember,
            'dailyTersebar'     => $dailyTersebar,
            'dailyTerpakai'     => $dailyTerpakai,
            'insights'          => $insights,
            'bulan'             => $bulan,
            'prevBulan'         => $prevBulan,
            'programs'          => $programs,
            'monthlyData'       => $monthlyData,
            'voucherByProgram'  => $voucherByProgram,
            'evoucherByProgram' => $evoucherByProgram,
            'hadiahByProgram'   => $hadiahByProgram,
            'ehadiahByProgram'  => $ehadiahByProgram,
            'prevMonthlyData'      => $prevMonthlyData,
            'prevVoucherByProgram' => $prevVoucherByProgram,
            'prevEvoucherByProgram'=> $prevEvoucherByProgram,
            'prevHadiahByProgram'  => $prevHadiahByProgram,
            'prevEhadiahByProgram' => $prevEhadiahByProgram,
            'cumulativeData'       => $cumulativeData,
            'voucherCumByProgram'  => $voucherCumByProgram,
            'evoucherCumByProgram' => $evoucherCumByProgram,
            'hadiahCumByProgram'   => $hadiahCumByProgram,
            'ehadiahCumByProgram'  => $ehadiahCumByProgram,
            'mallCounts'        => $mallCounts,
            'nilaiRealisasi'    => $nilaiRealisasi,
            'serapanPct'        => $serapanPct,
            'totalBudgetActive' => $totalBudgetActive,
            'kpiMember'         => $kpiMember,
            'kpiMemberAktif'    => $kpiMemberAktif,
            'kpiTersebar'       => $kpiTersebar,
            'kpiTerpakai'       => $kpiTerpakai,
            'kpiHadiah'         => $kpiHadiah,
            'analisaMap'        => (new LoyaltySummaryAnalysisModel())->getMapByMonth($bulan),
            'signatories'       => $this->reportSignatories(),
            'printedBy'         => $this->currentUser()['name'] ?? '',
            'printedAt'         => date('d M Y H:i'),
        ]);
    }

    /**
     * Penandatangan laporan bulanan:
     * Disusun = Dept Head dept penyusun (jabatan grade terendah di dept),
     * Diperiksa = Deputy GM (grade 3) divisi dept tsb, Mengetahui = GM.
     * Dept penyusun = dept karyawan si pencetak; bila pencetak admin / tidak
     * terhubung ke karyawan ber-dept, fallback ke dept pemilik hak edit menu
     * loyalty_main (department_menu_access) — agar laporan tetap bertanda tangan.
     */
    private function reportSignatories(): array
    {
        $db  = db_connect();
        $uid = (int) session()->get('user_id');

        $pick = fn(?array $row) => $row ? ['nama' => $row['nama'], 'jabatan' => $row['jabatan_nama']] : null;

        $me = $db->table('employees e')
            ->select('e.dept_id')
            ->where('e.user_id', $uid)
            ->get()->getRowArray();

        $deptId = (int) ($me['dept_id'] ?? 0);
        if (! $deptId) {
            $own = $db->table('department_menu_access')
                ->select('department_id')
                ->where('menu_key', 'loyalty_main')
                ->where('can_edit', 1)
                ->get(1)->getRowArray();
            $deptId = (int) ($own['department_id'] ?? 0);
        }

        $deptHead = null;
        $deputy   = null;
        if ($deptId) {
            $deptHead = $db->table('employees e')
                ->select('e.nama, j.nama AS jabatan_nama')
                ->join('jabatans j', 'j.id = e.jabatan_id')
                ->where('e.dept_id', $deptId)
                ->where('e.status', 'aktif')
                ->orderBy('j.grade', 'ASC')
                ->get(1)->getRowArray();

            $divisiId = (int) ($db->table('departments')->select('division_id')
                ->where('id', $deptId)->get()->getRowArray()['division_id'] ?? 0);
            if ($divisiId) {
                $deputy = $db->table('employees e')
                    ->select('e.nama, j.nama AS jabatan_nama')
                    ->join('jabatans j', 'j.id = e.jabatan_id')
                    ->join('departments d', 'd.id = e.dept_id', 'left')
                    ->where('j.grade', 3)
                    ->where('e.status', 'aktif')
                    ->groupStart()
                        ->where('e.division_id', $divisiId)
                        ->orWhere('d.division_id', $divisiId)
                    ->groupEnd()
                    ->get(1)->getRowArray();
            }
        }

        $gm = $db->table('employees e')
            ->select('e.nama, j.nama AS jabatan_nama')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('LOWER(j.nama) LIKE', '%general manager%')
            ->where('e.status', 'aktif')
            ->orderBy('j.grade', 'ASC')
            ->get(1)->getRowArray();

        return [
            'disusun'   => $pick($deptHead),
            'diperiksa' => $pick($deputy),
            'mengetahui'=> $pick($gm),
        ];
    }

    // ── Master Tenant ─────────────────────────────────────────────────────────

    public function indexTenants()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        return view('loyalty_program/tenants', [
            'user'     => $this->currentUser(),
            'tenants'  => (new TenantModel())->getAllWithProgramCount(),
            'canEdit'  => $this->canEditMenu('loyalty_main'),
        ]);
    }

    public function storeTenant()
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty/tenants')->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $model = new TenantModel();
        $model->insert([
            'nama'           => $post['nama'],
            'kategori'       => $post['kategori']       ?? null ?: null,
            'lantai'         => $post['lantai']         ?? null ?: null,
            'nomor_unit'     => $post['nomor_unit']     ?? null ?: null,
            'contact_person' => $post['contact_person'] ?? null ?: null,
            'no_hp'          => $post['no_hp']          ?? null ?: null,
            'email'          => $post['email']          ?? null ?: null,
            'catatan'        => $post['catatan']        ?? null ?: null,
            'status'         => 'active',
            'created_by'     => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'tenant', (string)$model->getInsertID(), $post['nama']);
        return redirect()->to('/loyalty/tenants')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function updateTenant(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty/tenants')->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $model = new TenantModel();
        ActivityLog::captureBefore($model->find($id));
        $data = [
            'nama'           => $post['nama'],
            'kategori'       => $post['kategori']       ?? null ?: null,
            'lantai'         => $post['lantai']         ?? null ?: null,
            'nomor_unit'     => $post['nomor_unit']     ?? null ?: null,
            'contact_person' => $post['contact_person'] ?? null ?: null,
            'no_hp'          => $post['no_hp']          ?? null ?: null,
            'email'          => $post['email']          ?? null ?: null,
            'catatan'        => $post['catatan']        ?? null ?: null,
        ];
        $model->update($id, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'tenant', (string)$id, $post['nama']);
        return redirect()->to('/loyalty/tenants')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function deleteTenant(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty/tenants')->with('error', 'Akses ditolak.');
        $model  = new TenantModel();
        $tenant = $model->find($id);
        if (! $tenant) return redirect()->to('/loyalty/tenants')->with('error', 'Tenant tidak ditemukan.');
        $count = db_connect()->table('loyalty_programs')->where('tenant_id', $id)->countAllResults();
        if ($count > 0) {
            return redirect()->to('/loyalty/tenants')->with('error', 'Tenant tidak bisa dihapus karena masih memiliki ' . $count . ' program terkait.');
        }
        $model->delete($id);
        ActivityLog::write('delete', 'tenant', (string)$id, $tenant['nama']);
        return redirect()->to('/loyalty/tenants')->with('success', 'Tenant berhasil dihapus.');
    }

    public function tenantDetail(int $id)
    {
        if (! $this->canViewMenu('loyalty_main')) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $model  = new TenantModel();
        $tenant = $model->find($id);
        if (! $tenant) return redirect()->to('/loyalty/tenants')->with('error', 'Tenant tidak ditemukan.');
        $programs = $model->getPrograms($id);
        return view('loyalty_program/tenant_detail', [
            'user'     => $this->currentUser(),
            'tenant'   => $tenant,
            'programs' => $programs,
            'canEdit'  => $this->canEditMenu('loyalty_main'),
        ]);
    }

    // ── Detail per program ────────────────────────────────────────────────────

    public function detail(string $source, int $id)
    {
        if (! in_array($source, ['s', 'e'])) return redirect()->to('/loyalty');

        if ($source === 's') {
            $prog = (new LoyaltyProgramModel())->find($id);
            if (! $prog) return redirect()->to('/loyalty')->with('error', 'Program tidak ditemukan.');
            $prog['source']     = 'standalone';
            $prog['event_name'] = null;
            $prog['event_mall'] = null;

            $realModel  = new LoyaltyRealisasiModel();
            $allEntries = $realModel->getByProgram($id);
            $rMonthly   = $realModel->getAllMonthlyTotals([$id]);

            $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $id)->orderBy('nilai_voucher', 'DESC')->findAll();
            $vItemIds     = array_column($voucherItems, 'id');
            $voucherReal  = (new LoyaltyVoucherRealisasiModel())->getGroupedByItems($vItemIds);
            $vMonthly     = (new LoyaltyVoucherRealisasiModel())->getAllMonthlyTotals($vItemIds);

            $hadiahItems  = (new LoyaltyHadiahItemModel())->where('program_id', $id)->orderBy('id')->findAll();
            $hItemIds     = array_column($hadiahItems, 'id');
            $hadiahReal   = (new LoyaltyHadiahRealisasiModel())->getGroupedByItems($hItemIds);
            $hMonthly     = (new LoyaltyHadiahRealisasiModel())->getAllMonthlyTotals($hItemIds);
        } else {
            $db  = \Config\Database::connect();
            $prog = $db->table('event_loyalty_programs elp')
                ->select('elp.*, e.name as event_name, e.mall as event_mall')
                ->join('events e', 'e.id = elp.event_id')
                ->where('elp.id', $id)
                ->get()->getRowArray();
            if (! $prog) return redirect()->to('/loyalty')->with('error', 'Program tidak ditemukan.');
            $prog['source'] = 'event';
            $prog['status'] = 'active';

            $realModel  = new EventLoyaltyRealisasiModel();
            $allEntries = $realModel->getByProgram($id);
            $rMonthly   = $realModel->getAllMonthlyTotals([$id]);

            $voucherItems = (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->orderBy('nilai_voucher', 'DESC')->findAll();
            $vItemIds     = array_column($voucherItems, 'id');
            $voucherReal  = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($vItemIds);
            $vMonthly     = (new EventLoyaltyVoucherRealisasiModel())->getAllMonthlyTotals($vItemIds);

            $hadiahItems  = (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->orderBy('id')->findAll();
            $hItemIds     = array_column($hadiahItems, 'id');
            $hadiahReal   = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($hItemIds);
            $hMonthly     = (new EventLoyaltyHadiahRealisasiModel())->getAllMonthlyTotals($hItemIds);
        }

        // All-time KPIs
        $totalMember      = array_sum(array_column($allEntries, 'jumlah'));
        $totalMemberAktif = array_sum(array_map(fn($r) => (int)($r['member_aktif'] ?? 0), $allEntries));
        $totalDiterbitkan = array_sum(array_column($voucherItems, 'total_diterbitkan'));
        $totalTersebar    = 0;
        $totalTerpakai    = 0;
        foreach ($voucherReal as $vd) {
            $totalTersebar += (int)$vd['total_tersebar'];
            $totalTerpakai += (int)$vd['total_terpakai'];
        }
        $totalHadiah = 0;
        $totalStok   = array_sum(array_column($hadiahItems, 'stok'));
        foreach ($hadiahReal as $hd) { $totalHadiah += (int)$hd['total']; }

        // Merge monthly trend
        $emptyRow = ['bulan' => '', 'total_member' => 0, 'total_aktif' => 0, 'total_tersebar' => 0, 'total_terpakai' => 0, 'total_hadiah' => 0];
        $trend = [];
        foreach ($rMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_member'] += (int)$row['total_jumlah'];
            $trend[$m]['total_aktif']  += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($vMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $trend[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($hMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        ksort($trend);

        return view('loyalty_program/detail', [
            'user'             => $this->currentUser(),
            'source'           => $source,
            'prog'             => $prog,
            'allEntries'       => $allEntries,
            'voucherItems'     => $voucherItems,
            'voucherReal'      => $voucherReal,
            'hadiahItems'      => $hadiahItems,
            'hadiahReal'       => $hadiahReal,
            'totalMember'      => $totalMember,
            'totalMemberAktif' => $totalMemberAktif,
            'totalDiterbitkan' => $totalDiterbitkan,
            'totalTersebar'    => $totalTersebar,
            'totalTerpakai'    => $totalTerpakai,
            'totalHadiah'      => $totalHadiah,
            'totalStok'        => $totalStok,
            'trend'            => array_values($trend),
        ]);
    }
}
