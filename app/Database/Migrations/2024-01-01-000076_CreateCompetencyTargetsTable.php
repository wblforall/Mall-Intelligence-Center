<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetencyTargetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'competency_id' => ['type' => 'INT', 'unsigned' => true],
            'dept_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'target_level'  => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['competency_id', 'dept_id']);
        $this->forge->createTable('competency_targets');
    }

    public function down()
    {
        $this->forge->dropTable('competency_targets', true);
    }
}
