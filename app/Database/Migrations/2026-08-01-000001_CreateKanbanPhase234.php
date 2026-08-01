<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Boards/Kanban — Fase 2–4 (KANBAN_DESIGN.md §2, §10):
 * labels, card_labels, card_members, checklists, checklist_items,
 * comments, attachments, activity.
 *
 * PLUS tabel `notifications` GENERIK (MIC_MOBILE_DESIGN.md §5) — pengganti
 * kanban_notifications pada desain awal: satu pusat notifikasi in-app untuk
 * semua modul (kanban = produser pertama; approval/reminder modul lain &
 * app mobile menyusul memakai tabel yang sama).
 */
class CreateKanbanPhase234 extends Migration
{
    public function up(): void
    {
        // ── Label (palet milik board) ──
        $this->db->query("
            CREATE TABLE kanban_labels (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                board_id INT UNSIGNED NOT NULL,
                nama VARCHAR(60) NULL,
                color VARCHAR(20) NOT NULL,
                PRIMARY KEY (id),
                KEY idx_board (board_id),
                CONSTRAINT fk_klb_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE kanban_card_labels (
                card_id INT UNSIGNED NOT NULL,
                label_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (card_id, label_id),
                CONSTRAINT fk_kcl_card  FOREIGN KEY (card_id)  REFERENCES kanban_cards(id)  ON DELETE CASCADE,
                CONSTRAINT fk_kcl_label FOREIGN KEY (label_id) REFERENCES kanban_labels(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Multi-assignee ──
        $this->db->query("
            CREATE TABLE kanban_card_members (
                card_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (card_id, user_id),
                KEY idx_user (user_id),
                CONSTRAINT fk_kcm_card FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Checklist ──
        $this->db->query("
            CREATE TABLE kanban_checklists (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                card_id INT UNSIGNED NOT NULL,
                judul VARCHAR(150) NOT NULL DEFAULT 'Checklist',
                position INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_card (card_id),
                CONSTRAINT fk_kch_card FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE kanban_checklist_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                checklist_id INT UNSIGNED NOT NULL,
                teks VARCHAR(255) NOT NULL,
                is_done TINYINT(1) NOT NULL DEFAULT 0,
                position INT NOT NULL DEFAULT 0,
                done_by INT UNSIGNED NULL,
                done_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_checklist (checklist_id),
                CONSTRAINT fk_kci_checklist FOREIGN KEY (checklist_id) REFERENCES kanban_checklists(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Komentar ──
        $this->db->query("
            CREATE TABLE kanban_comments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                card_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_card (card_id),
                CONSTRAINT fk_kco_card FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Lampiran (file di writable/kanban_uploads, non-public) ──
        $this->db->query("
            CREATE TABLE kanban_attachments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                card_id INT UNSIGNED NOT NULL,
                filename VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                mime VARCHAR(100) NOT NULL,
                size INT UNSIGNED NOT NULL DEFAULT 0,
                uploaded_by INT UNSIGNED NOT NULL,
                created_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_card (card_id),
                CONSTRAINT fk_kat_card FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Feed aktivitas per board ──
        $this->db->query("
            CREATE TABLE kanban_activity (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                board_id INT UNSIGNED NOT NULL,
                card_id INT UNSIGNED NULL,
                user_id INT UNSIGNED NOT NULL,
                action VARCHAR(50) NOT NULL,
                detail VARCHAR(255) NULL,
                created_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_board (board_id, id),
                KEY idx_card (card_id),
                CONSTRAINT fk_kac_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Notifikasi GENERIK lintas-modul (in-app center + lonceng) ──
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
        $this->db->query('DROP TABLE IF EXISTS kanban_activity');
        $this->db->query('DROP TABLE IF EXISTS kanban_attachments');
        $this->db->query('DROP TABLE IF EXISTS kanban_comments');
        $this->db->query('DROP TABLE IF EXISTS kanban_checklist_items');
        $this->db->query('DROP TABLE IF EXISTS kanban_checklists');
        $this->db->query('DROP TABLE IF EXISTS kanban_card_members');
        $this->db->query('DROP TABLE IF EXISTS kanban_card_labels');
        $this->db->query('DROP TABLE IF EXISTS kanban_labels');
    }
}
