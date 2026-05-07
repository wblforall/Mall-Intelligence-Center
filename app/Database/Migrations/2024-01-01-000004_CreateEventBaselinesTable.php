<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventBaselinesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'day_label'                    => ['type' => 'VARCHAR', 'constraint' => 20],
            'comparable_period'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'day_type'                     => ['type' => 'ENUM', 'constraint' => ['Weekday', 'Weekend/High Season'], 'default' => 'Weekday'],
            'baseline_traffic'             => ['type' => 'INT', 'default' => 0],
            'baseline_event_area_visitors' => ['type' => 'INT', 'default' => 0],
            'baseline_transactions'        => ['type' => 'INT', 'default' => 0],
            'baseline_tenant_sales'        => ['type' => 'BIGINT', 'default' => 0],
            'baseline_parking_revenue'     => ['type' => 'BIGINT', 'default' => 0],
            'created_at'                   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('event_baselines');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_baselines');
    }
}
