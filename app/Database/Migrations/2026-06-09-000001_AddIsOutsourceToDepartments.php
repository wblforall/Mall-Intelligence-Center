<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsOutsourceToDepartments extends Migration
{
    public function up()
    {
        // Departemen outsource (mis. Security) disembunyikan dari dropdown People-dev / org
        $this->db->query("ALTER TABLE departments ADD COLUMN is_outsource TINYINT(1) NOT NULL DEFAULT 0 AFTER description");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE departments DROP COLUMN is_outsource");
    }
}
