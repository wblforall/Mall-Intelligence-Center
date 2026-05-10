<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetencyJabatanMap extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'jabatan_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'competency_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['jabatan_id', 'competency_id']);
        $this->forge->createTable('competency_jabatan_map');
    }

    public function down()
    {
        $this->forge->dropTable('competency_jabatan_map');
    }
}
