<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusMenungguPersetujuan extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE pip_plans MODIFY COLUMN status ENUM('draft','menunggu_persetujuan','aktif','selesai','diperpanjang','dihentikan') NOT NULL DEFAULT 'menunggu_persetujuan'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE pip_plans MODIFY COLUMN status ENUM('draft','aktif','selesai','diperpanjang','dihentikan') NOT NULL DEFAULT 'aktif'");
    }
}
