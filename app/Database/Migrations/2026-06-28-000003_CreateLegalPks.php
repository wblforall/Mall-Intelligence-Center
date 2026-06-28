<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalPks extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_pks'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'pihak_kedua'   => ['type' => 'VARCHAR', 'constraint' => 200],
            'ruang_lingkup' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'nilai'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'tanggal_mulai' => ['type' => 'DATE'],
            'tanggal_berakhir' => ['type' => 'DATE'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['draft','active','expired','terminated'], 'default' => 'draft'],
            'catatan'       => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_pks');
        $this->forge->addKey('tanggal_berakhir');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_pks');
    }

    public function down()
    {
        $this->forge->dropTable('legal_pks', true);
    }
}
