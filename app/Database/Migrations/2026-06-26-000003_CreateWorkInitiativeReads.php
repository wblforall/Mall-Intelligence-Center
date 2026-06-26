<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkInitiativeReads extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'initiative_id'  => ['type' => 'INT', 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'last_read_gm_at'=> ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['initiative_id', 'user_id']);
        $this->forge->createTable('work_initiative_reads');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiative_reads');
    }
}
