<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventUserAccessTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'access_level' => ['type' => 'ENUM', 'constraint' => ['view', 'edit', 'admin'], 'default' => 'view'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['event_id', 'user_id']);
        $this->forge->createTable('event_user_access');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_user_access');
    }
}
