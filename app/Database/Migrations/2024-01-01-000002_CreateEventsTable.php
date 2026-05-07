<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'mall'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'start_date'  => ['type' => 'DATE', 'null' => true],
            'event_days'  => ['type' => 'INT', 'constraint' => 3, 'default' => 1],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'completed'], 'default' => 'draft'],
            'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('events');
    }

    public function down(): void
    {
        $this->forge->dropTable('events');
    }
}
