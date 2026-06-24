<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeadlineToCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('creative_items', [
            'deadline' => [
                'type'       => 'DATE',
                'null'       => true,
                'default'    => null,
                'after'      => 'target_impressions',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('creative_items', 'deadline');
    }
}
