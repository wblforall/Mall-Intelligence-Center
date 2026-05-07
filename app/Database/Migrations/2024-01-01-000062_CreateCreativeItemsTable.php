<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCreativeItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tipe'        => ['type' => 'ENUM', 'constraint' => ['master_design', 'digital', 'cetak', 'influencer', 'media_prescon'], 'null' => false],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'platform'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tanggal_take'=> ['type' => 'DATE', 'null' => true],
            'jam_take'    => ['type' => 'TIME', 'null' => true],
            'pic'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'deskripsi'   => ['type' => 'TEXT', 'null' => true],
            'budget'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'review', 'approved', 'revision'], 'default' => 'draft'],
            'catatan'     => ['type' => 'TEXT', 'null' => true],
            'urutan'      => ['type' => 'INT', 'default' => 0],
            'created_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tipe');
        $this->forge->createTable('creative_items');
    }

    public function down()
    {
        $this->forge->dropTable('creative_items');
    }
}
