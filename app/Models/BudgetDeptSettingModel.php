<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetDeptSettingModel extends Model
{
    protected $table         = 'budget_dept_setting';
    protected $primaryKey    = 'dept_id';
    protected $useAutoIncrement = false;
    protected $allowedFields = ['dept_id', 'pisah_mal', 'tahun_mulai', 'event_mulai', 'updated_by', 'updated_at'];

    public function pisahMal(int $deptId): bool
    {
        $r = $this->find($deptId);
        return (bool) ($r['pisah_mal'] ?? false);
    }

    /** Tahun pertama yang boleh dibuka dept ini; kosong = bawaan modul. */
    public function tahunMulai(int $deptId, int $bawaan): int
    {
        $r = $this->find($deptId);
        return ! empty($r['tahun_mulai']) ? (int) $r['tahun_mulai'] : $bawaan;
    }

    /**
     * Bulan (YYYY-MM) sejak realisasi Event Content dihitung ke budget dept ini;
     * null = dept ini tidak terhubung ke modul Events (tanpa RAB).
     */
    public function eventMulai(int $deptId): ?string
    {
        $r = $this->find($deptId);
        return ! empty($r['event_mulai']) ? (string) $r['event_mulai'] : null;
    }

    /** Dept yang terhubung ke modul Events (pemilik RAB) — [dept_id => event_mulai]. */
    public function deptEvent(): array
    {
        $rows = $this->where('event_mulai IS NOT NULL')->findAll();
        return array_column($rows, 'event_mulai', 'dept_id');
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
