<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLevelDescToQuestions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('competency_questions', [
            'level_1' => ['type' => 'TEXT', 'null' => true, 'after' => 'pertanyaan'],
            'level_2' => ['type' => 'TEXT', 'null' => true, 'after' => 'level_1'],
            'level_3' => ['type' => 'TEXT', 'null' => true, 'after' => 'level_2'],
            'level_4' => ['type' => 'TEXT', 'null' => true, 'after' => 'level_3'],
            'level_5' => ['type' => 'TEXT', 'null' => true, 'after' => 'level_4'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('competency_questions', ['level_1','level_2','level_3','level_4','level_5']);
    }
}
