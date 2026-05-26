<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJenisToLoyaltyPrograms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_programs', [
            'jenis' => [
                'type'       => 'ENUM',
                'constraint' => ['internal', 'tenant'],
                'default'    => 'internal',
                'after'      => 'nama_program',
            ],
            'nama_tenant' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'jenis',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_programs', ['jenis', 'nama_tenant']);
    }
}
