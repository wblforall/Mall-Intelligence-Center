<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBiayaSatuanToLoyaltyPrograms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_programs', [
            'nilai_voucher' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'after'   => 'total_voucher',
                'comment' => 'Nilai nominal per voucher (Rp) — untuk hitung budget realisasi evoucher',
            ],
            'biaya_per_member' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'after'   => 'nilai_voucher',
                'comment' => 'Biaya akuisisi per member (Rp) — untuk hitung budget realisasi member',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_programs', ['nilai_voucher', 'biaya_per_member']);
    }
}
