<?php

namespace App\Models;

use CodeIgniter\Model;

class UserMenuModel extends Model
{
    protected $table         = 'user_menu_access';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'menu_key', 'can_view', 'can_edit', 'can_approve'];
    protected $useTimestamps = true;

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    // Returns ['menu_key' => ['can_view'=>bool, 'can_edit'=>bool, 'can_approve'=>bool], ...]
    // — grant tambahan per user (additive di atas akses dept).
    public function getMenuMap(int $userId): array
    {
        $map = [];
        foreach ($this->getByUser($userId) as $r) {
            $map[$r['menu_key']] = [
                'can_view'    => (bool) $r['can_view'],
                'can_edit'    => (bool) $r['can_edit'],
                'can_approve' => (bool) ($r['can_approve'] ?? false),
            ];
        }
        return $map;
    }

    public function saveMenuAccess(int $userId, array $menuData): void
    {
        $this->where('user_id', $userId)->delete();
        foreach ($menuData as $menuKey => $data) {
            if (empty($data['can_view']) && empty($data['can_edit']) && empty($data['can_approve'])) continue;
            $this->insert([
                'user_id'     => $userId,
                'menu_key'    => $menuKey,
                'can_view'    => (int) (bool) ($data['can_view'] ?? 0),
                'can_edit'    => (int) (bool) ($data['can_edit'] ?? 0),
                'can_approve' => (int) (bool) ($data['can_approve'] ?? 0),
            ]);
        }
    }

    /**
     * Salin seluruh grant menu dari satu user ke user lain (menimpa yang ada).
     * Dipakai saat menyiapkan akses karyawan baru: pilih "samakan dengan
     * [nama]" lalu sesuaikan — menekan risiko ada menu yang terlupa.
     */
    public function copyFrom(int $sumberUserId, int $tujuanUserId): int
    {
        if ($sumberUserId === $tujuanUserId) return 0;

        $sumber = $this->getByUser($sumberUserId);
        $this->where('user_id', $tujuanUserId)->delete();

        $baris = [];
        $now   = date('Y-m-d H:i:s');
        foreach ($sumber as $r) {
            $baris[] = [
                'user_id'     => $tujuanUserId,
                'menu_key'    => $r['menu_key'],
                'can_view'    => (int) $r['can_view'],
                'can_edit'    => (int) $r['can_edit'],
                'can_approve' => (int) ($r['can_approve'] ?? 0),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        if ($baris) $this->insertBatch($baris);
        return count($baris);
    }
}
