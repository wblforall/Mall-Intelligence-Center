<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanImportTrafficToRoles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('roles', [
            'can_import_traffic' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'can_delete_traffic',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('roles', 'can_import_traffic');
    }
}
