<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTakeDateToCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_items', [
            'tanggal_take' => ['type' => 'DATE', 'null' => true, 'default' => null, 'after' => 'platform'],
            'jam_take'     => ['type' => 'TIME', 'null' => true, 'default' => null, 'after' => 'tanggal_take'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_items', ['tanggal_take', 'jam_take']);
    }
}
