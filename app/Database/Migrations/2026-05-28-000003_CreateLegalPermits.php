<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalPermits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_izin'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'nama_izin'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis_izin'         => ['type' => 'ENUM', 'constraint' => ['IMB','SLF','HO_SITU','SIUP','TDP','Amdal','K3','lainnya'], 'default' => 'lainnya'],
            'instansi_penerbit'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'mall_id'            => ['type' => 'TINYINT', 'unsigned' => true, 'comment' => '1=eWalk 2=Pentacity'],
            'tanggal_terbit'     => ['type' => 'DATE'],
            'tanggal_berakhir'   => ['type' => 'DATE', 'null' => true, 'default' => null, 'comment' => 'NULL = tidak ada masa berlaku'],
            'status'             => ['type' => 'ENUM', 'constraint' => ['active','expired','pending_renewal','revoked'], 'default' => 'active'],
            'catatan'            => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('mall_id');
        $this->forge->addKey('tanggal_berakhir');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_permits');
    }

    public function down()
    {
        $this->forge->dropTable('legal_permits', true);
    }
}
