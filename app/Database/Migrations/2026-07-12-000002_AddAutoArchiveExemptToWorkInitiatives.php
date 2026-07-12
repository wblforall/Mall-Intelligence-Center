<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom auto_archive_exempt: penanda "jangan auto-arsip" untuk program yang
 * di-unarchive / dipulihkan padahal memenuhi rule auto-arsip (done/cancelled
 * >30 hari). Tanpa ini, Batal Arsip & Pulihkan jadi no-op — item langsung
 * tertangkap lagi oleh rule auto dan terkunci permanen di tab Arsip.
 * Penanda di-reset ke 0 saat ada update progress baru atau diarsipkan manual.
 */
class AddAutoArchiveExemptToWorkInitiatives extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE work_initiatives
             ADD COLUMN auto_archive_exempt TINYINT(1) NOT NULL DEFAULT 0 AFTER archived_by"
        );
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE work_initiatives DROP COLUMN auto_archive_exempt");
    }
}
