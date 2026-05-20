<?php

namespace App\Models;

use CodeIgniter\Model;

class StockVoucherBatchModel extends Model
{
    protected $table         = 'stock_voucher_batch';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_voucher', 'nilai_voucher', 'expired_date', 'total_kode', 'sisa_kode', 'stok_reserved', 'catatan', 'created_by'];

    public function getAll(): array
    {
        return $this->orderBy('nama_voucher')->findAll();
    }

    public function getAvailable(): array
    {
        return $this->where('sisa_kode >', 0)
            ->where('(expired_date IS NULL OR expired_date >= CURDATE())')
            ->orderBy('nama_voucher')
            ->findAll();
    }

    public function reserveSisa(int $id, int $jumlah = 1): void
    {
        if ($jumlah <= 0) return;
        $this->db->table('stock_voucher_batch')
            ->where('id', $id)
            ->set('stok_reserved', "stok_reserved + {$jumlah}", false)
            ->update();
    }

    public function releaseSisa(int $id, int $jumlah = 1): void
    {
        if ($jumlah <= 0) return;
        $this->db->table('stock_voucher_batch')
            ->where('id', $id)
            ->set('stok_reserved', "GREATEST(0, stok_reserved - {$jumlah})", false)
            ->update();
    }

    public function deductSisa(int $id): void
    {
        $this->db->table('stock_voucher_batch')
            ->where('id', $id)
            ->set('sisa_kode', 'sisa_kode - 1', false)
            ->update();
    }

    public function restoreSisa(int $id): void
    {
        $this->db->table('stock_voucher_batch')
            ->where('id', $id)
            ->set('sisa_kode', 'sisa_kode + 1', false)
            ->update();
    }
}
