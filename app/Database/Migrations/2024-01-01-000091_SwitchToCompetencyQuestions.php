<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SwitchToCompetencyQuestions extends Migration
{
    public function up()
    {
        // Drop old tables (items first due to FK)
        $this->db->query('DROP TABLE IF EXISTS tna_assessment_items');
        $this->db->query('DROP TABLE IF EXISTS competency_indicators');

        // competency_questions
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'competency_id'  => ['type' => 'INT', 'unsigned' => true],
            'pertanyaan'     => ['type' => 'TEXT'],
            'urutan'         => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('competency_id', 'competencies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('competency_questions');

        // tna_assessment_items (new schema)
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assessment_id' => ['type' => 'INT', 'unsigned' => true],
            'question_id'   => ['type' => 'INT', 'unsigned' => true],
            'score'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assessment_id', 'question_id']);
        $this->forge->addForeignKey('assessment_id', 'tna_assessments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'competency_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tna_assessment_items');
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS tna_assessment_items');
        $this->db->query('DROP TABLE IF EXISTS competency_questions');
    }
}
