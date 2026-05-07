<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCreativeInsightTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'creative_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'tanggal'          => ['type' => 'DATE', 'null' => false],
            'platform'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'reach'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'impressions'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'views'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'likes'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'comments'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shares'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'saves'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'followers_gained' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'file_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'original_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('creative_item_id');
        $this->forge->createTable('creative_insight');
    }

    public function down()
    {
        $this->forge->dropTable('creative_insight');
    }
}
