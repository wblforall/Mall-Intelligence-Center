<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddValueFieldsToEventLoyaltyPrograms extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE event_loyalty_programs
            ADD COLUMN nilai_voucher    BIGINT NULL DEFAULT NULL AFTER total_voucher,
            ADD COLUMN biaya_per_member BIGINT NULL DEFAULT NULL AFTER nilai_voucher
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE event_loyalty_programs
            DROP COLUMN nilai_voucher,
            DROP COLUMN biaya_per_member
        ");
    }
}
