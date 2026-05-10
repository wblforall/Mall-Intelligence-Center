<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTnaPeriodsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'tahun'           => ['type' => 'SMALLINT', 'unsigned' => true],
            'tanggal_mulai'   => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['open', 'closed'], 'default' => 'open'],
            'catatan'         => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('tna_periods');
    }

    public function down()
    {
        $this->forge->dropTable('tna_periods', true);
    }
}
