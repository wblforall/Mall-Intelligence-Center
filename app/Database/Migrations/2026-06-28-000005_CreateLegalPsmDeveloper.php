<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalPsmDeveloper extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_psm'         => ['type' => 'VARCHAR', 'constraint' => 80],
            'nama_developer'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'objek_perjanjian'  => ['type' => 'VARCHAR', 'constraint' => 300],
            'nilai'             => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'mall_id'           => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null, 'comment' => '1=eWalk 2=Pentacity NULL=keduanya'],
            'tanggal_mulai'     => ['type' => 'DATE'],
            'tanggal_berakhir'  => ['type' => 'DATE'],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft','active','expired','terminated'], 'default' => 'draft'],
            'catatan'           => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_psm');
        $this->forge->addKey('tanggal_berakhir');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_psm_developer');
    }

    public function down()
    {
        $this->forge->dropTable('legal_psm_developer', true);
    }
}
