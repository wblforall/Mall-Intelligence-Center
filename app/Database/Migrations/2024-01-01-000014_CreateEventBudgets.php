<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventBudgets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'      => ['type' => 'INT', 'unsigned' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true],
            'kategori'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'keterangan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jumlah'        => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['event_id', 'department_id']);
        $this->forge->createTable('event_budgets');
    }

    public function down()
    {
        $this->forge->dropTable('event_budgets');
    }
}
