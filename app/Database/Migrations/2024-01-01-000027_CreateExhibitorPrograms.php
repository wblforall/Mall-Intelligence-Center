<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExhibitorPrograms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'exhibitor_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id'     => ['type' => 'INT', 'unsigned' => true],
            'nama_program' => ['type' => 'VARCHAR', 'constraint' => 200],
            'tanggal'      => ['type' => 'DATE', 'null' => true],
            'deskripsi'    => ['type' => 'TEXT', 'null' => true],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('exhibitor_id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('exhibitor_programs');
    }

    public function down()
    {
        $this->forge->dropTable('exhibitor_programs');
    }
}
