<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFillTokenToTnaAssessments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tna_assessments', [
            'fill_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'submitted_at',
            ],
            'token_expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'fill_token',
            ],
        ]);

        $this->forge->addKey('fill_token', false, true); // unique index
        $this->forge->processIndexes('tna_assessments');
    }

    public function down()
    {
        $this->forge->dropColumn('tna_assessments', 'fill_token');
        $this->forge->dropColumn('tna_assessments', 'token_expires_at');
    }
}
