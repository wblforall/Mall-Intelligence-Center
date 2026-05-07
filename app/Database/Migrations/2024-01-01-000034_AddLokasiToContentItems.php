<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLokasiToContentItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_content_items', [
            'lokasi' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'pic'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_content_items', 'lokasi');
    }
}
