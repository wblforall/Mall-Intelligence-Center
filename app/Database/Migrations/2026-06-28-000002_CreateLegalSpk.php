<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalSpk extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_spk'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'nama_vendor'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'deskripsi_pekerjaan' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'nilai_spk'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'tanggal_terbit'      => ['type' => 'DATE'],
            'tanggal_selesai'     => ['type' => 'DATE'],
            'pic_user_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'status'              => ['type' => 'ENUM', 'constraint' => ['draft','aktif','selesai','batal'], 'default' => 'draft'],
            'catatan'             => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_spk');
        $this->forge->addKey('status');
        $this->forge->addKey('tanggal_selesai');
        $this->forge->createTable('legal_spk');
    }

    public function down()
    {
        $this->forge->dropTable('legal_spk', true);
    }
}
