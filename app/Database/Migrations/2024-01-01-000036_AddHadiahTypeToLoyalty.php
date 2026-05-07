<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHadiahTypeToLoyalty extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher','hadiah') NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher') NULL");
    }
}
