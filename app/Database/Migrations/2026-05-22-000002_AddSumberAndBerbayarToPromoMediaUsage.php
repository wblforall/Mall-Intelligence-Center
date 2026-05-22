<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSumberAndBerbayarToPromoMediaUsage extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE promo_media_usage
            ADD COLUMN sumber ENUM('internal','tenant','external') NOT NULL DEFAULT 'internal' AFTER catatan_pemohon,
            ADD COLUMN is_berbayar TINYINT(1) NOT NULL DEFAULT 0 AFTER sumber");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE promo_media_usage
            DROP COLUMN sumber,
            DROP COLUMN is_berbayar");
    }
}
