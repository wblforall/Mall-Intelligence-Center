<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom mall pada program loyalty standalone (ewalk/pentacity/both, nullable).
 * Dipakai Laporan Bulanan untuk menghitung jumlah program baru per mall —
 * jalur event sudah punya mall via events.mall, standalone belum.
 */
class AddMallToLoyaltyPrograms extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE loyalty_programs ADD COLUMN mall VARCHAR(20) NULL AFTER jenis"
        );
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE loyalty_programs DROP COLUMN mall");
    }
}
