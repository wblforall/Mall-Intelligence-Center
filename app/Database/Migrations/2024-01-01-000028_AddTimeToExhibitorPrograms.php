<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimeToExhibitorPrograms extends Migration
{
    public function up()
    {
        // rename tanggal → tanggal_mulai
        $this->forge->modifyColumn('exhibitor_programs', [
            'tanggal' => ['name' => 'tanggal_mulai', 'type' => 'DATE', 'null' => true],
        ]);

        $this->forge->addColumn('exhibitor_programs', [
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true, 'after' => 'tanggal_mulai'],
            'jam_mulai'       => ['type' => 'TIME', 'null' => true, 'after' => 'tanggal_selesai'],
            'jam_selesai'     => ['type' => 'TIME', 'null' => true, 'after' => 'jam_mulai'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('exhibitor_programs', ['tanggal_selesai', 'jam_mulai', 'jam_selesai']);
        $this->forge->modifyColumn('exhibitor_programs', [
            'tanggal_mulai' => ['name' => 'tanggal', 'type' => 'DATE', 'null' => true],
        ]);
    }
}
