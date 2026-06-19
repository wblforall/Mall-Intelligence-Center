<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermsChangedAtToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'perms_changed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'is_active'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['perms_changed_at']);
    }
}
