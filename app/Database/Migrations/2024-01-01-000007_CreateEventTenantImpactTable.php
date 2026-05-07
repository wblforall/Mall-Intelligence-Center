<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventTenantImpactTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tenant_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tracking_date'       => ['type' => 'DATE'],
            'actual_sales'        => ['type' => 'BIGINT', 'default' => 0],
            'receipts'            => ['type' => 'INT', 'default' => 0],
            'voucher_redemptions' => ['type' => 'INT', 'default' => 0],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['event_id', 'tenant_id', 'tracking_date']);
        $this->forge->createTable('event_tenant_impact');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_tenant_impact');
    }
}
