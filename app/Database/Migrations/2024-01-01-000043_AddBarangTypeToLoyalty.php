<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBarangTypeToLoyalty extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher','barang') NULL");
        $this->db->query("ALTER TABLE event_loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher','barang') NULL");
    }

    public function down()
    {
        $this->db->query("UPDATE loyalty_programs SET target_type = NULL WHERE target_type = 'barang'");
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher') NULL");
        $this->db->query("UPDATE event_loyalty_programs SET target_type = NULL WHERE target_type = 'barang'");
        $this->db->query("ALTER TABLE event_loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher') NULL");
    }
}
