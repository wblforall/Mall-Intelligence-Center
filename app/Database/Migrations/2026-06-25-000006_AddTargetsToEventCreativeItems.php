<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddTargetsToEventCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_items', [
            'target_reach' => [
                'type'    => 'INT',
                'unsigned' => true,
                'null'    => true,
                'default' => null,
                'after'   => 'budget',
            ],
            'target_impressions' => [
                'type'    => 'INT',
                'unsigned' => true,
                'null'    => true,
                'default' => null,
                'after'   => 'target_reach',
            ],
        ]);
    }
    public function down()
    {
        $this->forge->dropColumn('event_creative_items', ['target_reach', 'target_impressions']);
    }
}
