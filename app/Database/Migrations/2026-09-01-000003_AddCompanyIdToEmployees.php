<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pengganti `employees.project` (varchar, hanya kenal "eWalk"/"Pentacity") —
 * `company_id` menunjuk ke `companies` yang mencakup seluruh unit bisnis
 * holding, tidak hanya dua mall.
 *
 * SENGAJA nullable dan `project` TIDAK dihapus di migrasi ini. Backfill
 * datanya dikerjakan AppAccessSeeder (setelah baris companies ada), dan
 * `project` baru dihapus di migrasi terpisah setelah seluruh rujukan di kode
 * dipastikan sudah membaca company_id — lihat KONTEKS.md bagian migrasi.
 */
class AddCompanyIdToEmployees extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employees', [
            'company_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'project',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', 'company_id');
    }
}
