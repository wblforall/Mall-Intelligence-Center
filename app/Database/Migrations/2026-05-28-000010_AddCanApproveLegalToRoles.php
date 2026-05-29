<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanApproveLegalToRoles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE roles ADD COLUMN can_approve_legal TINYINT(1) NOT NULL DEFAULT 0 AFTER can_approve_promo_media");
        // Admin role already has is_admin=1 so it bypasses permission checks, but set it for clarity
        $this->db->query("UPDATE roles SET can_approve_legal = 1 WHERE is_admin = 1");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE roles DROP COLUMN can_approve_legal");
    }
}
