<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVMRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vm_item_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id'   => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'jumlah'     => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'catatan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('vm_item_id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_vm_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('event_vm_realisasi');
    }
}
