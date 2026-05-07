<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBuktiTerpasangToCreativeRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_realisasi', [
            'bukti_terpasang_file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'serah_terima_original_name',
            ],
            'bukti_terpasang_original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'bukti_terpasang_file_name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_realisasi', ['bukti_terpasang_file_name', 'bukti_terpasang_original_name']);
    }
}
