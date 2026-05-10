<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorshipSponsors extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'  => ['type' => 'INT', 'unsigned' => true],
            'nama_sponsor'=> ['type' => 'VARCHAR', 'constraint' => 255],
            'kategori'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'jenis'       => ['type' => 'ENUM', 'constraint' => ['cash', 'barang'], 'default' => 'cash'],
            'nilai'       => ['type' => 'BIGINT', 'default' => 0],
            'status_deal' => ['type' => 'ENUM', 'constraint' => ['prospek', 'negosiasi', 'terkonfirmasi', 'lunas', 'batal'], 'default' => 'prospek'],
            'detail'      => ['type' => 'TEXT', 'null' => true],
            'catatan'     => ['type' => 'TEXT', 'null' => true],
            'created_by'  => ['type' => 'INT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('sponsorship_sponsors');
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_sponsors', true);
    }
}
