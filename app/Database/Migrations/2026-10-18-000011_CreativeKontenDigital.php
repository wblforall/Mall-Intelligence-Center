<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 3: KONTEN DIGITAL.
 *
 *  - creative_items / event_creative_items : status_konten ENUM('rencana','produksi','tayang','dilaporkan') NULL.
 *    Kolom `status` lama (alur persetujuan) TIDAK diubah. Item lama = NULL ("belum diatur"); tidak ada data lama yang ditulis.
 *  - creative_paket_awal : format bawaan untuk baris digital yang belum punya format
 *    (Feed Instagram -> "Feed Instagram" 1080x1350, Story/Reels -> "Story / Reels / TikTok" 1080x1920).
 *
 * Titik layar digital memakai tabel `creative_cetak_titik`, kolom `pasang_mulai/pasang_selesai` (= periode tayang di layar)
 * dan `jenis_media/ukuran` (= format konten) yang sudah ada sejak Tahap 2a/2b; tidak ada tabel baru.
 */
class CreativeKontenDigital extends Migration
{
    public function up()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            if (! $this->db->query("SHOW COLUMNS FROM {$t} LIKE 'status_konten'")->getNumRows()) {
                $this->db->query("ALTER TABLE {$t} ADD COLUMN status_konten ENUM('rencana','produksi','tayang','dilaporkan') NULL DEFAULT NULL");
            }
        }
        $this->db->query("UPDATE creative_paket_awal SET jenis_media='Feed Instagram', ukuran='1080x1350 (potret)' WHERE tipe='digital' AND nama_pola='Feed Instagram' AND jenis_media IS NULL AND ukuran IS NULL");
        $this->db->query("UPDATE creative_paket_awal SET jenis_media='Story / Reels / TikTok', ukuran='1080x1920 (vertikal)' WHERE tipe='digital' AND nama_pola='Story/Reels' AND jenis_media IS NULL AND ukuran IS NULL");
    }

    public function down()
    {
        foreach (['creative_items', 'event_creative_items'] as $t) {
            $this->db->query("ALTER TABLE {$t} DROP COLUMN status_konten");
        }
        $this->db->query("UPDATE creative_paket_awal SET jenis_media=NULL, ukuran=NULL WHERE tipe='digital'");
    }
}
