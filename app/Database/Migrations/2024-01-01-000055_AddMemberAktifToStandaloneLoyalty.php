<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMemberAktifToStandaloneLoyalty extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_programs', [
            'target_member_aktif' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'null'    => true,
                'default' => null,
                'after'   => 'target_peserta',
            ],
        ]);

        $this->forge->addColumn('loyalty_realisasi', [
            'member_aktif' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'default' => 0,
                'after'   => 'jumlah',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_programs', 'target_member_aktif');
        $this->forge->dropColumn('loyalty_realisasi', 'member_aktif');
    }
}
