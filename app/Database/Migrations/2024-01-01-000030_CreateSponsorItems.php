<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sponsor_id'       => ['type' => 'INT', 'unsigned' => true],
            'deskripsi_barang' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'qty'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nilai'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sponsor_id');
        $this->forge->createTable('event_sponsor_items');

        // Migrate existing barang sponsor data into the new table
        $this->db->query("
            INSERT INTO event_sponsor_items (sponsor_id, deskripsi_barang, qty, nilai, created_at)
            SELECT id, deskripsi_barang, qty, nilai, created_at
            FROM event_sponsors
            WHERE jenis = 'barang'
        ");
    }

    public function down()
    {
        $this->forge->dropTable('event_sponsor_items');
    }
}
