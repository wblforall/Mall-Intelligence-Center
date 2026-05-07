<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventLoyaltyVoucherTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'       => ['type' => 'INT', 'unsigned' => true],
            'nama_voucher'     => ['type' => 'VARCHAR', 'constraint' => 200],
            'nilai_voucher'    => ['type' => 'BIGINT', 'default' => 0],
            'total_diterbitkan'=> ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => 0],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('event_loyalty_voucher_items');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'unsigned' => true],
            'item_id'    => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'tersebar'   => ['type' => 'INT', 'default' => 0],
            'terpakai'   => ['type' => 'INT', 'default' => 0],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('item_id');
        $this->forge->createTable('event_loyalty_voucher_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('event_loyalty_voucher_realisasi');
        $this->forge->dropTable('event_loyalty_voucher_items');
    }
}
