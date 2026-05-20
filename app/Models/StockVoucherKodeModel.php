<?php

namespace App\Models;

use CodeIgniter\Model;

class StockVoucherKodeModel extends Model
{
    protected $table         = 'stock_voucher_kode';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['batch_id', 'kode', 'status', 'nama_penerima', 'assigned_at', 'program_type', 'program_id', 'item_id', 'realisasi_id'];

    public function getAvailableByBatch(int $batchId): array
    {
        return $this->where('batch_id', $batchId)
            ->where('status', 'available')
            ->orderBy('kode')
            ->findAll();
    }

    public function getByBatch(int $batchId): array
    {
        return $this->where('batch_id', $batchId)->orderBy('kode')->findAll();
    }

    public function assign(int $kodeId, string $namaPenerima, string $programType, int $programId, int $itemId, int $realisasiId): void
    {
        $this->update($kodeId, [
            'status'        => 'assigned',
            'nama_penerima' => $namaPenerima,
            'assigned_at'   => date('Y-m-d H:i:s'),
            'program_type'  => $programType,
            'program_id'    => $programId,
            'item_id'       => $itemId,
            'realisasi_id'  => $realisasiId,
        ]);
    }

    public function assignManual(int $kodeId, string $namaPenerima = ''): void
    {
        $this->update($kodeId, [
            'status'        => 'assigned',
            'nama_penerima' => $namaPenerima ?: null,
            'assigned_at'   => date('Y-m-d H:i:s'),
            'program_type'  => 'manual',
            'program_id'    => null,
            'item_id'       => null,
            'realisasi_id'  => null,
        ]);
    }

    public function unassign(int $kodeId): void
    {
        $this->update($kodeId, [
            'status'        => 'available',
            'nama_penerima' => null,
            'assigned_at'   => null,
            'program_type'  => null,
            'program_id'    => null,
            'item_id'       => null,
            'realisasi_id'  => null,
        ]);
    }

    public function importKodes(int $batchId, array $kodes): int
    {
        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($kodes as $kode) {
            $kode = trim($kode);
            if ($kode === '') continue;
            $rows[] = ['batch_id' => $batchId, 'kode' => $kode, 'status' => 'available', 'created_at' => $now, 'updated_at' => $now];
        }
        if (empty($rows)) return 0;
        $this->db->table('stock_voucher_kode')->insertBatch($rows);
        return count($rows);
    }
}
