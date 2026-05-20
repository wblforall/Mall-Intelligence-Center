<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBatchIdToHadiahItems extends Migration
{
    public function up(): void
    {
        // Add batch_id after barang_id in hadiah item tables
        foreach (['loyalty_hadiah_items', 'event_loyalty_hadiah_items'] as $table) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `batch_id` INT NULL AFTER `barang_id`");
        }

        // Add kode_id after nama_penerima in hadiah realisasi tables
        foreach (['loyalty_hadiah_realisasi', 'event_loyalty_hadiah_realisasi'] as $table) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `kode_id` INT NULL AFTER `nama_penerima`");
        }
    }

    public function down(): void
    {
        foreach (['loyalty_hadiah_items', 'event_loyalty_hadiah_items'] as $table) {
            $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `batch_id`");
        }
        foreach (['loyalty_hadiah_realisasi', 'event_loyalty_hadiah_realisasi'] as $table) {
            $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `kode_id`");
        }
    }
}
