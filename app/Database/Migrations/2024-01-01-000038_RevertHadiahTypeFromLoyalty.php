<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RevertHadiahTypeFromLoyalty extends Migration
{
    public function up(): void
    {
        // Nullify any programs that were set to hadiah type
        $this->db->query("UPDATE loyalty_programs SET target_type = NULL WHERE target_type = 'hadiah'");
        // Revert enum to only member and evoucher
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher') NULL");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE loyalty_programs MODIFY COLUMN target_type ENUM('member','evoucher','hadiah') NULL");
    }
}
