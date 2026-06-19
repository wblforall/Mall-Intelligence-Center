<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipeToAppraisalPeriods extends Migration
{
    public function up()
    {
        $this->forge->addColumn('appraisal_periods', [
            'tipe' => ['type' => 'ENUM', 'constraint' => ['reguler', 'khusus'], 'default' => 'reguler', 'after' => 'nama'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('appraisal_periods', ['tipe']);
    }
}
