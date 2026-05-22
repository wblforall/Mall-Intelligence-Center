<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherKodeModel;

class StockVoucherCtrl extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $batchModel = new StockVoucherBatchModel();
        $kodeModel  = new StockVoucherKodeModel();
        $batches    = $batchModel->getAll();

        foreach ($batches as &$batch) {
            $batch['kodes'] = $kodeModel->getByBatch((int)$batch['id']);
        }

        return view('stock/voucher/index', [
            'user'    => $this->currentUser(),
            'batches' => $batches,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $post   = $this->request->getPost();
        $clean  = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $userId = $this->currentUser()['id'];

        $id = (new StockVoucherBatchModel())->insert([
            'nama_voucher'  => $post['nama_voucher'],
            'nilai_voucher' => $clean($post['nilai_voucher']),
            'expired_date'  => ($post['expired_date'] ?? '') ?: null,
            'total_kode'    => 0,
            'sisa_kode'     => 0,
            'catatan'       => $post['catatan'] ?? null,
            'created_by'    => $userId,
        ]);

        ActivityLog::write('create', 'stock_voucher_batch', (string)$id, $post['nama_voucher']);
        return redirect()->to('/stock/voucher')->with('success', 'Batch voucher berhasil dibuat.');
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $post       = $this->request->getPost();
        $clean      = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $batchModel2 = new StockVoucherBatchModel();
        ActivityLog::captureBefore($batchModel2->find($id));
        $batchData = [
            'nama_voucher'  => $post['nama_voucher'],
            'nilai_voucher' => $clean($post['nilai_voucher']),
            'expired_date'  => ($post['expired_date'] ?? '') ?: null,
            'catatan'       => $post['catatan'] ?? null,
        ];
        $batchModel2->update($id, $batchData);
        ActivityLog::captureAfter($batchData);

        ActivityLog::write('update', 'stock_voucher_batch', (string)$id, $post['nama_voucher']);
        return redirect()->to('/stock/voucher')->with('success', 'Batch voucher diupdate.');
    }

    public function importKode(int $batchId)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $batchModel = new StockVoucherBatchModel();
        $batch      = $batchModel->find($batchId);
        if (! $batch) return redirect()->to('/stock/voucher')->with('error', 'Batch tidak ditemukan.');

        $post     = $this->request->getPost();
        $raw      = $post['kodes'] ?? '';
        $kodes    = array_unique(array_filter(array_map('trim', explode("\n", $raw))));
        $kodeModel = new StockVoucherKodeModel();

        $inserted = $kodeModel->importKodes($batchId, $kodes);

        // Update total_kode and sisa_kode
        $total     = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->countAllResults();
        $sisa      = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->where('status', 'available')->countAllResults();
        ActivityLog::captureBefore($batchModel->find($batchId));
        $importData = ['total_kode' => $total, 'sisa_kode' => $sisa];
        $batchModel->update($batchId, $importData);
        ActivityLog::captureAfter($importData);

        ActivityLog::write('update', 'stock_voucher_batch', (string)$batchId, "Import {$inserted} kode ke batch {$batch['nama_voucher']}");
        return redirect()->to('/stock/voucher')->with('success', "{$inserted} kode berhasil diimport.");
    }

    public function deleteKode(int $batchId, int $kodeId)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $kode = (new StockVoucherKodeModel())->find($kodeId);
        if (! $kode || $kode['batch_id'] != $batchId) {
            return redirect()->to('/stock/voucher')->with('error', 'Kode tidak ditemukan.');
        }
        if ($kode['status'] === 'assigned') {
            return redirect()->to('/stock/voucher')->with('error', 'Kode sudah diassign, tidak bisa dihapus.');
        }

        (new StockVoucherKodeModel())->delete($kodeId);

        $batchModel = new StockVoucherBatchModel();
        $total = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->countAllResults();
        $sisa  = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->where('status', 'available')->countAllResults();
        $batchModel->update($batchId, ['total_kode' => $total, 'sisa_kode' => $sisa]);

        return redirect()->to('/stock/voucher')->with('success', 'Kode dihapus.');
    }

    public function distributeKode(int $batchId, int $kodeId)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $kodeModel = new StockVoucherKodeModel();
        $kode      = $kodeModel->find($kodeId);
        if (! $kode || (int)$kode['batch_id'] !== $batchId) {
            return redirect()->to('/stock/voucher')->with('error', 'Kode tidak ditemukan.');
        }
        if ($kode['status'] !== 'available') {
            return redirect()->to('/stock/voucher')->with('error', 'Kode sudah digunakan.');
        }

        $namaPenerima = trim($this->request->getPost('nama_penerima') ?? '');
        $kodeModel->assignManual($kodeId, $namaPenerima);
        (new StockVoucherBatchModel())->deductSisa($batchId);
        // stok_reserved tidak diubah — distribusi manual bukan realisasi dari reservasi program

        ActivityLog::write('update', 'stock_voucher_batch', (string)$batchId, "Distribusi manual kode {$kode['kode']}");
        return redirect()->to('/stock/voucher')->with('success', "Kode {$kode['kode']} berhasil didistribusikan.");
    }

    public function deleteBatch(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak.');
        }

        $batch = (new StockVoucherBatchModel())->find($id);
        if (! $batch) return redirect()->to('/stock/voucher')->with('error', 'Batch tidak ditemukan.');

        $assigned = db_connect()->table('stock_voucher_kode')->where('batch_id', $id)->where('status', 'assigned')->countAllResults();
        if ($assigned > 0) {
            return redirect()->to('/stock/voucher')->with('error', 'Batch masih memiliki kode yang sudah diassign.');
        }

        (new StockVoucherKodeModel())->where('batch_id', $id)->delete();
        (new StockVoucherBatchModel())->delete($id);

        ActivityLog::write('delete', 'stock_voucher_batch', (string)$id, $batch['nama_voucher']);
        return redirect()->to('/stock/voucher')->with('success', 'Batch voucher dihapus.');
    }

    public function getAvailableKodes(int $batchId)
    {
        $kodes = (new StockVoucherKodeModel())->getAvailableByBatch($batchId);
        return $this->response->setJSON($kodes);
    }
}
