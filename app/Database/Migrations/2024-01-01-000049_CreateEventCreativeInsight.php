<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventCreativeInsight extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'         => ['type' => 'INT', 'unsigned' => true],
            'creative_item_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'          => ['type' => 'DATE'],
            'platform'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
            'reach'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'impressions'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'views'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'likes'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'comments'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shares'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'saves'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'followers_gained' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'catatan'          => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event_id', 'creative_item_id']);
        $this->forge->createTable('event_creative_insight');
    }

    public function down()
    {
        $this->forge->dropTable('event_creative_insight', true);
    }
}
