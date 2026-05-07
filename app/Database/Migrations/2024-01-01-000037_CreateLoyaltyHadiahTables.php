<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoyaltyHadiahTables extends Migration
{
    public function up()
    {
        // Items: list of prize types per program
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'   => ['type' => 'INT', 'unsigned' => true],
            'nama_hadiah'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'stok'         => ['type' => 'INT', 'default' => 0],
            'nilai_satuan' => ['type' => 'BIGINT', 'default' => 0, 'comment' => 'Harga satuan (Rp) untuk budget realisasi'],
            'catatan'      => ['type' => 'TEXT', 'null' => true],
            'created_by'   => ['type' => 'INT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('loyalty_hadiah_items');

        // Daily distribution realisasi per item
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'       => ['type' => 'INT', 'unsigned' => true],
            'item_id'          => ['type' => 'INT', 'unsigned' => true],
            'tanggal'          => ['type' => 'DATE'],
            'jumlah_dibagikan' => ['type' => 'INT', 'default' => 0],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['program_id', 'item_id']);
        $this->forge->createTable('loyalty_hadiah_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('loyalty_hadiah_realisasi');
        $this->forge->dropTable('loyalty_hadiah_items');
    }
}
