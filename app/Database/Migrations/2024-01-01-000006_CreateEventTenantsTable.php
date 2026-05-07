<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventTenantsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 200],
            'category'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'participating_promo'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'baseline_sales'     => ['type' => 'BIGINT', 'default' => 0],
            'event_relevance'    => ['type' => 'ENUM', 'constraint' => ['High', 'Medium', 'Low'], 'default' => 'Medium'],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('event_tenants');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_tenants');
    }
}
