<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalFormKpi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'form_id'   => ['type' => 'INT', 'unsigned' => true],
            'area'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'indikator' => ['type' => 'TEXT'],
            'unit'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'bobot'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'target'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'realisasi' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'skor'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true], // 0-100, input manual penilai
            'urutan'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('form_id');
        $this->forge->addForeignKey('form_id', 'appraisal_forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('appraisal_form_kpi');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_form_kpi', true);
    }
}
