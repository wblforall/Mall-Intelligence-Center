<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventCreativeTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'   => ['type' => 'INT', 'unsigned' => true],
            'tipe'       => ['type' => 'ENUM', 'constraint' => ['master_design', 'digital', 'cetak', 'influencer']],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'platform'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
            'deskripsi'  => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'budget'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'     => ['type' => 'ENUM', 'constraint' => ['draft', 'review', 'approved', 'revision'], 'default' => 'draft'],
            'catatan'    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'urutan'     => ['type' => 'INT', 'default' => 0],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_creative_items');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'creative_item_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id'         => ['type' => 'INT', 'unsigned' => true],
            'file_name'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'catatan'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'uploaded_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('creative_item_id');
        $this->forge->createTable('event_creative_files');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'         => ['type' => 'INT', 'unsigned' => true],
            'creative_item_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'          => ['type' => 'DATE'],
            'nilai'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'nama_influencer'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'catatan'          => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event_id', 'creative_item_id']);
        $this->forge->createTable('event_creative_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('event_creative_realisasi', true);
        $this->forge->dropTable('event_creative_files', true);
        $this->forge->dropTable('event_creative_items', true);
    }
}
