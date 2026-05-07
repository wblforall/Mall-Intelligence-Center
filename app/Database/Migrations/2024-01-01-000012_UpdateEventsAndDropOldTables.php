<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateEventsAndDropOldTables extends Migration
{
    public function up()
    {
        // Add new columns to events
        $this->forge->addColumn('events', [
            'tema' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'after'      => 'name',
            ],
            'content' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'tema',
            ],
        ]);

        // Drop old tables no longer used
        foreach (['event_configs', 'event_baselines', 'event_daily_tracking', 'event_tenants', 'event_tenant_impact', 'event_user_access'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('events', ['tema', 'content']);
    }
}
