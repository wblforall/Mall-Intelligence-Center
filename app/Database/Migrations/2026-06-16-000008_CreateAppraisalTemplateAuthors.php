<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalTemplateAuthors extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dept_id'    => ['type' => 'INT', 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true], // dept head yang ditunjuk menyusun template
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('dept_id'); // satu penyusun per dept
        $this->forge->createTable('appraisal_template_authors');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_template_authors', true);
    }
}
