<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanViewGanttToRoles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('roles', [
            'can_view_gantt' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'can_approve_pip',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('roles', 'can_view_gantt');
    }
}
