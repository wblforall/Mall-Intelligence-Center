<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFrekuensiReviewToPipPlans extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pip_plans', [
            'frekuensi_review' => [
                'type'       => 'ENUM',
                'constraint' => ['mingguan', '2mingguan', 'bulanan'],
                'default'    => 'mingguan',
                'after'      => 'tanggal_selesai',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pip_plans', 'frekuensi_review');
    }
}
