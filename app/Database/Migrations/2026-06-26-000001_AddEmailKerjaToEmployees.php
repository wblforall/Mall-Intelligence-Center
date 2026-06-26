<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailKerjaToEmployees extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employees', [
            'email_kerja' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'after'      => 'email',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', 'email_kerja');
    }
}
