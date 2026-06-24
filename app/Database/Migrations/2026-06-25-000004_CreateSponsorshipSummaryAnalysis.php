<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorshipSummaryAnalysis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'bulan'         => ['type' => 'VARCHAR', 'constraint' => 7],
            'program_id'    => ['type' => 'INT', 'unsigned' => true],
            'highlight'     => ['type' => 'TEXT', 'null' => true],
            'kendala'       => ['type' => 'TEXT', 'null' => true],
            'tindak_lanjut' => ['type' => 'TEXT', 'null' => true],
            'updated_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bulan', 'program_id']);
        $this->forge->createTable('sponsorship_summary_analysis');
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_summary_analysis', true);
    }
}
