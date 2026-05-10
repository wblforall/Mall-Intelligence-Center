<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWeightsTnaPeriods extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tna_periods', [
            'weight_self'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 20, 'after' => 'status'],
            'weight_atasan' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 50, 'after' => 'weight_self'],
            'weight_rekan'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 30, 'after' => 'weight_atasan'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tna_periods', ['weight_self', 'weight_atasan', 'weight_rekan']);
    }
}
