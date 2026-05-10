<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTnaAssessmentItemsForIndicators extends Migration
{
    public function up()
    {
        // Drop old table and recreate with indicator-based schema
        $this->forge->dropTable('tna_assessment_items', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assessment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'indicator_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'met'           => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assessment_id', 'indicator_id']);
        $this->forge->addForeignKey('assessment_id', 'tna_assessments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('indicator_id', 'competency_indicators', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tna_assessment_items');
    }

    public function down()
    {
        $this->forge->dropTable('tna_assessment_items', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assessment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'competency_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'level_given'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assessment_id', 'competency_id']);
        $this->forge->createTable('tna_assessment_items');
    }
}
