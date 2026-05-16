<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePipItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pip_id'     => ['type' => 'INT', 'unsigned' => true],
            'aspek'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'masalah'    => ['type' => 'TEXT', 'null' => true],
            'target'     => ['type' => 'TEXT', 'null' => true],
            'metrik'     => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'deadline'   => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pip_id');
        $this->forge->createTable('pip_items');
    }

    public function down()
    {
        $this->forge->dropTable('pip_items', true);
    }
}
