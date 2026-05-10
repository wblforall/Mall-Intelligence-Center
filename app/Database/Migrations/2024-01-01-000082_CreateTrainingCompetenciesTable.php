<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrainingCompetenciesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'     => ['type' => 'INT', 'unsigned' => true],
            'competency_id'  => ['type' => 'INT', 'unsigned' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['program_id', 'competency_id']);
        $this->forge->createTable('training_competencies');
    }

    public function down()
    {
        $this->forge->dropTable('training_competencies', true);
    }
}
