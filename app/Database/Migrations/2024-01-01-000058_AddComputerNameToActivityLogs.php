<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComputerNameToActivityLogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('activity_logs', [
            'computer_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'ip_address'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('activity_logs', 'computer_name');
    }
}
