<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorshipSponsorItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'       => ['type' => 'INT', 'unsigned' => true],
            'sponsor_id'       => ['type' => 'INT', 'unsigned' => true],
            'deskripsi_barang' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'qty'              => ['type' => 'INT', 'null' => true],
            'nilai'            => ['type' => 'BIGINT', 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sponsor_id');
        $this->forge->createTable('sponsorship_sponsor_items');
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_sponsor_items', true);
    }
}
