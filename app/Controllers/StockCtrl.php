<?php

namespace App\Controllers;

use App\Models\StockBarangModel;
use App\Models\StockBarangLogModel;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherLogModel;

class StockCtrl extends BaseController
{
    private function period(): array
    {
        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-t');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-t');
        return [$from, $to];
    }

    // Summary gabungan stok fisik (barang + voucher)
    public function summary()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        [$from, $to] = $this->period();

        $barang   = (new StockBarangModel())->getAll();
        $bSum     = (new StockBarangLogModel())->getPeriodSummary($from, $to);
        foreach ($barang as &$b) {
            $b['masuk']  = (int)($bSum[$b['id']]['masuk']  ?? 0);
            $b['keluar'] = (int)($bSum[$b['id']]['keluar'] ?? 0);
            $b['nilai_stok'] = (int)$b['stok_tersedia'] * (int)$b['nilai_satuan'];
        }
        unset($b);

        $batches = (new StockVoucherBatchModel())->getAll();
        $vSum    = (new StockVoucherLogModel())->getPeriodSummary($from, $to);
        foreach ($batches as &$v) {
            $v['masuk']  = (int)($vSum[$v['id']]['masuk']  ?? 0);
            $v['keluar'] = (int)($vSum[$v['id']]['keluar'] ?? 0);
            $v['retur']  = (int)($vSum[$v['id']]['retur']  ?? 0);
            $v['nilai_stok'] = (int)$v['sisa_kode'] * (int)$v['nilai_voucher'];
        }
        unset($v);

        return view('stock/summary', [
            'user'           => $this->currentUser(),
            'from'           => $from,
            'to'             => $to,
            'barang'         => $barang,
            'batches'        => $batches,
            'totalNilaiBarang'  => array_sum(array_column($barang, 'nilai_stok')),
            'totalNilaiVoucher' => array_sum(array_column($batches, 'nilai_stok')),
        ]);
    }

    // Kartu stok satu barang
    public function kartuBarang(int $id)
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $barang = (new StockBarangModel())->find($id);
        if (! $barang) return redirect()->to('/stock/summary')->with('error', 'Barang tidak ditemukan.');
        [$from, $to] = $this->period();

        $entries = (new StockBarangLogModel())->getByBarangAsc($id, $from, $to);
        return view('stock/kartu_barang', [
            'user'    => $this->currentUser(),
            'barang'  => $barang,
            'entries' => $entries,
            'from'    => $from,
            'to'      => $to,
        ]);
    }

    // Kartu stok satu batch voucher
    public function kartuVoucher(int $batchId)
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $batch = (new StockVoucherBatchModel())->find($batchId);
        if (! $batch) return redirect()->to('/stock/summary')->with('error', 'Batch tidak ditemukan.');
        [$from, $to] = $this->period();

        $all     = (new StockVoucherLogModel())->getByBatch($batchId);
        $entries = array_filter($all, fn($e) => $e['tanggal'] >= $from && $e['tanggal'] <= $to);
        return view('stock/kartu_voucher', [
            'user'    => $this->currentUser(),
            'batch'   => $batch,
            'entries' => array_values($entries),
            'from'    => $from,
            'to'      => $to,
        ]);
    }
}
