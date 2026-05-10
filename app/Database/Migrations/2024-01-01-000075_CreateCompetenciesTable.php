<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetenciesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'kategori'  => ['type' => 'ENUM', 'constraint' => ['hard', 'soft'], 'default' => 'hard'],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'level_1'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'level_2'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'level_3'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'level_4'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'level_5'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('competencies');
    }

    public function down()
    {
        $this->forge->dropTable('competencies', true);
    }
}
