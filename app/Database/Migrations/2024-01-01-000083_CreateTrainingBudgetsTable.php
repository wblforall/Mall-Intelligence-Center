<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrainingBudgetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dept_id'    => ['type' => 'INT', 'unsigned' => true],
            'tahun'      => ['type' => 'SMALLINT', 'unsigned' => true],
            'anggaran'   => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['dept_id', 'tahun']);
        $this->forge->createTable('training_budgets');
    }

    public function down()
    {
        $this->forge->dropTable('training_budgets', true);
    }
}
