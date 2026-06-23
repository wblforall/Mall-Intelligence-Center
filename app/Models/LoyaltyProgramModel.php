<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltyProgramModel extends Model
{
    protected $table         = 'loyalty_programs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama_program', 'jenis', 'tenant_id',
        'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai',
        'mekanisme', 'target_type', 'target_peserta', 'target_member_aktif', 'target_penyerapan', 'total_voucher',
        'nilai_voucher', 'biaya_per_member',
        'budget', 'status', 'locked', 'locked_by', 'locked_at',
        'eval_status', 'eval_kendala', 'eval_rekomendasi', 'catatan', 'created_by',
    ];

    public function getAll(): array
    {
        return db_connect()->table('loyalty_programs lp')
            ->select('lp.*, t.nama as nama_tenant, t.kategori as tenant_kategori')
            ->join('tenants t', 't.id = lp.tenant_id', 'left')
            ->orderBy('lp.status', 'ASC')
            ->orderBy('lp.nama_program', 'ASC')
            ->get()->getResultArray();
    }

    public function getActive(): array
    {
        return db_connect()->table('loyalty_programs lp')
            ->select('lp.*, t.nama as nama_tenant, t.kategori as tenant_kategori')
            ->join('tenants t', 't.id = lp.tenant_id', 'left')
            ->where('lp.status', 'active')
            ->orderBy('lp.nama_program', 'ASC')
            ->get()->getResultArray();
    }

    public function toggleStatus(int $id): void
    {
        $current = $this->find($id);
        if (! $current) return;
        $this->update($id, [
            'status' => $current['status'] === 'active' ? 'inactive' : 'active',
        ]);
    }

    public function lock(int $id, int $userId): void
    {
        // Lock = finalisasi/tutup → sekalian nonaktifkan agar pindah ke tab Closed
        $this->update($id, [
            'locked'    => 1,
            'locked_by' => $userId,
            'locked_at' => date('Y-m-d H:i:s'),
            'status'    => 'inactive',
        ]);
    }

    public function unlock(int $id): void
    {
        // Buka kunci → aktifkan kembali (keluar dari tab Closed)
        $this->update($id, [
            'locked'    => 0,
            'locked_by' => null,
            'locked_at' => null,
            'status'    => 'active',
        ]);
    }

    public function isLocked(int $id): bool
    {
        $row = $this->select('locked')->find($id);
        return (bool)($row['locked'] ?? false);
    }
}
