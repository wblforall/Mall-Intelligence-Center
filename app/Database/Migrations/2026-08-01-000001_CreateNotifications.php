<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Notifikasi in-app GENERIK lintas modul (lonceng navbar).
 * Satu tabel dipakai semua modul: approval masuk, hasil approval, komentar/
 * balasan Progress Report, pengingat cron (PIP review, progress report,
 * kedaluwarsa dokumen legal). Kirim lewat App\Libraries\Notify::send().
 *
 * Dirancang agar app mobile kelak memakai sumber yang sama (lihat
 * MIC_MOBILE_DESIGN.md §5) — kolom link_type/link_id/url = deep-link.
 */
class CreateNotifications extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE notifications (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                actor_id INT UNSIGNED NULL,
                module VARCHAR(40) NOT NULL,
                type VARCHAR(40) NOT NULL,
                title VARCHAR(150) NOT NULL,
                body VARCHAR(500) NULL,
                link_type VARCHAR(40) NULL,
                link_id INT UNSIGNED NULL,
                url VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_user_read (user_id, is_read),
                KEY idx_link (link_type, link_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS notifications');
    }
}
