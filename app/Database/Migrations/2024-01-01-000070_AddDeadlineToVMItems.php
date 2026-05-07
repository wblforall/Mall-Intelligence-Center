<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeadlineToVMItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_vm_items', [
            'tanggal_deadline' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
                'after'   => 'catatan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_vm_items', 'tanggal_deadline');
    }
}
