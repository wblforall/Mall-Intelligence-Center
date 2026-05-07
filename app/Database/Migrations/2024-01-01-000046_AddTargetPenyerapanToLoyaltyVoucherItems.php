<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTargetPenyerapanToLoyaltyVoucherItems extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE loyalty_voucher_items ADD COLUMN target_penyerapan DECIMAL(5,2) NULL DEFAULT NULL AFTER total_diterbitkan');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE loyalty_voucher_items DROP COLUMN target_penyerapan');
    }
}
