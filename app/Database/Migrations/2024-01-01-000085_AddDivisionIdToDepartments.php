<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDivisionIdToDepartments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('departments', [
            'division_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('departments', 'division_id');
    }
}
