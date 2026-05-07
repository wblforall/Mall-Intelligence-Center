<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLockedToLoyaltyPrograms extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('loyalty_programs', [
            'locked'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false, 'after' => 'status'],
            'locked_by' => ['type' => 'INT',     'unsigned' => true, 'null' => true, 'default' => null, 'after' => 'locked'],
            'locked_at' => ['type' => 'DATETIME','null' => true, 'default' => null, 'after' => 'locked_by'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('loyalty_programs', ['locked', 'locked_by', 'locked_at']);
    }
}
