<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table         = 'departments';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'description'];
    protected $useTimestamps = true;

    public function getAllWithMenuCount(): array
    {
        return $this->db->table('departments d')
            ->select('d.*, COUNT(ma.id) AS menu_count, COUNT(u.id) AS user_count')
            ->join('department_menu_access ma', 'ma.department_id = d.id', 'left')
            ->join('users u', 'u.department_id = d.id', 'left')
            ->groupBy('d.id')
            ->orderBy('d.name')
            ->get()->getResultArray();
    }

    public function getWithMenus(int $id): ?array
    {
        $dept = $this->find($id);
        if (! $dept) return null;

        $menus = (new DepartmentMenuModel())->getByDepartment($id);
        $dept['menus'] = [];
        foreach ($menus as $m) {
            $dept['menus'][$m['menu_key']] = $m;
        }
        return $dept;
    }
}
