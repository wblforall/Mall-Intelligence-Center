<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventLoyaltyAndVM extends Migration
{
    public function up()
    {
        // Loyalty programs
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'       => ['type' => 'INT', 'unsigned' => true],
            'nama_program'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'mekanisme'      => ['type' => 'TEXT', 'null' => true],
            'target_peserta' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'budget'         => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_loyalty_programs');

        // VM / Dekorasi items
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'            => ['type' => 'INT', 'unsigned' => true],
            'nama_item'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'deskripsi_referensi' => ['type' => 'TEXT', 'null' => true],
            'budget'              => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'catatan'             => ['type' => 'TEXT', 'null' => true],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_vm_items');
    }

    public function down()
    {
        $this->forge->dropTable('event_loyalty_programs');
        $this->forge->dropTable('event_vm_items');
    }
}
