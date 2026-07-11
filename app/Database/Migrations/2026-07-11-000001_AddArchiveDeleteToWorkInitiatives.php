<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Arsip & jejak hapus untuk Progress Report.
 * - archived_at/by : arsip manual (auto-arsip done/cancelled >30 hari dihitung saat query).
 * - deleted_at/by  : program dihapus pindah ke tab "Dihapus" (is_active=0), historis tidak hilang.
 * Kedua kolom *_by menyimpan users.id (bukan employees.id) agar admin tanpa data karyawan tetap tercatat.
 */
class AddArchiveDeleteToWorkInitiatives extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('work_initiatives', [
            'archived_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'is_active'],
            'archived_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'archived_at'],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'archived_by'],
            'deleted_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'deleted_at'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('work_initiatives', ['archived_at', 'archived_by', 'deleted_at', 'deleted_by']);
    }
}
