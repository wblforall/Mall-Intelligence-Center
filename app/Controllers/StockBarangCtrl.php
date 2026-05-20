<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\StockBarangModel;
use App\Models\StockBarangLogModel;

class StockBarangCtrl extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $model  = new StockBarangModel();
        $logMdl = new StockBarangLogModel();
        $items  = $model->getAll();

        foreach ($items as &$item) {
            $item['log'] = $logMdl->getByBarang((int)$item['id'], 10);
        }

        return view('stock/barang/index', [
            'user'  => $this->currentUser(),
            'items' => $items,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/barang')->with('error', 'Akses ditolak.');
        }

        $post   = $this->request->getPost();
        $clean  = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $stok   = (int)($post['stok_awal'] ?? 0);
        $model  = new StockBarangModel();
        $userId = $this->currentUser()['id'];

        $id = $model->insert([
            'nama_barang'   => $post['nama_barang'],
            'satuan'        => $post['satuan'] ?? 'pcs',
            'nilai_satuan'  => $clean($post['nilai_satuan']),
            'stok_awal'     => $stok,
            'stok_tersedia' => $stok,
            'catatan'       => $post['catatan'] ?? null,
            'created_by'    => $userId,
        ]);

        if ($stok > 0) {
            (new StockBarangLogModel())->writeMasuk((int)$id, $stok, 0, date('Y-m-d'), $userId, 'Stok awal');
        }

        ActivityLog::write('create', 'stock_barang', (string)$id, $post['nama_barang']);
        return redirect()->to('/stock/barang')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/barang')->with('error', 'Akses ditolak.');
        }

        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $model = new StockBarangModel();

        $model->update($id, [
            'nama_barang'  => $post['nama_barang'],
            'satuan'       => $post['satuan'] ?? 'pcs',
            'nilai_satuan' => $clean($post['nilai_satuan']),
            'catatan'      => $post['catatan'] ?? null,
        ]);

        ActivityLog::write('update', 'stock_barang', (string)$id, $post['nama_barang']);
        return redirect()->to('/stock/barang')->with('success', 'Barang berhasil diupdate.');
    }

    public function tambahStok(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/barang')->with('error', 'Akses ditolak.');
        }

        $post   = $this->request->getPost();
        $jumlah = (int)($post['jumlah'] ?? 0);
        if ($jumlah <= 0) {
            return redirect()->to('/stock/barang')->with('error', 'Jumlah harus lebih dari 0.');
        }

        $model  = new StockBarangModel();
        $barang = $model->find($id);
        if (! $barang) return redirect()->to('/stock/barang')->with('error', 'Barang tidak ditemukan.');

        $userId = $this->currentUser()['id'];
        $model->restoreStock($id, $jumlah);
        (new StockBarangLogModel())->writeMasuk($id, $jumlah, (int)$barang['stok_tersedia'], date('Y-m-d'), $userId, $post['catatan'] ?? null);

        ActivityLog::write('update', 'stock_barang', (string)$id, "Tambah stok {$barang['nama_barang']} +{$jumlah}");
        return redirect()->to('/stock/barang')->with('success', "Stok {$barang['nama_barang']} berhasil ditambah {$jumlah} {$barang['satuan']}.");
    }

    public function storeRealisasi(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/barang')->with('error', 'Akses ditolak.');
        }

        $post   = $this->request->getPost();
        $jumlah = (int)($post['jumlah'] ?? 0);
        if ($jumlah <= 0) {
            return redirect()->to('/stock/barang')->with('error', 'Jumlah harus lebih dari 0.');
        }

        $model  = new StockBarangModel();
        $barang = $model->find($id);
        if (! $barang) return redirect()->to('/stock/barang')->with('error', 'Barang tidak ditemukan.');

        if ($jumlah > (int)$barang['stok_tersedia']) {
            return redirect()->to('/stock/barang')->with('error', 'Jumlah melebihi stok tersedia.');
        }

        $userId  = $this->currentUser()['id'];
        $tanggal = $post['tanggal'] ?? date('Y-m-d');
        $catatan = trim($post['catatan'] ?? '') ?: 'Distribusi manual';

        $model->deductStock($id, $jumlah);
        (new StockBarangLogModel())->writeKeluar($id, $jumlah, (int)$barang['stok_tersedia'], 'manual', 0, $tanggal, $userId, $catatan);

        ActivityLog::write('update', 'stock_barang', (string)$id, "Distribusi manual {$barang['nama_barang']} -{$jumlah}");
        return redirect()->to('/stock/barang')->with('success', "{$jumlah} {$barang['satuan']} {$barang['nama_barang']} berhasil dikeluarkan.");
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) {
            return redirect()->to('/stock/barang')->with('error', 'Akses ditolak.');
        }

        $model  = new StockBarangModel();
        $barang = $model->find($id);
        if (! $barang) return redirect()->to('/stock/barang')->with('error', 'Barang tidak ditemukan.');

        (new StockBarangLogModel())->where('barang_id', $id)->delete();
        $model->delete($id);

        ActivityLog::write('delete', 'stock_barang', (string)$id, $barang['nama_barang']);
        return redirect()->to('/stock/barang')->with('success', 'Barang dihapus.');
    }
}
