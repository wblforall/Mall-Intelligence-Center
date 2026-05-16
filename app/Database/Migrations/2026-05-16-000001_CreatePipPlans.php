<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePipPlans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'        => ['type' => 'INT', 'unsigned' => true],
            'created_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'judul'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'alasan'             => ['type' => 'TEXT', 'null' => true],
            'tanggal_mulai'      => ['type' => 'DATE'],
            'tanggal_selesai'    => ['type' => 'DATE'],
            'status'             => ['type' => 'ENUM', 'constraint' => ['draft','aktif','selesai','diperpanjang','dihentikan'], 'default' => 'draft'],
            'catatan_penutup'    => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('status');
        $this->forge->createTable('pip_plans');
    }

    public function down()
    {
        $this->forge->dropTable('pip_plans', true);
    }
}
