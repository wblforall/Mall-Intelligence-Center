<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkInitiativeUpdates extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'initiative_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'status'         => ['type' => 'ENUM', 'constraint' => ['on_track', 'at_risk', 'delayed', 'done', 'cancelled'], 'null' => false, 'default' => 'on_track'],
            'progress_pct'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => null],
            'catatan'        => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'hambatan'       => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'updated_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'created_at'     => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('initiative_id');
        $this->forge->createTable('work_initiative_updates');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiative_updates', true);
    }
}
