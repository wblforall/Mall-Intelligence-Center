<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJabatanIdAndAtasanIdToEmployees extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'jabatan_id' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'null'    => true,
                'default' => null,
                'after'   => 'jabatan',
            ],
            'atasan_id' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'null'    => true,
                'default' => null,
                'after'   => 'jabatan_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', ['jabatan_id', 'atasan_id']);
    }
}
