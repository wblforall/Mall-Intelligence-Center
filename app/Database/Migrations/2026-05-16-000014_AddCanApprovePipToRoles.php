<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanApprovePipToRoles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('roles', [
            'can_approve_pip' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'can_approve_events',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('roles', 'can_approve_pip');
    }
}
