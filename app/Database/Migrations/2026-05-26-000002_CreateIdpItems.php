<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdpItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'idp_id'          => ['type' => 'INT', 'unsigned' => true],
            'competency_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'judul'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'level_saat_ini'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null],
            'level_target'    => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null],
            'langkah_aksi'    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'sumber_daya'     => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'deadline'        => ['type' => 'DATE', 'null' => true, 'default' => null],
            'status'          => ['type' => 'ENUM', 'constraint' => ['belum_mulai','dalam_proses','selesai','dibatalkan'], 'default' => 'belum_mulai'],
            'catatan_progres' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'urutan'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('idp_id');
        $this->forge->createTable('idp_items');
    }

    public function down()
    {
        $this->forge->dropTable('idp_items', true);
    }
}
