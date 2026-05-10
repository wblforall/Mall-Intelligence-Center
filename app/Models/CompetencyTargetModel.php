<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetencyTargetModel extends Model
{
    protected $table         = 'competency_targets';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['competency_id', 'dept_id', 'jabatan', 'target_level'];
    protected $useTimestamps = true;

    public function getByDept(int $deptId): array
    {
        return $this->where('dept_id', $deptId)->where('jabatan IS NULL', null, false)->findAll();
    }

    // Returns [competency_id => target_level] map for a dept
    public function getMapByDept(int $deptId): array
    {
        $rows = $this->getByDept($deptId);
        return array_column($rows, 'target_level', 'competency_id');
    }

    public function saveForDept(int $deptId, array $levels): void
    {
        $db = db_connect();
        $db->table('competency_targets')
           ->where('dept_id', $deptId)
           ->where('jabatan IS NULL', null, false)
           ->delete();

        $insert = [];
        foreach ($levels as $compId => $level) {
            $level = (int)$level;
            if ($level >= 1 && $level <= 5) {
                $insert[] = [
                    'competency_id' => (int)$compId,
                    'dept_id'       => $deptId,
                    'jabatan'       => null,
                    'target_level'  => $level,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ];
            }
        }
        if ($insert) $db->table('competency_targets')->insertBatch($insert);
    }

    public function getMapByDeptJabatan(int $deptId, string $jabatan): array
    {
        $rows = $this->where('dept_id', $deptId)->where('jabatan', $jabatan)->findAll();
        return array_column($rows, 'target_level', 'competency_id');
    }

    public function saveForDeptJabatan(int $deptId, string $jabatan, array $levels): void
    {
        $db = db_connect();
        $db->table('competency_targets')
           ->where('dept_id', $deptId)
           ->where('jabatan', $jabatan)
           ->delete();

        $insert = [];
        $now    = date('Y-m-d H:i:s');
        foreach ($levels as $compId => $level) {
            $level = (int)$level;
            if ($level >= 1 && $level <= 5) {
                $insert[] = [
                    'competency_id' => (int)$compId,
                    'dept_id'       => $deptId,
                    'jabatan'       => $jabatan,
                    'target_level'  => $level,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }
        if ($insert) $db->table('competency_targets')->insertBatch($insert);
    }

    // List of jabatan names that have specific overrides for a dept
    public function getOverridingJabatans(int $deptId): array
    {
        $rows = $this->select('jabatan')
                     ->where('dept_id', $deptId)
                     ->where('jabatan IS NOT NULL', null, false)
                     ->groupBy('jabatan')
                     ->findAll();
        return array_column($rows, 'jabatan');
    }

    // Get target level for an employee based on their dept and jabatan
    public function getTargetForEmployee(int $deptId, string $jabatan): array
    {
        // jabatan-specific takes priority over dept-level
        $jabatanRows = $this->where('dept_id', $deptId)->where('jabatan', $jabatan)->findAll();
        $deptRows    = $this->getByDept($deptId);

        $map = array_column($deptRows, 'target_level', 'competency_id');
        foreach ($jabatanRows as $r) $map[$r['competency_id']] = $r['target_level'];
        return $map;
    }
}
