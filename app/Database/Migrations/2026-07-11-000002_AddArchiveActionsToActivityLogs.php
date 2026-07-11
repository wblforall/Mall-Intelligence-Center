<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nilai enum action baru untuk Progress Report: archive, unarchive, restore.
 * Tanpa ini MariaDB (non-strict) menyimpan '' saat ActivityLog::write dipanggil
 * dengan action di luar enum.
 */
class AddArchiveActionsToActivityLogs extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE activity_logs MODIFY action
            ENUM('login','logout','login_failed','create','update','delete','archive','unarchive','restore')
            NOT NULL DEFAULT 'create'");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE activity_logs MODIFY action
            ENUM('login','logout','login_failed','create','update','delete')
            NOT NULL DEFAULT 'create'");
    }
}
