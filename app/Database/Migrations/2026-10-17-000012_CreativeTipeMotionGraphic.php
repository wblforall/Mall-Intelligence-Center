<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Jenis item baru 'motion_graphic' (diperlakukan seperti Design). Nilai lama tidak berubah. */
class CreativeTipeMotionGraphic extends Migration
{
    private const LAMA = "'master_design','digital','cetak','influencer','media_prescon'";
    private const BARU = "'master_design','digital','cetak','influencer','media_prescon','motion_graphic'";

    public function up()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("ALTER TABLE {$t} MODIFY COLUMN tipe ENUM(" . self::BARU . ") NOT NULL");
        }
    }

    public function down()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("DELETE FROM {$t} WHERE tipe = 'motion_graphic'");
            $this->db->query("ALTER TABLE {$t} MODIFY COLUMN tipe ENUM(" . self::LAMA . ") NOT NULL");
        }
    }
}
