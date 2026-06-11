<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanDeassignVoucherToRoles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE roles ADD COLUMN can_deassign_voucher TINYINT(1) NOT NULL DEFAULT 0 AFTER can_view_gantt");
        $this->db->query("UPDATE roles SET can_deassign_voucher = 1 WHERE is_admin = 1");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE roles DROP COLUMN can_deassign_voucher");
    }
}
