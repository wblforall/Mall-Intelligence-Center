<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMediaPresconToCreativeTipe extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE event_creative_items MODIFY COLUMN tipe ENUM('master_design','digital','cetak','influencer','media_prescon') NOT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE event_creative_items MODIFY COLUMN tipe ENUM('master_design','digital','cetak','influencer') NOT NULL");
    }
}
