<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupRolePermissions extends Migration
{
    public function up(): void
    {
        // Remove traffic view/input columns and JSON module perms from roles.
        // Module access is now controlled exclusively by department_menu_access.
        $this->forge->dropColumn('roles', ['can_view_traffic', 'can_input_traffic', 'permissions']);
    }

    public function down(): void
    {
        $this->forge->addColumn('roles', [
            'can_view_traffic'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'can_manage_users'],
            'can_input_traffic' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'can_view_traffic'],
            'permissions'       => ['type' => 'TEXT', 'null' => true, 'after' => 'can_delete_traffic'],
        ]);
    }
}
