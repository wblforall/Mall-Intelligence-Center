<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermissionsJsonToRoles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE roles ADD COLUMN permissions TEXT NULL AFTER can_view_logs");
    }

    public function down()
    {
        $this->forge->dropColumn('roles', 'permissions');
    }
}
