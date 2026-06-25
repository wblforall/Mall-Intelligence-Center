<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkInitiativeFlags extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'initiative_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'flagged_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'flagged_at'     => ['type' => 'DATETIME', 'null' => false],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['initiative_id', 'is_active']);
        $this->forge->createTable('work_initiative_flags');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiative_flags', true);
    }
}
