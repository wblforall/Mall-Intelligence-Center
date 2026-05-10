<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJabatanLevelToEeiResponses extends Migration
{
    public function up()
    {
        $this->forge->addColumn('eei_responses', [
            'jabatan_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'dept_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('eei_responses', 'jabatan_level');
    }
}
