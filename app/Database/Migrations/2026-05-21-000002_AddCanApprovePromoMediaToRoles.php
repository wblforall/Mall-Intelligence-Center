<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanApprovePromoMediaToRoles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE roles ADD COLUMN can_approve_promo_media TINYINT(1) NOT NULL DEFAULT 0 AFTER can_approve_pip");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE roles DROP COLUMN can_approve_promo_media");
    }
}
