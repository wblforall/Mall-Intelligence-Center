<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nik'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nama'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis_kelamin'  => ['type' => 'ENUM', 'constraint' => ['L', 'P'], 'null' => true],
            'tanggal_lahir'  => ['type' => 'DATE', 'null' => true],
            'tanggal_masuk'  => ['type' => 'DATE'],
            'dept_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'jabatan'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'no_hp'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['aktif', 'resign', 'cuti_panjang', 'pensiun'], 'default' => 'aktif'],
            'foto'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nik');
        $this->forge->createTable('employees');
    }

    public function down()
    {
        $this->forge->dropTable('employees', true);
    }
}
