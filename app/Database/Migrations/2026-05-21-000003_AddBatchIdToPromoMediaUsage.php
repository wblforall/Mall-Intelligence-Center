<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBatchIdToPromoMediaUsage extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE promo_media_usage ADD COLUMN batch_id VARCHAR(20) NULL AFTER id, ADD INDEX idx_batch_id (batch_id)");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE promo_media_usage DROP INDEX idx_batch_id, DROP COLUMN batch_id");
    }
}
