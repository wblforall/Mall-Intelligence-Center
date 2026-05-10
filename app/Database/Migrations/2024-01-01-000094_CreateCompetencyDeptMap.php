<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetencyDeptMap extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dept_id'        => ['type' => 'INT', 'unsigned' => true],
            'competency_id'  => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['dept_id', 'competency_id']);
        $this->forge->addForeignKey('dept_id',       'departments',  'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('competency_id', 'competencies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('competency_dept_map');
    }

    public function down()
    {
        $this->forge->dropTable('competency_dept_map');
    }
}
