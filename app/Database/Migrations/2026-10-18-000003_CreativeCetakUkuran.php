<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 2a (lanjutan): jenis media + ukuran pada item Media Cetak, dan nama paket awal
 * tanpa nama Tema.
 *
 *  - creative_items / event_creative_items : jenis_media VARCHAR(60) NULL, ukuran VARCHAR(80) NULL
 *    (hanya diisi untuk item cetak; item lama tetap NULL, tidak ada data yang ditulis otomatis).
 *  - creative_paket_awal : jenis_media / ukuran bawaan; pola nama menjadi nama jenis saja
 *    ("Design", "Poster", ...) karena item sudah berada di dalam kartu Tema.
 */
class CreativeCetakUkuran extends Migration
{
    public function up()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            if (! $this->db->query("SHOW COLUMNS FROM {$t} LIKE 'jenis_media'")->getNumRows()) {
                $this->db->query("ALTER TABLE {$t} ADD COLUMN jenis_media VARCHAR(60) NULL, ADD COLUMN ukuran VARCHAR(80) NULL");
            }
        }
        if (! $this->db->query("SHOW COLUMNS FROM creative_paket_awal LIKE 'jenis_media'")->getNumRows()) {
            $this->db->query("ALTER TABLE creative_paket_awal ADD COLUMN jenis_media VARCHAR(60) NULL, ADD COLUMN ukuran VARCHAR(80) NULL");
        }
        // pola nama tanpa nama Tema
        $this->db->query("UPDATE creative_paket_awal SET nama_pola = TRIM(REPLACE(REPLACE(nama_pola, ' – {tema}', ''), '{tema}', '')) WHERE nama_pola LIKE '%{tema}%'");
        // bawaan Media Cetak
        $this->db->query("UPDATE creative_paket_awal SET jenis_media='Poster', ukuran='A3 297×420 mm' WHERE tipe='cetak' AND nama_pola='Poster' AND jenis_media IS NULL");
        $this->db->query("UPDATE creative_paket_awal SET jenis_media='X-Banner', ukuran='60×160 cm' WHERE tipe='cetak' AND nama_pola='X-Banner' AND jenis_media IS NULL");
        $this->db->query("UPDATE creative_paket_awal SET jenis_media='Backdrop' WHERE tipe='cetak' AND nama_pola='Backdrop' AND jenis_media IS NULL");
    }

    public function down()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("ALTER TABLE {$t} DROP COLUMN jenis_media, DROP COLUMN ukuran");
        }
        $this->db->query("ALTER TABLE creative_paket_awal DROP COLUMN jenis_media, DROP COLUMN ukuran");
    }
}
