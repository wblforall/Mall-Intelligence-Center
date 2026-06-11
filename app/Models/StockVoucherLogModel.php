<?php

namespace App\Models;

use CodeIgniter\Model;

class StockVoucherLogModel extends Model
{
    protected $table         = 'stock_voucher_log';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'batch_id', 'tipe', 'jumlah', 'saldo_sebelum', 'saldo_sesudah',
        'referensi_tipe', 'referensi_id', 'tanggal', 'catatan', 'created_by',
    ];

    // Catat satu gerakan stok voucher. $saldoSebelum = sisa_kode batch sebelum operasi.
    public function record(int $batchId, string $tipe, int $jumlah, int $saldoSebelum, string $refTipe, ?int $refId, ?int $userId, ?string $catatan = null, ?string $tanggal = null): void
    {
        $delta = $tipe === 'keluar' ? -$jumlah : $jumlah; // masuk/retur menambah saldo
        $this->insert([
            'batch_id'       => $batchId,
            'tipe'           => $tipe,
            'jumlah'         => $jumlah,
            'saldo_sebelum'  => $saldoSebelum,
            'saldo_sesudah'  => $saldoSebelum + $delta,
            'referensi_tipe' => $refTipe,
            'referensi_id'   => $refId,
            'tanggal'        => $tanggal ?: date('Y-m-d'),
            'catatan'        => $catatan,
            'created_by'     => $userId,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    public function getByBatch(int $batchId): array
    {
        return $this->select('stock_voucher_log.*, u.name AS pengisi')
                    ->join('users u', 'u.id = stock_voucher_log.created_by', 'left')
                    ->where('batch_id', $batchId)
                    ->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')
                    ->findAll();
    }

    // Map kode_id → nama user yang meng-assign (dari entri 'keluar' terbaru per kode)
    public function getAssignerByKode(): array
    {
        $rows = $this->select('stock_voucher_log.referensi_id, u.name AS pengisi')
                     ->join('users u', 'u.id = stock_voucher_log.created_by', 'left')
                     ->where('tipe', 'keluar')
                     ->where('referensi_id IS NOT NULL', null, false)
                     ->orderBy('stock_voucher_log.id', 'ASC')
                     ->findAll();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['referensi_id']] = $r['pengisi']; // entri terbaru menimpa yg lama
        }
        return $map;
    }

    // Rekap masuk/keluar/retur per batch dalam periode → [batch_id => ['masuk'=>, 'keluar'=>, 'retur'=>]]
    public function getPeriodSummary(string $dari, string $sampai): array
    {
        $rows = $this->select('batch_id, tipe, SUM(jumlah) AS total')
                     ->where('tanggal >=', $dari)->where('tanggal <=', $sampai)
                     ->groupBy('batch_id')->groupBy('tipe')
                     ->findAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['batch_id']][$r['tipe']] = (int)$r['total'];
        }
        return $map;
    }
}
