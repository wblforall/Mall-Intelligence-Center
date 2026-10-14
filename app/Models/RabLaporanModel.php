<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan realisasi RAB satu bulan. diajukan (isi dibekukan, PDF untuk e-Sign)
 * → disahkan (admin E&P unggah PDF bertanda tangan); sesudah disahkan,
 * realisasi item bulan itu terkunci. Laporan baru untuk bulan yang sama
 * membatalkan yang lama selama belum disahkan.
 */
class RabLaporanModel extends Model
{
    protected $table         = 'rab_laporan';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'bulan', 'rab_id', 'nomor', 'status', 'snapshot', 'file_ttd', 'dibuat_by', 'disahkan_by', 'disahkan_at'];
    protected $useTimestamps = true;

    public function milikDept(int $deptId): array
    {
        return $this->select('rab_laporan.id, dept_id, bulan, rab_id, nomor, status, file_ttd, disahkan_at, rab_laporan.created_at, u.name AS pembuat, s.name AS pengesah')
            ->join('users u', 'u.id = rab_laporan.dibuat_by', 'left')
            ->join('users s', 's.id = rab_laporan.disahkan_by', 'left')
            ->where('dept_id', $deptId)->where('status !=', 'batal')
            ->orderBy('bulan', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    /** Bulan-bulan (YYYY-MM) yang laporannya sudah disahkan — realisasinya terkunci. */
    public function bulanDisahkan(): array
    {
        return array_column($this->select('bulan')->where('status', 'disahkan')->findAll(), 'bulan');
    }
}
