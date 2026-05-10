<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTnaAssessmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'period_id'     => ['type' => 'INT', 'unsigned' => true],
            'employee_id'   => ['type' => 'INT', 'unsigned' => true],
            'assessor_type' => ['type' => 'ENUM', 'constraint' => ['self', 'atasan', 'rekan']],
            'assessor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted'], 'default' => 'draft'],
            'submitted_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['period_id', 'employee_id']);
        $this->forge->createTable('tna_assessments');
    }

    public function down()
    {
        $this->forge->dropTable('tna_assessments', true);
    }
}
