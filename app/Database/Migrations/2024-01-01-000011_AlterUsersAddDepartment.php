<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddDepartment extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'department_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'role',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'department_id');
    }
}
