<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalContracts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_kontrak'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'nama_vendor'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis_kontrak'       => ['type' => 'ENUM', 'constraint' => ['cleaning','security','parkir','maintenance','catering','IT','marketing','lainnya'], 'default' => 'lainnya'],
            'lingkup_pekerjaan'   => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'mall_id'             => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null, 'comment' => 'NULL = kedua mall'],
            'tanggal_mulai'       => ['type' => 'DATE'],
            'tanggal_berakhir'    => ['type' => 'DATE'],
            'nilai_kontrak'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'status'              => ['type' => 'ENUM', 'constraint' => ['draft','active','expired','terminated'], 'default' => 'draft'],
            'catatan'             => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_kontrak');
        $this->forge->addKey('mall_id');
        $this->forge->addKey('tanggal_berakhir');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_contracts');
    }

    public function down()
    {
        $this->forge->dropTable('legal_contracts', true);
    }
}
