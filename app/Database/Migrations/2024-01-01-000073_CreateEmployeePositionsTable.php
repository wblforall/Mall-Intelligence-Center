<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeePositionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'     => ['type' => 'INT', 'unsigned' => true],
            'jabatan'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'dept_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tanggal_mulai'   => ['type' => 'DATE'],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'keterangan'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('employee_positions');
    }

    public function down()
    {
        $this->forge->dropTable('employee_positions', true);
    }
}
