<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 2b: MEDIA CETAK berdiri sendiri + TITIK Media Promo + turunan otomatis.
 *
 *  - creative_items / event_creative_items : pasang_mulai, pasang_selesai (periode pasang, DATE NULL),
 *                                            design_ref ('standalone:ID' | 'event:ID', Design induk pilihan, NULL = Design pertama).
 *  - creative_cetak_titik : tautan item cetak -> pemesanan Media Promo (promo_media_usage) per titik,
 *                           ditambah bukti pasang (foto) per titik.
 *  - promo_media_usage.creative_ref : 'standalone:ID' | 'event:ID' agar baris dari Creative dikenali (NULL = bukan dari Creative).
 *  - creative_turunan.keterangan / penanda : keterangan "dipakai di N titik, M item cetak" dan penanda
 *                           "tidak lagi dipakai media cetak" untuk turunan otomatis (sumber = otomatis).
 *  - creative_turunan_jenis : jenis Media Cetak yang punya titik di master Media Promo (T-Banner, Hanging Banner,
 *                           Sticker Lift, Totem) + Neon Box dan Materi Cetak Lainnya; ditambahkan hanya bila namanya belum ada.
 *
 * Semua perubahan aditif / NULL-able; tidak ada data lama yang diubah.
 */
class CreativeCetakTitik extends Migration
{
    public function up()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            if (! $this->db->query("SHOW COLUMNS FROM {$t} LIKE 'pasang_mulai'")->getNumRows()) {
                $this->db->query("ALTER TABLE {$t} ADD COLUMN pasang_mulai DATE NULL, ADD COLUMN pasang_selesai DATE NULL");
            }
            if (! $this->db->query("SHOW COLUMNS FROM {$t} LIKE 'design_ref'")->getNumRows()) {
                $this->db->query("ALTER TABLE {$t} ADD COLUMN design_ref VARCHAR(40) NULL");
            }
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS creative_cetak_titik (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            scope ENUM('standalone','event') NOT NULL,
            item_id INT UNSIGNED NOT NULL COMMENT 'creative_items.id atau event_creative_items.id',
            usage_id INT NOT NULL COMMENT 'promo_media_usage.id (pemesanan terhubung)',
            spot_id INT NOT NULL COMMENT 'promo_media_spots.id',
            slot_number TINYINT NULL,
            tanggal_pasang DATE NULL,
            bukti_pasang_file VARCHAR(255) NULL,
            bukti_pasang_asli VARCHAR(255) NULL,
            catatan TEXT NULL,
            dibuat_oleh INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY item_spot (scope, item_id, spot_id),
            KEY usage_id (usage_id),
            KEY spot_id (spot_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if (! $this->db->query("SHOW COLUMNS FROM promo_media_usage LIKE 'creative_ref'")->getNumRows()) {
            $this->db->query("ALTER TABLE promo_media_usage ADD COLUMN creative_ref VARCHAR(40) NULL, ADD KEY idx_creative_ref (creative_ref)");
        }

        foreach (['keterangan' => 'VARCHAR(255) NULL', 'penanda' => 'VARCHAR(160) NULL'] as $col => $def) {
            if (! $this->db->query("SHOW COLUMNS FROM creative_turunan LIKE '{$col}'")->getNumRows()) {
                $this->db->query("ALTER TABLE creative_turunan ADD COLUMN {$col} {$def}");
            }
        }

        // Jenis Media Cetak untuk katalog (ukuran bebas). Hanya bila nama belum ada pada induk design.
        $now  = date('Y-m-d H:i:s');
        $urut = (int) ($this->db->query("SELECT MAX(urutan) m FROM creative_turunan_jenis WHERE induk='design'")->getRow()->m ?? 0);
        foreach (['T-Banner', 'Hanging Banner', 'Sticker Lift', 'Totem', 'Neon Box', 'Materi Cetak Lainnya'] as $nama) {
            $ada = $this->db->table('creative_turunan_jenis')->where('induk', 'design')->where('nama', $nama)->countAllResults();
            if ($ada) continue;
            $this->db->table('creative_turunan_jenis')->insert([
                'induk' => 'design', 'kelompok' => 'cetak', 'nama' => $nama,
                'ukuran_baku' => json_encode(['bebas' => true]), 'durasi_opsi' => null,
                'aktif' => 1, 'urutan' => ++$urut, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS creative_cetak_titik");
        $this->db->query("ALTER TABLE promo_media_usage DROP INDEX idx_creative_ref, DROP COLUMN creative_ref");
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("ALTER TABLE {$t} DROP COLUMN pasang_mulai, DROP COLUMN pasang_selesai, DROP COLUMN design_ref");
        }
        $this->db->query("ALTER TABLE creative_turunan DROP COLUMN keterangan, DROP COLUMN penanda");
    }
}
