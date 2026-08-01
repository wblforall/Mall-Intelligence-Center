<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Modul Boards/Kanban — Fase 1 (Fondasi): boards, board_members, lists, cards.
 * Spec: KANBAN_DESIGN.md §2 & §10. FK cascade hanya antar tabel kanban_*
 * (board → members/lists/cards, list → cards); referensi ke users/events/
 * departments tanpa FK keras (pola MIC).
 */
class CreateKanbanPhase1 extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE kanban_boards (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                nama VARCHAR(150) NOT NULL,
                deskripsi TEXT NULL,
                color VARCHAR(20) NULL,
                owner_id INT UNSIGNED NOT NULL,
                event_id INT UNSIGNED NULL,
                dept_id INT UNSIGNED NULL,
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_owner (owner_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE kanban_board_members (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                board_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                role ENUM('owner','editor','viewer') NOT NULL DEFAULT 'editor',
                created_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_board_user (board_id, user_id),
                KEY idx_user (user_id),
                CONSTRAINT fk_kbm_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE kanban_lists (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                board_id INT UNSIGNED NOT NULL,
                nama VARCHAR(120) NOT NULL,
                position INT NOT NULL DEFAULT 0,
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_board_pos (board_id, position),
                CONSTRAINT fk_kl_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE kanban_cards (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                board_id INT UNSIGNED NOT NULL,
                list_id INT UNSIGNED NOT NULL,
                judul VARCHAR(255) NOT NULL,
                deskripsi MEDIUMTEXT NULL,
                position INT NOT NULL DEFAULT 0,
                due_date DATETIME NULL,
                due_done TINYINT(1) NOT NULL DEFAULT 0,
                cover_color VARCHAR(20) NULL,
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_list_pos (list_id, position),
                KEY idx_board (board_id),
                KEY idx_due (due_date),
                CONSTRAINT fk_kc_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE,
                CONSTRAINT fk_kc_list FOREIGN KEY (list_id) REFERENCES kanban_lists(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS kanban_cards');
        $this->db->query('DROP TABLE IF EXISTS kanban_lists');
        $this->db->query('DROP TABLE IF EXISTS kanban_board_members');
        $this->db->query('DROP TABLE IF EXISTS kanban_boards');
    }
}
