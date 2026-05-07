<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipeToContentItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_content_items', [
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['program', 'biaya'],
                'default'    => 'program',
                'after'      => 'nama',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_content_items', 'tipe');
    }
}
