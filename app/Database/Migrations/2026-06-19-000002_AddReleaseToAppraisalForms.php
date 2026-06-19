<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReleaseToAppraisalForms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('appraisal_forms', [
            'released_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'finalized_at'],
            'released_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'released_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('appraisal_forms', ['released_at', 'released_by']);
    }
}
