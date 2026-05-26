<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdpPlans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'           => ['type' => 'INT', 'unsigned' => true],
            'tna_period_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'periode_label'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'tahun'                 => ['type' => 'SMALLINT', 'unsigned' => true],
            'tujuan_karir'          => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'catatan'               => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'status'                => ['type' => 'ENUM', 'constraint' => ['draft','aktif','selesai','dibatalkan'], 'default' => 'draft'],
            'token_atasan'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'persetujuan_atasan'    => ['type' => 'ENUM', 'constraint' => ['pending','setuju','menolak'], 'default' => 'pending'],
            'catatan_penolakan'     => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_by_user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'approved_by_user_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'approved_at'           => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_at'            => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('tna_period_id');
        $this->forge->createTable('idp_plans');
    }

    public function down()
    {
        $this->forge->dropTable('idp_plans', true);
    }
}
