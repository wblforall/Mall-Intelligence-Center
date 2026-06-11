<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDateClosedToCreativeItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('creative_items', [
            'tanggal'   => ['type' => 'DATE', 'null' => true, 'after' => 'deskripsi'],       // tanggal materi (semua tipe) — penentu bulan
            'is_closed' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'status'],
            'closed_at' => ['type' => 'DATE', 'null' => true, 'after' => 'is_closed'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('creative_items', ['tanggal', 'is_closed', 'closed_at']);
    }
}
