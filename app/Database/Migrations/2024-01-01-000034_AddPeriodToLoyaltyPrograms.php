<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPeriodToLoyaltyPrograms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_programs', [
            'tanggal_mulai' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'nama_program',
            ],
            'tanggal_selesai' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'tanggal_mulai',
            ],
            'jam_mulai' => [
                'type'       => 'TIME',
                'null'       => true,
                'after'      => 'tanggal_selesai',
            ],
            'jam_selesai' => [
                'type'       => 'TIME',
                'null'       => true,
                'after'      => 'jam_mulai',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_programs', ['tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai']);
    }
}
