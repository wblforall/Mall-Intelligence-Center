<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockVoucherLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'batch_id'       => ['type' => 'INT', 'constraint' => 11],
            'tipe'           => ['type' => 'ENUM', 'constraint' => ['masuk', 'keluar', 'retur']],
            'jumlah'         => ['type' => 'INT', 'constraint' => 11],
            'saldo_sebelum'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'saldo_sesudah'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'referensi_tipe' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // import|manual|program|deassign|delete
            'referensi_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tanggal'        => ['type' => 'DATE'],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('batch_id');
        $this->forge->addKey('tanggal');
        $this->forge->createTable('stock_voucher_log');
    }

    public function down()
    {
        $this->forge->dropTable('stock_voucher_log');
    }
}
