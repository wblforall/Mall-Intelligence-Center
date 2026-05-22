<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewTipeToPromoMediaSpots extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE promo_media_spots
            MODIFY COLUMN tipe ENUM('t_banner','hanging','digital','sticker_lift','totem_stainless') NOT NULL");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE promo_media_spots
            MODIFY COLUMN tipe ENUM('t_banner','hanging','digital') NOT NULL");
    }
}
