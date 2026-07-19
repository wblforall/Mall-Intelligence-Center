<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom mall pada program sponsorship standalone (ewalk/pentacity/both,
 * nullable) — dipakai Laporan Bulanan untuk rekap program per mall,
 * konsisten dengan loyalty_programs.mall.
 */
class AddMallToSponsorshipPrograms extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE sponsorship_programs ADD COLUMN mall VARCHAR(20) NULL AFTER nama_program"
        );
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE sponsorship_programs DROP COLUMN mall");
    }
}
