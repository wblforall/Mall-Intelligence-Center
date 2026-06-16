<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalTemplateKpi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'INT', 'unsigned' => true],
            // area kinerja utama (slug baku): pencapaian_target, program_kerja, metode_kerja, pelaporan
            'area'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'indikator'   => ['type' => 'TEXT'],
            // unit pengukur: persen, jumlah_nilai, minggu, bulan
            'unit'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'bobot'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0], // Σ per template = 100
            'target'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'urutan'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addForeignKey('template_id', 'appraisal_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('appraisal_template_kpi');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_template_kpi', true);
    }
}
