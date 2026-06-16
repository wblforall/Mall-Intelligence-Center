<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalTemplates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'jabatan_id'       => ['type' => 'INT', 'unsigned' => true],
            'nama'             => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            // draft = manager menyusun, submitted = diajukan ke HR, approved = disetujui HR
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'approved'], 'default' => 'draft'],
            // bobot final (disepakati fix 0.60 / 0.40, disimpan untuk fleksibilitas)
            'bobot_kpi'        => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0.60],
            'bobot_kompetensi' => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0.40],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'submitted_at'     => ['type' => 'DATETIME', 'null' => true],
            'approved_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'catatan_hr'       => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('jabatan_id'); // satu template per jabatan
        $this->forge->createTable('appraisal_templates');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_templates', true);
    }
}
