<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventExhibitorTargetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'auto_increment' => true],
            'event_id'             => ['type' => 'INT', 'null' => false],
            'target_jumlah'        => ['type' => 'INT', 'default' => 0],
            'target_nilai_dealing' => ['type' => 'BIGINT', 'default' => 0],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('event_id');
        $this->forge->createTable('event_exhibitor_targets');
    }

    public function down()
    {
        $this->forge->dropTable('event_exhibitor_targets');
    }
}
