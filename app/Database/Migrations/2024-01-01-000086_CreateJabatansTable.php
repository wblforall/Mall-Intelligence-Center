<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJabatansTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'grade'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 5,
                              'comment' => '1=paling atas (GM/DGM), makin besar makin bawah'],
            'dept_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'division_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['dept_id']);
        $this->forge->addKey(['division_id']);
        $this->forge->createTable('jabatans');
    }

    public function down()
    {
        $this->forge->dropTable('jabatans', true);
    }
}
