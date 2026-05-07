<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCreativeRealisasiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'creative_item_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'tanggal'                       => ['type' => 'DATE', 'null' => false],
            'nilai'                         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'nama_influencer'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_name'                     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'original_name'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'serah_terima_file_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'serah_terima_original_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bukti_terpasang_file_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bukti_terpasang_original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'catatan'                       => ['type' => 'TEXT', 'null' => true],
            'created_by'                    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'                    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('creative_item_id');
        $this->forge->createTable('creative_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('creative_realisasi');
    }
}
