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

    /** Stok fisik yang benar-benar ada (bisa negatif pada data lama yang belum dikoreksi). */
    public function tersedia(int $id): int
    {
        return (int) ($this->find($id)['stok_tersedia'] ?? 0);
    }

    /** Stok yang belum dialokasikan ke program/event mana pun. */
    public function bebas(int $id): int
    {
        $b = $this->find($id);
        return (int) ($b['stok_tersedia'] ?? 0) - (int) ($b['stok_reserved'] ?? 0);
    }

    public static function pesanRealisasiMelebihi(string $nama, int $jumlah, int $tersedia): string
    {
        return 'Realisasi ditolak: stok "' . $nama . '" tinggal ' . max(0, $tersedia) . ' pcs, sedangkan yang dicatat ' . $jumlah
             . ' pcs. Tambah stok dulu di menu Stock Hadiah Fisik (atau hubungi admin stok), lalu catat ulang.';
    }

    public static function pesanAlokasiMelebihi(string $nama, int $minta, int $bebas): string
    {
        return 'Alokasi ditolak: stok "' . $nama . '" yang belum dialokasikan tinggal ' . max(0, $bebas) . ' pcs, sedangkan tambahan alokasi yang diminta ' . $minta
             . ' pcs. Kurangi jumlah atau tambah stok dulu di menu Stock Hadiah Fisik.';
    }
}
