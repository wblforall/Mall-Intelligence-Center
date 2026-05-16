<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToPipPlans extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pip_plans', [
            'level_sp' => [
                'type'       => 'ENUM',
                'constraint' => ['none','sp1','sp2','sp3','phk'],
                'default'    => 'none',
                'after'      => 'alasan',
            ],
            'dukungan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'level_sp',
            ],
            'konsekuensi' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'dukungan',
            ],
            'persetujuan_karyawan' => [
                'type'       => 'ENUM',
                'constraint' => ['pending','setuju','menolak'],
                'default'    => 'pending',
                'after'      => 'konsekuensi',
            ],
            'catatan_penolakan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'persetujuan_karyawan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pip_plans', ['level_sp','dukungan','konsekuensi','persetujuan_karyawan','catatan_penolakan']);
    }
}
