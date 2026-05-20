<?php

namespace App\Models;

use CodeIgniter\Model;

class StockBarangModel extends Model
{
    protected $table         = 'stock_barang';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_barang', 'satuan', 'nilai_satuan', 'stok_awal', 'stok_tersedia', 'stok_reserved', 'catatan', 'created_by'];

    public function getAll(): array
    {
        return $this->orderBy('nama_barang')->findAll();
    }

    public function reserveStock(int $id, int $jumlah): void
    {
        if ($jumlah <= 0) return;
        $this->db->table('stock_barang')
            ->where('id', $id)
            ->set('stok_reserved', "stok_reserved + {$jumlah}", false)
            ->update();
    }

    public function releaseStock(int $id, int $jumlah): void
    {
        if ($jumlah <= 0) return;
        $this->db->table('stock_barang')
            ->where('id', $id)
            ->set('stok_reserved', "GREATEST(0, stok_reserved - {$jumlah})", false)
            ->update();
    }

    public function deductStock(int $id, int $jumlah): void
    {
        $this->db->table('stock_barang')
            ->where('id', $id)
            ->set('stok_tersedia', "stok_tersedia - {$jumlah}", false)
            ->update();
    }

    public function restoreStock(int $id, int $jumlah): void
    {
        $this->db->table('stock_barang')
            ->where('id', $id)
            ->set('stok_tersedia', "stok_tersedia + {$jumlah}", false)
            ->update();
    }
}
