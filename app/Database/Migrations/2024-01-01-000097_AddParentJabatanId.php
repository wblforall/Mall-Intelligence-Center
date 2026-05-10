<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddParentJabatanId extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE jabatans ADD COLUMN parent_jabatan_id INT UNSIGNED NULL DEFAULT NULL AFTER division_id');
        $this->db->query('ALTER TABLE jabatans ADD CONSTRAINT fk_jabatan_parent FOREIGN KEY (parent_jabatan_id) REFERENCES jabatans(id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE jabatans DROP FOREIGN KEY fk_jabatan_parent');
        $this->db->query('ALTER TABLE jabatans DROP COLUMN parent_jabatan_id');
    }
}
