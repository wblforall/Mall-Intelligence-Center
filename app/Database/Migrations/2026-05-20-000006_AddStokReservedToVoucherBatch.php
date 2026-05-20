<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStokReservedToVoucherBatch extends Migration
{
    public function up()
    {
        $this->forge->addColumn('stock_voucher_batch', [
            'stok_reserved' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'sisa_kode',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('stock_voucher_batch', 'stok_reserved');
    }
}
