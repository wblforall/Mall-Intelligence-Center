<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 1: TEMA sebagai induk.
 *
 *  - creative_tema          : Tema Bulanan (pekerjaan mal di luar event).
 *  - creative_tema_status   : penutupan Tema milik EVENT (tabel events tidak diubah).
 *  - creative_items.tema_id / event_id : penautan item ke Tema (keduanya NULL = "Tanpa Tema",
 *                             paling banyak salah satu terisi).
 *  - creative_pemetaan_log  : jejak pemetaan data lama agar bisa dibatalkan.
 *
 * Tidak ada data lama yang diubah.
 */
class CreativeTema extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS creative_tema (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nama VARCHAR(200) NOT NULL,
            bulan CHAR(7) NOT NULL COMMENT 'YYYY-MM',
            deskripsi TEXT NULL,
            ditutup TINYINT(1) NOT NULL DEFAULT 0,
            ditutup_oleh INT UNSIGNED NULL,
            ditutup_pada DATETIME NULL,
            dibuat_oleh INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY bulan (bulan),
            KEY ditutup (ditutup)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS creative_tema_status (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id INT UNSIGNED NOT NULL,
            ditutup TINYINT(1) NOT NULL DEFAULT 0,
            ditutup_oleh INT UNSIGNED NULL,
            ditutup_pada DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_id (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("ALTER TABLE creative_items
            ADD COLUMN tema_id INT UNSIGNED NULL AFTER tipe,
            ADD COLUMN event_id INT UNSIGNED NULL AFTER tema_id,
            ADD KEY tema_id (tema_id),
            ADD KEY event_id (event_id),
            ADD CONSTRAINT chk_creative_items_satu_tema CHECK (tema_id IS NULL OR event_id IS NULL)");

        $this->db->query("CREATE TABLE IF NOT EXISTS creative_pemetaan_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch VARCHAR(32) NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            tema_id_lama INT UNSIGNED NULL,
            event_id_lama INT UNSIGNED NULL,
            tema_id_baru INT UNSIGNED NULL,
            event_id_baru INT UNSIGNED NULL,
            tema_dibuat TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Tema Bulanan baru dibuat oleh batch ini',
            oleh INT UNSIGNED NULL,
            dibatalkan_pada DATETIME NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY batch (batch),
            KEY item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE creative_items DROP CHECK chk_creative_items_satu_tema");
        $this->db->query("ALTER TABLE creative_items DROP KEY tema_id, DROP KEY event_id, DROP COLUMN tema_id, DROP COLUMN event_id");
        $this->db->query("DROP TABLE IF EXISTS creative_pemetaan_log");
        $this->db->query("DROP TABLE IF EXISTS creative_tema_status");
        $this->db->query("DROP TABLE IF EXISTS creative_tema");
    }
}
