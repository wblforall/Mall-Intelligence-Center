<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStandaloneLoyaltyRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'jumlah'     => ['type' => 'INT', 'default' => 0],
            'tersebar'   => ['type' => 'INT', 'null' => true],
            'terpakai'   => ['type' => 'INT', 'null' => true],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('loyalty_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('loyalty_realisasi');
    }
}
