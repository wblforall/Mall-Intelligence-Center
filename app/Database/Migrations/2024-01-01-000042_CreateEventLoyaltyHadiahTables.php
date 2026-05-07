<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventLoyaltyHadiahTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'program_id'   => ['type' => 'INT', 'null' => false],
            'nama_hadiah'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'stok'         => ['type' => 'INT', 'default' => 0],
            'nilai_satuan' => ['type' => 'BIGINT', 'default' => 0],
            'catatan'      => ['type' => 'TEXT', 'null' => true],
            'created_by'   => ['type' => 'INT', 'null' => false],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('event_loyalty_hadiah_items');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'program_id'       => ['type' => 'INT', 'null' => false],
            'item_id'          => ['type' => 'INT', 'null' => false],
            'tanggal'          => ['type' => 'DATE', 'null' => false],
            'jumlah_dibagikan' => ['type' => 'INT', 'default' => 0],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'null' => false],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['program_id', 'item_id', 'tanggal']);
        $this->forge->createTable('event_loyalty_hadiah_realisasi');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_loyalty_hadiah_realisasi', true);
        $this->forge->dropTable('event_loyalty_hadiah_items', true);
    }
}
