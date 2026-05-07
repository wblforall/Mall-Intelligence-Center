<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFotoToVMRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_vm_realisasi', [
            'foto_file_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'catatan'],
            'foto_original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'foto_file_name'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_vm_realisasi', ['foto_file_name', 'foto_original_name']);
    }
}
