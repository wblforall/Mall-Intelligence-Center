<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStructuredAnalysisFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_summary_analysis', [
            'highlight'     => ['type' => 'TEXT', 'null' => true],
            'kendala'       => ['type' => 'TEXT', 'null' => true],
            'tindak_lanjut' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_summary_analysis', ['highlight', 'kendala', 'tindak_lanjut']);
    }
}
