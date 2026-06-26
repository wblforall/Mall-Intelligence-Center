<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDivisionIdToEmployees extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employees', [
            'division_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'dept_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', 'division_id');
    }
}
