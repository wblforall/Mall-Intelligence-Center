<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 2a: PAKET ITEM AWAL.
 *
 *  - creative_paket_awal : baris pola item yang dibuat otomatis saat Tema Bulanan / Event dibuat.
 *  - creative_items.dari_paket, event_creative_items.dari_paket : 1 = item "Rencana" dari paket
 *    yang belum dikerjakan (hilang saat item diubah / diisi).
 *
 * Seed hanya bila tabel kosong. Data item lama tidak berubah (dari_paket default 0).
 */
class CreativePaketAwal extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS creative_paket_awal (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            konteks ENUM('bulanan','event') NOT NULL,
            tipe VARCHAR(30) NOT NULL,
            nama_pola VARCHAR(200) NOT NULL COMMENT 'mis. Design',
            platform VARCHAR(20) NULL,
            urutan INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY konteks_aktif (konteks, aktif, urutan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        foreach (['creative_items', 'event_creative_items'] as $t) {
            $has = $this->db->query("SHOW COLUMNS FROM {$t} LIKE 'dari_paket'")->getNumRows();
            if (! $has) $this->db->query("ALTER TABLE {$t} ADD COLUMN dari_paket TINYINT(1) NOT NULL DEFAULT 0");
        }

        if ((int) $this->db->table('creative_paket_awal')->countAllResults() === 0) {
            $now  = date('Y-m-d H:i:s');
            $base = [
                ['master_design',  'Design',            null],
                ['motion_graphic', 'Motion Graphic',    null],
                ['digital',        'Feed Instagram',    'ig'],
                ['digital',        'Story/Reels',       'ig'],
                ['cetak',          'Poster',            null],
                ['cetak',          'X-Banner',          null],
            ];
            $event = array_merge($base, [['cetak', 'Backdrop', null]]);
            foreach (['bulanan' => $base, 'event' => $event] as $konteks => $list) {
                foreach ($list as $i => [$tipe, $pola, $platform]) {
                    $this->db->table('creative_paket_awal')->insert([
                        'konteks' => $konteks, 'tipe' => $tipe, 'nama_pola' => $pola, 'platform' => $platform,
                        'urutan' => $i + 1, 'aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("ALTER TABLE {$t} DROP COLUMN dari_paket");
        }
        $this->db->query("DROP TABLE IF EXISTS creative_paket_awal");
    }
}
