<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPicToCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_items', [
            'pic' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null, 'after' => 'jam_take'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_items', 'pic');
    }
}
