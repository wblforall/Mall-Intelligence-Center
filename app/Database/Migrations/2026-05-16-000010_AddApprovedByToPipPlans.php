<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApprovedByToPipPlans extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pip_plans', [
            'approved_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'created_by_user_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pip_plans', 'approved_by_user_id');
    }
}
