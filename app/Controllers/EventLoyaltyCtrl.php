<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Libraries\ActivityLog;
use App\Models\StockBarangModel;
use App\Models\StockBarangLogModel;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherKodeModel;
use App\Models\EventLoyaltyModel;
use App\Models\EventLoyaltyRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventBudgetModel;
use App\Models\EventCompletionModel;

class EventLoyaltyCtrl extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('loyalty')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $programs   = (new EventLoyaltyModel())->getByEvent($eventId);
        $realisasi  = (new EventLoyaltyRealisasiModel())->getGroupedByEvent($eventId);
        $programIds = array_column($programs, 'id');

        $hadiahItems = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);
        $allHadiahIds = [];
        foreach ($hadiahItems as $items) { foreach ($items as $item) { $allHadiahIds[] = $item['id']; } }
        $hadiahRealisasi = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        $voucherItems = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $allVoucherIds = [];
        foreach ($voucherItems as $items) { foreach ($items as $item) { $allVoucherIds[] = $item['id']; } }
        $voucherRealisasi = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);

        $totalBudgetProgram = array_sum(array_column($programs, 'budget'));

        return view('loyalty/index', [
            'user'               => $this->currentUser(),
            'event'              => $event,
            'programs'           => $programs,
            'realisasi'          => $realisasi,
            'hadiahItems'        => $hadiahItems,
            'hadiahRealisasi'    => $hadiahRealisasi,
            'voucherItems'       => $voucherItems,
            'voucherRealisasi'   => $voucherRealisasi,
            'totalBudgetProgram' => $totalBudgetProgram,
            'stockBarang'        => (new StockBarangModel())->getAll(),
            'stockVoucherBatch'  => (new StockVoucherBatchModel())->getAvailable(),
            'completion'         => ($completion = (new EventCompletionModel())->getByEvent($eventId)['loyalty'] ?? null),
            'canEdit'            => $this->canEditMenu('loyalty') && ! $completion,
        ]);
    }

    // ── Program CRUD ─────────────────────────────────────────────────────────

    public function storeProgram(int $eventId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $pid = (new EventLoyaltyModel())->insert([
            'event_id'     => $eventId,
            'nama_program' => $post['nama_program'],
            'mekanisme'    => $post['mekanisme'] ?? null,
            'catatan'      => $post['catatan'] ?? null,
            'budget'       => 0,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty', (string)$pid, $post['nama_program'], ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty")->with('success', 'Program loyalty berhasil ditambahkan.');
    }

    public function updateProgram(int $eventId, int $id)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post          = $this->request->getPost();
        $loyaltyModel  = new EventLoyaltyModel();
        ActivityLog::captureBefore($loyaltyModel->find($id));
        $loyaltyData = [
            'nama_program'   => $post['nama_program'],
            'mekanisme'      => $post['mekanisme'] ?? null,
            'target_peserta'      => ($post['target_peserta'] ?? null) ?: null,
            'target_member_aktif' => ($post['target_member_aktif'] ?? null) ?: null,
            'catatan'        => $post['catatan'] ?? null,
        ];
        $loyaltyModel->update($id, $loyaltyData);
        ActivityLog::captureAfter($loyaltyData);
        ActivityLog::write('update', 'loyalty', (string)$id, $post['nama_program'], ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$id}")->with('success', 'Program berhasil diperbarui.');
    }

    public function deleteProgram(int $eventId, int $id)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $prog = (new EventLoyaltyModel())->find($id);

        $db->transStart();

        (new EventLoyaltyRealisasiModel())->where('program_id', $id)->delete();

        $hadiahItems = (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->findAll();
        foreach ($hadiahItems as $item) {
            (new EventLoyaltyHadiahRealisasiModel())->where('item_id', $item['id'])->delete();
        }
        (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->delete();

        $voucherItems = (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->findAll();
        foreach ($voucherItems as $item) {
            (new EventLoyaltyVoucherRealisasiModel())->where('item_id', $item['id'])->delete();
        }
        (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->delete();

        (new EventLoyaltyModel())->delete($id);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Gagal menghapus program. Silakan coba lagi.');
        }

        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('delete', 'loyalty', (string)$id, $prog['nama_program'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty")->with('success', 'Program berhasil dihapus.');
    }

    // ── Member realisasi ──────────────────────────────────────────────────────

    public function storeRealisasi(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        $jumlah = (int)($post['jumlah'] ?? 0);
        (new EventLoyaltyRealisasiModel())->insert([
            'program_id'   => $programId,
            'event_id'     => $eventId,
            'tanggal'      => $post['tanggal'],
            'jumlah'       => $jumlah,
            'member_aktif' => (int)($post['member_aktif'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_realisasi', (string)$programId, 'Realisasi Member', ['event_id' => $eventId, 'tanggal' => $post['tanggal'], 'jumlah' => $jumlah]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi member disimpan.');
    }

    public function deleteRealisasi(int $eventId, int $programId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyRealisasiModel())->delete($rid);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── e-Voucher items ───────────────────────────────────────────────────────

    public function storeVoucherItem(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $penyerapan = ($post['target_penyerapan'] ?? '') !== '' ? (float)$post['target_penyerapan'] : null;
        $batchId     = ($post['batch_id'] ?? '') !== '' ? (int)$post['batch_id'] : null;
        $namaVoucher  = $post['nama_voucher'] ?? null;
        $nilaiVoucher = $clean($post['nilai_voucher']);
        if ($batchId) {
            $batch        = (new StockVoucherBatchModel())->find($batchId);
            $namaVoucher  = $batch['nama_voucher'] ?? $namaVoucher;
            $nilaiVoucher = $batch['nilai_voucher'] ?? $nilaiVoucher;
        }
        (new EventLoyaltyVoucherItemModel())->insert([
            'program_id'        => $programId,
            'batch_id'          => $batchId,
            'nama_voucher'      => $namaVoucher,
            'nilai_voucher'     => $nilaiVoucher,
            'total_diterbitkan' => $batchId ? ($batch['sisa_kode'] ?? 0) : (int)($post['total_diterbitkan'] ?? 0),
            'target_penyerapan' => $penyerapan,
            'catatan'           => $post['catatan'] ?? null,
            'created_by'        => $this->currentUser()['id'],
        ]);
        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('create', 'loyalty_voucher', (string)$programId, $post['nama_voucher'], ['event_id' => $eventId, 'nilai' => $post['nilai_voucher'] ?? 0, 'qty' => $post['total_diterbitkan'] ?? 0]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Voucher ditambahkan.');
    }

    public function deleteVoucherItem(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyVoucherRealisasiModel())->where('item_id', $itemId)->delete();
        (new EventLoyaltyVoucherItemModel())->delete($itemId);
        $this->syncLoyaltyBudget($eventId);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Voucher dihapus.');
    }

    // Upload foto bukti realisasi (wajib barang & voucher fisik). Return [filename|null, error|null].
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

    public function storeVoucherRealisasi(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        [$fotoName, $fotoErr] = $this->uploadRealisasiFoto();
        if ($fotoErr) return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('error', $fotoErr);
        $userId  = $this->currentUser()['id'];
        $kodeId  = ($post['kode_id'] ?? '') !== '' ? (int)$post['kode_id'] : null;
        $vrModel = new EventLoyaltyVoucherRealisasiModel();
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
        if ($kodeId) {
            $item = (new EventLoyaltyVoucherItemModel())->find($itemId);
            (new StockVoucherKodeModel())->assign($kodeId, $post['nama_penerima'] ?? '', 'event', $programId, $itemId, (int)$rid);
            if (! empty($item['batch_id'])) (new StockVoucherBatchModel())->deductSisa((int)$item['batch_id']);
        }
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi voucher disimpan.');
    }

    public function deleteVoucherRealisasi(int $eventId, int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $vrModel = new EventLoyaltyVoucherRealisasiModel();
        $entry   = $vrModel->find($rid);
        $vrModel->delete($rid);
        if ($entry && ! empty($entry['foto'])) { $f = FCPATH . 'uploads/loyalty-realisasi/' . $entry['foto']; if (is_file($f)) @unlink($f); }
        if ($entry && ! empty($entry['kode_id'])) {
            (new StockVoucherKodeModel())->unassign((int)$entry['kode_id']);
            $item = (new EventLoyaltyVoucherItemModel())->find($itemId);
            if (! empty($item['batch_id'])) (new StockVoucherBatchModel())->restoreSisa((int)$item['batch_id']);
        }
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── Hadiah barang items ───────────────────────────────────────────────────

    public function storeHadiahItem(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $barangId    = ($post['barang_id'] ?? '') !== '' ? (int)$post['barang_id'] : null;
        $batchId     = ($post['batch_id']  ?? '') !== '' ? (int)$post['batch_id']  : null;
        $namaHadiah  = $post['nama_hadiah'] ?? null;
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
        $hiModel = new EventLoyaltyHadiahItemModel();
        $hiModel->insert([
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
        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('create', 'loyalty_hadiah', (string)$programId, $namaHadiah, ['event_id' => $eventId, 'stok' => $stok, 'nilai_satuan' => $nilaiSatuan]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Item hadiah ditambahkan.');
    }

    public function updateHadiahItem(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $hiModel = new EventLoyaltyHadiahItemModel();
        $old = $hiModel->find($itemId);
        if (! $old) return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('error', 'Item tidak ditemukan.');
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
        $hiModel->update($itemId, $hiData);
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
        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('update', 'loyalty_hadiah', (string)$itemId, $namaHadiah, ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Item hadiah berhasil diperbarui.');
    }

    public function deleteHadiahItem(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $item = (new EventLoyaltyHadiahItemModel())->find($itemId);
        if ($item) {
            $realized = (int)(db_connect()->table('event_loyalty_hadiah_realisasi')
                ->selectSum('jumlah_dibagikan')->where('item_id', $itemId)
                ->get()->getRowArray()['jumlah_dibagikan'] ?? 0);
            $unrealized = max(0, (int)$item['stok'] - $realized);
            if ($unrealized > 0 && ! empty($item['barang_id'])) (new StockBarangModel())->releaseStock((int)$item['barang_id'], $unrealized);
            if ($unrealized > 0 && ! empty($item['batch_id']))  (new StockVoucherBatchModel())->releaseSisa((int)$item['batch_id'], $unrealized);
        }
        (new EventLoyaltyHadiahRealisasiModel())->where('item_id', $itemId)->delete();
        (new EventLoyaltyHadiahItemModel())->delete($itemId);
        $this->syncLoyaltyBudget($eventId);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Item hadiah dihapus.');
    }

    public function storeHadiahRealisasi(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        [$fotoName, $fotoErr] = $this->uploadRealisasiFoto();
        if ($fotoErr) return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('error', $fotoErr);
        $userId  = $this->currentUser()['id'];
        $jumlah  = (int)($post['jumlah_dibagikan'] ?? 0);
        $kodeId  = ($post['kode_id'] ?? '') !== '' ? (int)$post['kode_id'] : null;
        $hrModel = new EventLoyaltyHadiahRealisasiModel();
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
        $item = (new EventLoyaltyHadiahItemModel())->find($itemId);
        if ($kodeId) {
            (new StockVoucherKodeModel())->assign($kodeId, $post['nama_penerima'] ?? '', 'event', $programId, $itemId, (int)$rid);
            if (! empty($item['batch_id'])) {
                (new StockVoucherBatchModel())->deductSisa((int)$item['batch_id']);
                (new StockVoucherBatchModel())->releaseSisa((int)$item['batch_id']); // reservation terpenuhi
            }
        } elseif (! empty($item['barang_id']) && $jumlah > 0) {
            $barangModel = new StockBarangModel();
            $barang      = $barangModel->find((int)$item['barang_id']);
            $barangModel->deductStock((int)$item['barang_id'], $jumlah);
            $barangModel->releaseStock((int)$item['barang_id'], $jumlah);
            (new StockBarangLogModel())->writeKeluar((int)$item['barang_id'], $jumlah, (int)$barang['stok_tersedia'], 'event_loyalty', $programId, $post['tanggal'], $userId, $post['catatan'] ?? null);
        }
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi disimpan.');
    }

    public function deleteHadiahRealisasi(int $eventId, int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $hrModel = new EventLoyaltyHadiahRealisasiModel();
        $entry   = $hrModel->find($rid);
        $hrModel->delete($rid);
        if ($entry) {
            if (! empty($entry['foto'])) { $f = FCPATH . 'uploads/loyalty-realisasi/' . $entry['foto']; if (is_file($f)) @unlink($f); }
            $item = (new EventLoyaltyHadiahItemModel())->find($itemId);
            if (! empty($entry['kode_id'])) {
                (new StockVoucherKodeModel())->unassign((int)$entry['kode_id']);
                if (! empty($item['batch_id'])) {
                    (new StockVoucherBatchModel())->restoreSisa((int)$item['batch_id']);
                    (new StockVoucherBatchModel())->reserveSisa((int)$item['batch_id']); // restore reservation
                }
            } elseif (! empty($item['barang_id']) && (int)($entry['jumlah_dibagikan'] ?? 0) > 0) {
                $jumlah      = (int)$entry['jumlah_dibagikan'];
                $barangModel = new StockBarangModel();
                $barang      = $barangModel->find((int)$item['barang_id']);
                $barangModel->restoreStock((int)$item['barang_id'], $jumlah);
                $barangModel->reserveStock((int)$item['barang_id'], $jumlah);
                (new StockBarangLogModel())->writeMasuk((int)$item['barang_id'], $jumlah, (int)$barang['stok_tersedia'], $entry['tanggal'], $this->currentUser()['id'], 'Rollback realisasi #'.$rid);
            }
        }
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── Budget sync ───────────────────────────────────────────────────────────

    private function syncLoyaltyBudget(int $eventId): void
    {
        $programs   = (new EventLoyaltyModel())->getByEvent($eventId);
        $programIds = array_column($programs, 'id');

        $voucherItems = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems  = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);

        $loyaltyModel = new EventLoyaltyModel();
        $total = 0;
        foreach ($programs as $prog) {
            $pid        = $prog['id'];
            $progBudget = 0;
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $progBudget += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher'];
            }
            foreach ($hadiahItems[$pid] ?? [] as $hi) {
                $progBudget += (int)$hi['stok'] * (int)$hi['nilai_satuan'];
            }
            $loyaltyModel->update($pid, ['budget' => $progBudget]);
            $total += $progBudget;
        }

        $deptId = (int)session()->get('dept_id');
        if (! $deptId) return;

        $model = new EventBudgetModel();
        $model->where('event_id', $eventId)->where('department_id', $deptId)->delete();
        if ($total > 0) {
            $model->insert([
                'event_id'      => $eventId,
                'department_id' => $deptId,
                'kategori'      => 'Program Loyalty',
                'keterangan'    => 'Total dari ' . count($programs) . ' program',
                'jumlah'        => $total,
                'created_by'    => $this->currentUser()['id'],
            ]);
        }
    }
}
