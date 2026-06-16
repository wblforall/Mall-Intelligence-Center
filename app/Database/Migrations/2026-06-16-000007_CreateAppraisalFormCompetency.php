<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalFormCompetency extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'form_id'    => ['type' => 'INT', 'unsigned' => true],
            'nama_aspek' => ['type' => 'VARCHAR', 'constraint' => 150],
            'deskripsi'  => ['type' => 'TEXT', 'null' => true],
            'nilai'      => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true], // 1-5
            'urutan'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('form_id');
        $this->forge->addForeignKey('form_id', 'appraisal_forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('appraisal_form_competency');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_form_competency', true);
    }
}
