<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentMenuModel extends Model
{
    protected $table         = 'department_menu_access';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['department_id', 'menu_key', 'section_type', 'can_view', 'can_edit'];
    protected $useTimestamps = false;

    public function getByDepartment(int $deptId): array
    {
        return $this->where('department_id', $deptId)->findAll();
    }

    public function saveMenuAccess(int $deptId, array $menuData): void
    {
        $this->where('department_id', $deptId)->delete();

        foreach ($menuData as $menuKey => $data) {
            if (empty($data['can_view']) && empty($data['can_edit'])) continue;

            $this->insert([
                'department_id' => $deptId,
                'menu_key'      => $menuKey,
                'section_type'  => $data['section_type'] ?? 'all',
                'can_view'      => (int)(bool)($data['can_view'] ?? 0),
                'can_edit'      => (int)(bool)($data['can_edit'] ?? 0),
            ]);
        }
    }

    // Returns ['menu_key' => ['section_type', 'can_view', 'can_edit'], ...]
    public function getMenuMap(int $deptId): array
    {
        $rows = $this->getByDepartment($deptId);
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['menu_key']] = [
                'section_type' => $r['section_type'],
                'can_view'     => (bool)$r['can_view'],
                'can_edit'     => (bool)$r['can_edit'],
            ];
        }
        return $map;
    }
}
