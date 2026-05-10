<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTnaAssessmentItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assessment_id' => ['type' => 'INT', 'unsigned' => true],
            'competency_id' => ['type' => 'INT', 'unsigned' => true],
            'level_given'   => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assessment_id', 'competency_id']);
        $this->forge->createTable('tna_assessment_items');
    }

    public function down()
    {
        $this->forge->dropTable('tna_assessment_items', true);
    }
}
