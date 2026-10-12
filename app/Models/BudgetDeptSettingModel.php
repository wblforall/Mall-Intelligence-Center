<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetDeptSettingModel extends Model
{
    protected $table         = 'budget_dept_setting';
    protected $primaryKey    = 'dept_id';
    protected $useAutoIncrement = false;
    protected $allowedFields = ['dept_id', 'pisah_mal', 'updated_by', 'updated_at'];

    public function pisahMal(int $deptId): bool
    {
        $r = $this->find($deptId);
        return (bool) ($r['pisah_mal'] ?? false);
    }

    public function simpan(int $deptId, bool $pisahMal, ?int $userId): void
    {
        $data = ['pisah_mal' => $pisahMal ? 1 : 0, 'updated_by' => $userId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($this->find($deptId)) {
            $this->update($deptId, $data);
        } else {
            $this->insert(['dept_id' => $deptId] + $data);
        }
    }
}
