<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoyaltyVoucherItems extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'program_id'        => ['type' => 'INT', 'null' => false],
            'nama_voucher'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'nilai_voucher'     => ['type' => 'BIGINT', 'default' => 0],
            'total_diterbitkan' => ['type' => 'INT', 'default' => 0],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_by'        => ['type' => 'INT', 'null' => false],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('program_id');
        $this->forge->createTable('loyalty_voucher_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('loyalty_voucher_items', true);
    }
}
