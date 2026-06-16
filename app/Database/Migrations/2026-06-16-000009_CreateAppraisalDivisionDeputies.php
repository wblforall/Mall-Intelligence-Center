<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalDivisionDeputies extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'division_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true], // Deputy penyusun template level Manager/Dept Head
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('division_id'); // satu Deputy per divisi
        $this->forge->createTable('appraisal_division_deputies');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_division_deputies', true);
    }
}
