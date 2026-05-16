<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLoginLockToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'failed_login_attempts' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'must_change_password',
            ],
            'locked_until' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'failed_login_attempts',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['failed_login_attempts', 'locked_until']);
    }
}
