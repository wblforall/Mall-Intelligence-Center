<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventExhibitorsAndSponsors extends Migration
{
    public function up()
    {
        // Exhibitors (Casual Leasing)
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'      => ['type' => 'INT', 'unsigned' => true],
            'nama_exhibitor'=> ['type' => 'VARCHAR', 'constraint' => 150],
            'kategori'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'nilai_dealing' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'lokasi_booth'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'catatan'       => ['type' => 'TEXT', 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_exhibitors');

        // Sponsors
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'         => ['type' => 'INT', 'unsigned' => true],
            'nama_sponsor'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis'            => ['type' => 'ENUM', 'constraint' => ['cash', 'barang'], 'default' => 'cash'],
            'nilai'            => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'deskripsi_barang' => ['type' => 'TEXT', 'null' => true],
            'detail'           => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_sponsors');
    }

    public function down()
    {
        $this->forge->dropTable('event_exhibitors');
        $this->forge->dropTable('event_sponsors');
    }
}
