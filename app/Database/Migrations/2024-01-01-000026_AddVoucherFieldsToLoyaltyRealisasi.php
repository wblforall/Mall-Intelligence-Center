<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherFieldsToLoyaltyRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_loyalty_realisasi', [
            'tersebar' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'jumlah',
            ],
            'terpakai' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'tersebar',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_loyalty_realisasi', ['tersebar', 'terpakai']);
    }
}
