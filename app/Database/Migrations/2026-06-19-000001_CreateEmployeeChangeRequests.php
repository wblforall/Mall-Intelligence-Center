<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeChangeRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'requested_by'=> ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true], // users.id
            'field'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'value_old'   => ['type' => 'TEXT', 'null' => true],
            'value_new'   => ['type' => 'TEXT', 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'reviewed_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'catatan'     => ['type' => 'TEXT', 'null' => true], // alasan HR / catatan karyawan
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['employee_id']);
        $this->forge->addKey(['status']);
        $this->forge->createTable('employee_change_requests');
    }

    public function down()
    {
        $this->forge->dropTable('employee_change_requests');
    }
}
