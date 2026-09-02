<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Default akses aplikasi per departemen. Pola sama persis dengan
 * DepartmentMenuModel: hapus-lalu-tulis-ulang per departemen setiap kali
 * disimpan, supaya baris yang tidak dipilih otomatis hilang tanpa perlu
 * dihapus satu-satu.
 */
class DepartmentAppAccessModel extends Model
{
    protected $table         = 'department_app_access';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['department_id', 'company_id', 'app_id', 'app_role_id'];
    protected $useTimestamps = true;

    public function getByDepartment(int $deptId): array
    {
        return $this->where('department_id', $deptId)->findAll();
    }

    /**
     * @param array $baris [['company_id'=>?int, 'app_id'=>int, 'app_role_id'=>int], ...]
     *                      company_id null = berlaku semua unit bisnis (dept 'holding').
     */
    public function saveGrants(int $deptId, array $baris): void
    {
        $this->where('department_id', $deptId)->delete();
        foreach ($baris as $b) {
            if (empty($b['app_role_id'])) continue;
            $this->insert([
                'department_id' => $deptId,
                'company_id'    => $b['company_id'] ?: null,
                'app_id'        => (int) $b['app_id'],
                'app_role_id'   => (int) $b['app_role_id'],
            ]);
        }
    }
}
