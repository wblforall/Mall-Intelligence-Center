<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoyaltySummaryAnalysis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bulan'      => ['type' => 'VARCHAR', 'constraint' => 7],            // YYYY-MM
            'source'     => ['type' => 'VARCHAR', 'constraint' => 2],            // 's' = standalone, 'e' = event
            'program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'analisa'    => ['type' => 'TEXT', 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bulan', 'source', 'program_id']);
        $this->forge->createTable('loyalty_summary_analysis');
    }

    public function down()
    {
        $this->forge->dropTable('loyalty_summary_analysis');
    }
}
