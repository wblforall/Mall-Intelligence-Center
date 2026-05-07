<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixExhibitorProgramDates extends Migration
{
    public function up()
    {
        // rename tanggal → tanggal_mulai dan tambah tanggal_selesai setelahnya
        $this->db->query('ALTER TABLE exhibitor_programs
            CHANGE COLUMN `tanggal` `tanggal_mulai` DATE NULL,
            ADD COLUMN `tanggal_selesai` DATE NULL AFTER `tanggal_mulai`
        ');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE exhibitor_programs
            DROP COLUMN `tanggal_selesai`,
            CHANGE COLUMN `tanggal_mulai` `tanggal` DATE NULL
        ');
    }
}
