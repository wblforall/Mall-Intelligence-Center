<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEeiTables extends Migration
{
    public function up()
    {
        // Dimensions (Belonging, Growth, Recognition, etc.)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'deskripsi'  => ['type' => 'TEXT', 'null' => true],
            'urutan'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('eei_dimensions');

        // Questions per dimension
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dimension_id' => ['type' => 'INT', 'unsigned' => true],
            'pertanyaan'   => ['type' => 'TEXT'],
            'urutan'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'is_reversed'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('dimension_id', 'eei_dimensions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('eei_questions');

        // Survey periods
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_date' => ['type' => 'DATE'],
            'end_date'   => ['type' => 'DATE'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('eei_periods');

        // Responses — intentionally anonymous (dept_id only, no employee_id)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'period_id'   => ['type' => 'INT', 'unsigned' => true],
            'dept_id'     => ['type' => 'INT', 'unsigned' => true],
            'question_id' => ['type' => 'INT', 'unsigned' => true],
            'score'       => ['type' => 'TINYINT', 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('period_id',   'eei_periods',    'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('dept_id',     'departments',    'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'eei_questions',  'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('eei_responses');

        // Completions — tracks WHO completed (not WHAT they answered)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'period_id'    => ['type' => 'INT', 'unsigned' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'completed_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['period_id', 'user_id']);
        $this->forge->addForeignKey('period_id', 'eei_periods', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('eei_completions');
    }

    public function down()
    {
        $this->forge->dropTable('eei_completions');
        $this->forge->dropTable('eei_responses');
        $this->forge->dropTable('eei_periods');
        $this->forge->dropTable('eei_questions');
        $this->forge->dropTable('eei_dimensions');
    }
}
