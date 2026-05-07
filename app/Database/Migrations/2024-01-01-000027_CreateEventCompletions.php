<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventCompletions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'     => ['type' => 'INT', 'unsigned' => true],
            'module'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'completed_by' => ['type' => 'INT', 'unsigned' => true],
            'completed_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'module']);
        $this->forge->createTable('event_completions');
    }

    public function down()
    {
        $this->forge->dropTable('event_completions');
    }
}
