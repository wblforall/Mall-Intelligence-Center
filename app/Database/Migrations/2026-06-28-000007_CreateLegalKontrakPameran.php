<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalKontrakPameran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_kontrak'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'nama_penyelenggara'=> ['type' => 'VARCHAR', 'constraint' => 150],
            'nama_event'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'lokasi_area'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'mall_id'           => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null, 'comment' => '1=eWalk 2=Pentacity NULL=keduanya'],
            'tanggal_mulai'     => ['type' => 'DATE'],
            'tanggal_selesai'   => ['type' => 'DATE'],
            'nilai_sewa'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft','aktif','selesai','batal'], 'default' => 'draft'],
            'catatan'           => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_kontrak');
        $this->forge->addKey('mall_id');
        $this->forge->addKey('tanggal_selesai');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_kontrak_pameran');
    }

    public function down()
    {
        $this->forge->dropTable('legal_kontrak_pameran', true);
    }
}
