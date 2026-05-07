<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTargetTypeToLoyalty extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('event_loyalty_programs', [
            'target_type' => [
                'type'       => 'ENUM',
                'constraint' => ['member', 'evoucher'],
                'null'       => true,
                'default'    => null,
                'after'      => 'mekanisme',
            ],
            'target_penyerapan' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'target_peserta',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('event_loyalty_programs', ['target_type', 'target_penyerapan']);
    }
}
