<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokensToPipPlans extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pip_plans', [
            'token_atasan' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'catatan_penolakan_atasan',
            ],
            'token_karyawan' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'token_atasan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pip_plans', ['token_atasan','token_karyawan']);
    }
}
