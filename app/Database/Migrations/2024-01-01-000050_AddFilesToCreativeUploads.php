<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFilesToCreativeUploads extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_creative_realisasi', [
            'file_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'nama_influencer'],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'file_name'],
        ]);
        $this->forge->addColumn('event_creative_insight', [
            'file_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'followers_gained'],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'file_name'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_creative_realisasi', ['file_name', 'original_name']);
        $this->forge->dropColumn('event_creative_insight',   ['file_name', 'original_name']);
    }
}
