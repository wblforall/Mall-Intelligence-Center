<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeadlineToEventCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_items', [
            'deadline' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
                'after'   => 'target_impressions',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_items', 'deadline');
    }
}
