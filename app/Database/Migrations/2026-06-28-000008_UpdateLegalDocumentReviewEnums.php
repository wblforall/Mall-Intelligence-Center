<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateLegalDocumentReviewEnums extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE legal_documents MODIFY COLUMN entity_type ENUM('lease','permit','contract','spk','pks','psm_mall','psm_developer','psm_gudang','kontrak_pameran') NOT NULL");
        $this->db->query("ALTER TABLE legal_reviews MODIFY COLUMN entity_type ENUM('standalone','lease','permit','contract','spk','pks','psm_mall','psm_developer','psm_gudang','kontrak_pameran') NOT NULL DEFAULT 'standalone'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE legal_documents MODIFY COLUMN entity_type ENUM('lease','permit','contract') NOT NULL");
        $this->db->query("ALTER TABLE legal_reviews MODIFY COLUMN entity_type ENUM('standalone','lease','permit','contract') NOT NULL DEFAULT 'standalone'");
    }
}
