<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTrafficPermsToRoles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE roles
            ADD COLUMN can_view_traffic   TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_users,
            ADD COLUMN can_input_traffic  TINYINT(1) NOT NULL DEFAULT 0 AFTER can_view_traffic,
            ADD COLUMN can_delete_traffic TINYINT(1) NOT NULL DEFAULT 0 AFTER can_input_traffic
        ");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE roles
            DROP COLUMN can_view_traffic,
            DROP COLUMN can_input_traffic,
            DROP COLUMN can_delete_traffic
        ");
    }
}
