<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoyaltyRealisasi extends Migration
{
    public function up(): void
    {
        // Tambah total_voucher ke programs (untuk evoucher: total voucher diterbitkan)
        $this->forge->addColumn('event_loyalty_programs', [
            'total_voucher' => [
                'type'    => 'INT',
                'unsigned' => true,
                'null'    => true,
                'default' => null,
                'after'   => 'target_penyerapan',
            ],
        ]);

        // Tabel realisasi harian per program
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id'   => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'jumlah'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('program_id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('event_loyalty_realisasi');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_loyalty_realisasi');
        $this->forge->dropColumn('event_loyalty_programs', 'total_voucher');
    }
}
