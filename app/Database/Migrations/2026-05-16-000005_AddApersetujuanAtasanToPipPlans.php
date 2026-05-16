<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApersetujuanAtasanToPipPlans extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pip_plans', [
            'persetujuan_atasan' => [
                'type'       => 'ENUM',
                'constraint' => ['pending','setuju','menolak'],
                'default'    => 'pending',
                'after'      => 'konsekuensi',
            ],
            'catatan_penolakan_atasan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'persetujuan_atasan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pip_plans', ['persetujuan_atasan','catatan_penolakan_atasan']);
    }
}
