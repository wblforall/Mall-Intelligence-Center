<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEvalFieldsToLoyaltyPrograms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('loyalty_programs', [
            'eval_status'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'locked_at'],
            'eval_kendala'     => ['type' => 'TEXT', 'null' => true, 'after' => 'eval_status'],
            'eval_rekomendasi' => ['type' => 'TEXT', 'null' => true, 'after' => 'eval_kendala'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('loyalty_programs', ['eval_status', 'eval_kendala', 'eval_rekomendasi']);
    }
}
