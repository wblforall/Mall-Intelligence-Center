<?php

namespace App\Models;

use CodeIgniter\Model;

class UserMenuModel extends Model
{
    protected $table         = 'user_menu_access';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'menu_key', 'can_view', 'can_edit'];
    protected $useTimestamps = true;

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    // Returns ['menu_key' => ['can_view'=>bool, 'can_edit'=>bool], ...] — grant tambahan per user.
    public function getMenuMap(int $userId): array
    {
        $map = [];
        foreach ($this->getByUser($userId) as $r) {
            $map[$r['menu_key']] = [
                'can_view' => (bool) $r['can_view'],
                'can_edit' => (bool) $r['can_edit'],
            ];
        }
        return $map;
    }

    public function saveMenuAccess(int $userId, array $menuData): void
    {
        $this->where('user_id', $userId)->delete();
        foreach ($menuData as $menuKey => $data) {
            if (empty($data['can_view']) && empty($data['can_edit'])) continue;
            $this->insert([
                'user_id'  => $userId,
                'menu_key' => $menuKey,
                'can_view' => (int) (bool) ($data['can_view'] ?? 0),
                'can_edit' => (int) (bool) ($data['can_edit'] ?? 0),
            ]);
        }
    }
}
