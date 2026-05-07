<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'   => ['type' => 'INT', 'unsigned' => true],
            'sponsor_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'nilai'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'file_foto'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_terima'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event_id', 'sponsor_id']);
        $this->forge->createTable('event_sponsor_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('event_sponsor_realisasi');
    }
}
