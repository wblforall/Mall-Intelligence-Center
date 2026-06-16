<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalTemplateCompetency extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'INT', 'unsigned' => true],
            'nama_aspek'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'deskripsi'   => ['type' => 'TEXT', 'null' => true],
            'urutan'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addForeignKey('template_id', 'appraisal_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('appraisal_template_competency');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_template_competency', true);
    }
}
