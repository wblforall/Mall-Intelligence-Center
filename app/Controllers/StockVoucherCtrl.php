<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherKodeModel;
use App\Models\StockVoucherLogModel;

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

        $stIds = []; $evIds = [];
        foreach ($batches as &$batch) {
            $batch['kodes'] = $kodeModel->getByBatch((int)$batch['id']);
            foreach ($batch['kodes'] as $k) {
                if (! $k['program_id']) continue;
                if ($k['program_type'] === 'standalone') $stIds[] = (int)$k['program_id'];
                elseif ($k['program_type'] === 'event')  $evIds[] = (int)$k['program_id'];
            }
        }
        unset($batch);

        // Map nama program (standalone + event) untuk ditampilkan, bukan kode/id
        $progNames = [];
        if ($stIds) {
            foreach ((new \App\Models\LoyaltyProgramModel())->whereIn('id', array_unique($stIds))->findAll() as $p) {
                $progNames['standalone_' . $p['id']] = $p['nama_program'];
            }
        }
        if ($evIds) {
            foreach ((new \App\Models\EventLoyaltyModel())->whereIn('id', array_unique($evIds))->findAll() as $p) {
                $progNames['event_' . $p['id']] = $p['nama_program'];
            }
        }

        return view('stock/voucher/index', [
            'user'        => $this->currentUser(),
            'batches'     => $batches,
            'canDeassign' => $this->can('can_deassign_voucher'),
            'progNames'   => $progNames,
            'assignedBy'  => (new StockVoucherLogModel())->getAssignerByKode(),
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

        $sisaSebelum = (int)$batch['sisa_kode'];
        $inserted = $kodeModel->importKodes($batchId, $kodes);
        if ($inserted > 0) {
            (new StockVoucherLogModel())->record($batchId, 'masuk', $inserted, $sisaSebelum, 'import', null, $this->currentUser()['id'], "Import {$inserted} kode");
        }

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

        $batchModel  = new StockVoucherBatchModel();
        $sisaSebelum = (int)($batchModel->find($batchId)['sisa_kode'] ?? 0);

        (new StockVoucherKodeModel())->delete($kodeId);

        $total = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->countAllResults();
        $sisa  = db_connect()->table('stock_voucher_kode')->where('batch_id', $batchId)->where('status', 'available')->countAllResults();
        $batchModel->update($batchId, ['total_kode' => $total, 'sisa_kode' => $sisa]);

        // kode available yang dihapus mengurangi stok tersedia
        (new StockVoucherLogModel())->record($batchId, 'keluar', 1, $sisaSebelum, 'delete', $kodeId, $this->currentUser()['id'], "Hapus kode {$kode['kode']}");

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
        $batchModel   = new StockVoucherBatchModel();
        $sisaSebelum  = (int)($batchModel->find($batchId)['sisa_kode'] ?? 0);
        $kodeModel->assignManual($kodeId, $namaPenerima);
        $batchModel->deductSisa($batchId);
        (new StockVoucherLogModel())->record($batchId, 'keluar', 1, $sisaSebelum, 'manual', $kodeId, $this->currentUser()['id'], 'Distribusi manual' . ($namaPenerima ? " ke {$namaPenerima}" : ''));
        // stok_reserved tidak diubah — distribusi manual bukan realisasi dari reservasi program

        ActivityLog::captureBefore(['status' => $kode['status'], 'nama_penerima' => $kode['nama_penerima'] ?? '']);
        ActivityLog::captureAfter(['status'  => 'assigned',      'nama_penerima' => $namaPenerima]);
        ActivityLog::write('update', 'stock_voucher_batch', (string)$batchId, "Distribusi manual kode {$kode['kode']}");
        return redirect()->to('/stock/voucher')->with('success', "Kode {$kode['kode']} berhasil didistribusikan.");
    }

    // Batalkan distribusi MANUAL — kode kembali ke stok tersedia (mis. mau dialokasikan via program)
    public function deassignKode(int $batchId, int $kodeId)
    {
        if (! $this->can('can_deassign_voucher')) {
            return redirect()->to('/stock/voucher')->with('error', 'Akses ditolak. Anda tidak memiliki izin membatalkan distribusi voucher.');
        }

        $kodeModel = new StockVoucherKodeModel();
        $kode      = $kodeModel->find($kodeId);
        if (! $kode || (int)$kode['batch_id'] !== $batchId) {
            return redirect()->to('/stock/voucher')->with('error', 'Kode tidak ditemukan.');
        }
        if ($kode['status'] !== 'assigned' || $kode['program_type'] !== 'manual') {
            return redirect()->to('/stock/voucher')->with('error', 'Hanya distribusi manual yang bisa dibatalkan di sini. Untuk kode dari program, batalkan lewat realisasi program terkait.');
        }

        $batchModel  = new StockVoucherBatchModel();
        $sisaSebelum = (int)($batchModel->find($batchId)['sisa_kode'] ?? 0);
        $kodeModel->unassign($kodeId);
        $batchModel->restoreSisa($batchId); // kembalikan ke stok tersedia
        (new StockVoucherLogModel())->record($batchId, 'retur', 1, $sisaSebelum, 'deassign', $kodeId, $this->currentUser()['id'], "Batal distribusi kode {$kode['kode']}");

        ActivityLog::captureBefore(['status' => 'assigned',  'nama_penerima' => $kode['nama_penerima'] ?? '']);
        ActivityLog::captureAfter(['status'  => 'available', 'nama_penerima' => '']);
        ActivityLog::write('update', 'stock_voucher_batch', (string)$batchId, "Batal distribusi manual kode {$kode['kode']}");
        return redirect()->to('/stock/voucher')->with('success', "Distribusi kode {$kode['kode']} dibatalkan — kembali ke stok tersedia.");
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
