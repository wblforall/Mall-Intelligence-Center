<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom action activity_logs: ENUM → VARCHAR(30).
 * ENUM diam-diam menyimpan '' untuk action di luar daftar (approve, reject,
 * submit, export, upload, send_email, dst.) — kelas bug berulang. VARCHAR
 * menghilangkan constraint itu; tampilan log sudah punya badge fallback.
 * Sekalian repair data lama modul Legal yang argumennya tertukar
 * (action='' + module berisi nama action).
 */
class ActivityLogActionToVarchar extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE activity_logs MODIFY action VARCHAR(30) NOT NULL DEFAULT 'create'");

        // Repair rows Legal yang tertukar: write('legal', '<action>', ...)
        $map = ['create' => 'create', 'update' => 'update', 'delete' => 'delete',
                'upload_doc' => 'create', 'delete_doc' => 'delete'];
        foreach ($map as $mod => $act) {
            $this->db->query(
                "UPDATE activity_logs SET action = ?, module = 'legal' WHERE action = '' AND module = ?",
                [$act, $mod]
            );
        }
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE activity_logs MODIFY action
            ENUM('login','logout','login_failed','create','update','delete','archive','unarchive','restore')
            NOT NULL DEFAULT 'create'");
    }
}
