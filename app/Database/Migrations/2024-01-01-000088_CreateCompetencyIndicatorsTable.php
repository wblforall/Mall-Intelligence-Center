<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetencyIndicatorsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'competency_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'level'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
            'deskripsi'     => ['type' => 'TEXT', 'null' => false],
            'urutan'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('competency_id');
        $this->forge->addForeignKey('competency_id', 'competencies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('competency_indicators');
    }

    public function down()
    {
        $this->forge->dropTable('competency_indicators', true);
    }
}
