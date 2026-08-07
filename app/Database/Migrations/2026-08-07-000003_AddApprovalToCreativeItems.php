<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jejak persetujuan pada item creative.
 *
 * `versi` naik setiap kali materi diajukan ulang setelah diminta revisi
 * atau dibuka kembali setelah disetujui — dipakai untuk mengelompokkan
 * riwayat di creative_reviews dan menandai file milik putaran ke berapa.
 *
 * review_submitted_at menjadi dasar SLA "sudah berapa lama menunggu
 * ditinjau" di Kotak Persetujuan.
 */
class AddApprovalToCreativeItems extends Migration
{
    /** @var string[] */
    private array $tables = ['creative_items', 'event_creative_items'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->db->query("ALTER TABLE {$table}
                ADD COLUMN versi INT UNSIGNED NOT NULL DEFAULT 1 AFTER status,
                ADD COLUMN review_submitted_at DATETIME NULL AFTER versi,
                ADD COLUMN approved_by INT UNSIGNED NULL AFTER review_submitted_at,
                ADD COLUMN approved_at DATETIME NULL AFTER approved_by");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $this->db->query("ALTER TABLE {$table}
                DROP COLUMN versi,
                DROP COLUMN review_submitted_at,
                DROP COLUMN approved_by,
                DROP COLUMN approved_at");
        }
    }
}
