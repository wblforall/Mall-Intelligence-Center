<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorshipRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'unsigned' => true],
            'sponsor_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'nilai'      => ['type' => 'BIGINT', 'default' => 0],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'file_bukti' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sponsor_id');
        $this->forge->addKey('program_id');
        $this->forge->createTable('sponsorship_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_realisasi', true);
    }
}
