<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePipAspekMaster extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'aspek'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'kategori'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'target_default' => ['type' => 'TEXT', 'null' => true],
            'metrik_default' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'aktif'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pip_aspek_master');
    }

    public function down()
    {
        $this->forge->dropTable('pip_aspek_master', true);
    }
}
