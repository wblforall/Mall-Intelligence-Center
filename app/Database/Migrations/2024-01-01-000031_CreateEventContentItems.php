<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventContentItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'   => ['type' => 'INT', 'unsigned' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pic'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'budget'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'urutan'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_content_items');
    }

    public function down()
    {
        $this->forge->dropTable('event_content_items');
    }
}
