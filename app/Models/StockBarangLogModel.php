<?php

namespace App\Models;

use CodeIgniter\Model;

class StockBarangLogModel extends Model
{
    protected $table         = 'stock_barang_log';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['barang_id', 'tipe', 'jumlah', 'stok_sebelum', 'stok_sesudah', 'referensi_tipe', 'referensi_id', 'tanggal', 'catatan', 'created_by'];

    public function writeKeluar(int $barangId, int $jumlah, int $stokSebelum, string $refTipe, int $refId, string $tanggal, int $userId, string $catatan = null): void
    {
        $this->insert([
            'barang_id'      => $barangId,
            'tipe'           => 'keluar',
            'jumlah'         => $jumlah,
            'stok_sebelum'   => $stokSebelum,
            'stok_sesudah'   => $stokSebelum - $jumlah,
            'referensi_tipe' => $refTipe,
            'referensi_id'   => $refId,
            'tanggal'        => $tanggal,
            'catatan'        => $catatan,
            'created_by'     => $userId,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    public function writeMasuk(int $barangId, int $jumlah, int $stokSebelum, string $tanggal, int $userId, string $catatan = null): void
    {
        $this->insert([
            'barang_id'    => $barangId,
            'tipe'         => 'masuk',
            'jumlah'       => $jumlah,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSebelum + $jumlah,
            'tanggal'      => $tanggal,
            'catatan'      => $catatan,
            'created_by'   => $userId,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function getByBarang(int $barangId, int $limit = 50): array
    {
        return $this->where('barang_id', $barangId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function deleteByRef(string $refTipe, int $refId): void
    {
        $this->where('referensi_tipe', $refTipe)->where('referensi_id', $refId)->delete();
    }

    // Kartu stok satu barang (kronologis ASC, dengan saldo berjalan stok_sesudah)
    public function getByBarangAsc(int $barangId, ?string $dari = null, ?string $sampai = null): array
    {
        $q = $this->select('stock_barang_log.*, u.name AS pengisi')
                  ->join('users u', 'u.id = stock_barang_log.created_by', 'left')
                  ->where('barang_id', $barangId);
        if ($dari)   $q->where('tanggal >=', $dari);
        if ($sampai) $q->where('tanggal <=', $sampai);
        return $q->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    // Rekap masuk/keluar per barang dalam periode → [barang_id => ['masuk'=>, 'keluar'=>]]
    public function getPeriodSummary(string $dari, string $sampai): array
    {
        $rows = $this->select('barang_id, tipe, SUM(jumlah) AS total')
                     ->where('tanggal >=', $dari)->where('tanggal <=', $sampai)
                     ->groupBy('barang_id')->groupBy('tipe')
                     ->findAll();
        $map = [];
        foreach ($rows as $r) { $map[$r['barang_id']][$r['tipe']] = (int)$r['total']; }
        return $map;
    }

    public function getMutasi(string $dari, string $sampai, ?int $barangId = null): array
    {
        $q = $this->db->table('stock_barang_log l')
            ->select('l.*, b.nama_barang, b.satuan')
            ->join('stock_barang b', 'b.id = l.barang_id')
            ->where('l.tanggal >=', $dari)
            ->where('l.tanggal <=', $sampai)
            ->orderBy('l.tanggal', 'DESC')
            ->orderBy('l.created_at', 'DESC');
        if ($barangId) $q->where('l.barang_id', $barangId);
        return $q->get()->getResultArray();
    }
}
