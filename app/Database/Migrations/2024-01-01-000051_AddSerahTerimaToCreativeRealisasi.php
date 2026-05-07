<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSerahTerimaToCreativeRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_realisasi', [
            'serah_terima_file_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'original_name'],
            'serah_terima_original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'serah_terima_file_name'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_realisasi', ['serah_terima_file_name', 'serah_terima_original_name']);
    }
}
