<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStokReservedToStockBarang extends Migration
{
    public function up()
    {
        $this->forge->addColumn('stock_barang', [
            'stok_reserved' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'stok_tersedia',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('stock_barang', 'stok_reserved');
    }
}
