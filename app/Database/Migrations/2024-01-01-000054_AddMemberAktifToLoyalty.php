<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMemberAktifToLoyalty extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_loyalty_programs', [
            'target_member_aktif' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'target_peserta',
            ],
        ]);

        $this->forge->addColumn('event_loyalty_realisasi', [
            'member_aktif' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
                'after'    => 'jumlah',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_loyalty_programs', 'target_member_aktif');
        $this->forge->dropColumn('event_loyalty_realisasi', 'member_aktif');
    }
}
