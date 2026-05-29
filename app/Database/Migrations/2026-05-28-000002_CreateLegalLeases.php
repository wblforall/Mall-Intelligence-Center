<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalLeases extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_kontrak'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'tenant_name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'unit_no'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
            'mall_id'            => ['type' => 'TINYINT', 'unsigned' => true, 'comment' => '1=eWalk 2=Pentacity'],
            'jenis_sewa'         => ['type' => 'ENUM', 'constraint' => ['retail','fnb','anchor','kiosk','atm','lainnya'], 'default' => 'retail'],
            'tanggal_mulai'      => ['type' => 'DATE'],
            'tanggal_berakhir'   => ['type' => 'DATE'],
            'nilai_sewa'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'periode_pembayaran' => ['type' => 'ENUM', 'constraint' => ['bulanan','triwulan','tahunan'], 'null' => true, 'default' => null],
            'status'             => ['type' => 'ENUM', 'constraint' => ['draft','active','expired','terminated'], 'default' => 'draft'],
            'catatan'            => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nomor_kontrak');
        $this->forge->addKey('mall_id');
        $this->forge->addKey('tanggal_berakhir');
        $this->forge->addKey('status');
        $this->forge->createTable('legal_leases');
    }

    public function down()
    {
        $this->forge->dropTable('legal_leases', true);
    }
}
