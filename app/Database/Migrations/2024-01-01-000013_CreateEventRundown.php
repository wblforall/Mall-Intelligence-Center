<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventRundown extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'     => ['type' => 'INT', 'unsigned' => true],
            'hari_ke'      => ['type' => 'TINYINT', 'unsigned' => true],
            'tanggal'      => ['type' => 'DATE'],
            'waktu_mulai'  => ['type' => 'TIME', 'null' => true],
            'waktu_selesai'=> ['type' => 'TIME', 'null' => true],
            'sesi'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'deskripsi'    => ['type' => 'TEXT', 'null' => true],
            'pic'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'lokasi'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'urutan'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['event_id', 'hari_ke', 'urutan']);
        $this->forge->createTable('event_rundown');
    }

    public function down()
    {
        $this->forge->dropTable('event_rundown');
    }
}
