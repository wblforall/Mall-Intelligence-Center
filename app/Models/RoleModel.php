<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'slug', 'description', 'is_admin', 'can_create_event', 'can_delete_event', 'can_manage_users', 'can_delete_traffic', 'can_import_traffic', 'can_view_logs', 'can_approve_events'];
    protected $useTimestamps = true;

    public function getBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    public function isSlugTaken(string $slug, int $excludeId = 0): bool
    {
        $builder = $this->where('slug', $slug);
        if ($excludeId) $builder->where('id !=', $excludeId);
        return $builder->countAllResults() > 0;
    }

    // Build role_perms for session — roles govern system-level actions only
    public static function buildPerms(array $role): array
    {
        $isAdmin = (bool)$role['is_admin'];
        return [
            'is_admin'           => $isAdmin,
            'can_create_event'   => $isAdmin || (bool)$role['can_create_event'],
            'can_delete_event'   => $isAdmin || (bool)$role['can_delete_event'],
            'can_manage_users'   => $isAdmin || (bool)$role['can_manage_users'],
            'can_delete_traffic'  => $isAdmin || (bool)($role['can_delete_traffic']  ?? false),
            'can_import_traffic'  => $isAdmin || (bool)($role['can_import_traffic']  ?? false),
            'can_view_logs'       => $isAdmin || (bool)($role['can_view_logs']       ?? false),
            'can_approve_events'  => $isAdmin || (bool)($role['can_approve_events']  ?? false),
        ];
    }
}
