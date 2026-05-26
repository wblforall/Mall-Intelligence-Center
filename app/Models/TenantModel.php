<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table         = 'tenants';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama', 'kategori', 'lantai', 'nomor_unit',
        'contact_person', 'no_hp', 'email',
        'status', 'catatan', 'created_by',
    ];

    public function getActive(): array
    {
        return $this->where('status', 'active')->orderBy('nama', 'ASC')->findAll();
    }

    public function getAllWithProgramCount(): array
    {
        return db_connect()->table('tenants t')
            ->select('t.*, COUNT(lp.id) as program_count,
                      SUM(CASE WHEN lp.status = \'active\' THEN 1 ELSE 0 END) as program_aktif')
            ->join('loyalty_programs lp', 'lp.tenant_id = t.id AND lp.jenis = \'tenant\'', 'left')
            ->groupBy('t.id')
            ->orderBy('t.nama', 'ASC')
            ->get()->getResultArray();
    }

    public function getPrograms(int $tenantId): array
    {
        return db_connect()->table('loyalty_programs lp')
            ->select('lp.*, u.name as created_by_name,
                      (SELECT COUNT(*) FROM loyalty_realisasi WHERE program_id = lp.id) as total_realisasi_entries,
                      (SELECT COALESCE(SUM(jumlah),0) FROM loyalty_realisasi WHERE program_id = lp.id) as total_member')
            ->join('users u', 'u.id = lp.created_by', 'left')
            ->where('lp.tenant_id', $tenantId)
            ->orderBy('lp.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
