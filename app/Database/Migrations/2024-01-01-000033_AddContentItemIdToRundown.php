<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContentItemIdToRundown extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_rundown', [
            'content_item_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'event_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_rundown', 'content_item_id');
    }
}
