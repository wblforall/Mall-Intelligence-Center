<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creative Tahap 2a: KATALOG TURUNAN dan TURUNAN pada induk Design / Motion Graphic.
 *
 *  - creative_turunan_jenis : katalog jenis turunan (dapat diatur lead/admin). Tidak dihapus bila
 *                             sudah dipakai, hanya dinonaktifkan.
 *  - creative_turunan       : kebutuhan turunan per induk (scope standalone|event + item_id).
 *                             `kunci` mencegah baris ganda (scope|item|jenis|ukuran|durasi).
 *  - creative_turunan_file  : berkas hasil tiap turunan.
 *
 * Seed katalog hanya diisi bila tabel masih kosong. Tidak ada data lama yang diubah.
 */
class CreativeTurunan extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS creative_turunan_jenis (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            induk ENUM('design','motion_graphic') NOT NULL,
            kelompok ENUM('digital','cetak','video') NOT NULL,
            nama VARCHAR(120) NOT NULL,
            ukuran_baku TEXT NULL COMMENT 'JSON: daftar opsi [{label,w,h,satuan}] atau {\"bebas\":true}',
            durasi_opsi TEXT NULL COMMENT 'JSON daftar detik, khusus motion graphic',
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            urutan INT NOT NULL DEFAULT 0,
            dibuat_oleh INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY induk_aktif (induk, aktif, urutan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS creative_turunan (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            scope ENUM('standalone','event') NOT NULL,
            item_id INT UNSIGNED NOT NULL COMMENT 'creative_items.id atau event_creative_items.id',
            jenis_id INT UNSIGNED NULL COMMENT 'NULL = kustom',
            nama VARCHAR(160) NOT NULL,
            kelompok ENUM('digital','cetak','video') NOT NULL,
            ukuran VARCHAR(160) NOT NULL DEFAULT '',
            durasi_detik INT UNSIGNED NULL,
            status ENUM('belum','dikerjakan','review','selesai') NOT NULL DEFAULT 'belum',
            pic VARCHAR(150) NULL,
            tenggat DATE NULL,
            catatan TEXT NULL,
            versi INT UNSIGNED NOT NULL DEFAULT 1,
            approval_status ENUM('belum','diajukan','disetujui','ditolak') NOT NULL DEFAULT 'belum',
            diajukan_oleh INT UNSIGNED NULL,
            diajukan_pada DATETIME NULL,
            keputusan_oleh INT UNSIGNED NULL,
            keputusan_pada DATETIME NULL,
            alasan TEXT NULL,
            sumber ENUM('manual','otomatis') NOT NULL DEFAULT 'manual',
            sumber_ref VARCHAR(80) NULL,
            kunci VARCHAR(255) NOT NULL,
            dibuat_oleh INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY kunci (kunci),
            KEY induk (scope, item_id),
            KEY jenis_id (jenis_id),
            KEY approval (approval_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS creative_turunan_file (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            turunan_id INT UNSIGNED NOT NULL,
            nama_berkas VARCHAR(255) NOT NULL,
            asli VARCHAR(255) NULL,
            tipe VARCHAR(100) NULL,
            ukuran INT UNSIGNED NULL COMMENT 'byte',
            dibuat_oleh INT UNSIGNED NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY turunan_id (turunan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $ada = (int) $this->db->table('creative_turunan_jenis')->countAllResults();
        if ($ada === 0) $this->seed();
    }

    private function seed(): void
    {
        $o = fn(string $label, int $w, int $h, string $sat) => ['label' => $label, 'w' => $w, 'h' => $h, 'satuan' => $sat];
        $bebas = ['bebas' => true];
        $durasi = [6, 15, 30, 60];
        $now = date('Y-m-d H:i:s');

        // [induk, kelompok, nama, ukuran_baku, durasi]
        $rows = [
            // Design - Digital
            ['design', 'digital', 'Feed Instagram', [$o('1080x1350 (potret)', 1080, 1350, 'px'), $o('1080x1080 (persegi)', 1080, 1080, 'px')], null],
            ['design', 'digital', 'Story / Reels / TikTok', [$o('1080x1920 (vertikal)', 1080, 1920, 'px')], null],
            ['design', 'digital', 'Carousel', [$o('1080x1350 per slide', 1080, 1350, 'px')], null],
            ['design', 'digital', 'Poster WhatsApp / broadcast', [$o('1080x1080 (persegi)', 1080, 1080, 'px')], null],
            ['design', 'digital', 'Layar digital / TV mall', [$o('1920x1080 (lanskap)', 1920, 1080, 'px'), $o('1080x1920 (potret)', 1080, 1920, 'px')], null],
            ['design', 'digital', 'LED Videotron', $bebas, null],
            ['design', 'digital', 'Banner website / email', [$o('1920x600', 1920, 600, 'px')], null],
            // Design - Cetak
            ['design', 'cetak', 'Poster', [$o('A3 (297x420 mm)', 297, 420, 'mm'), $o('A2 (420x594 mm)', 420, 594, 'mm')], null],
            ['design', 'cetak', 'Flyer', [$o('A5 (148x210 mm)', 148, 210, 'mm')], null],
            ['design', 'cetak', 'X-Banner', [$o('60x160 cm', 60, 160, 'cm')], null],
            ['design', 'cetak', 'Roll-up Banner', [$o('85x200 cm', 85, 200, 'cm')], null],
            ['design', 'cetak', 'Standing Sign / Easel', [$o('A1 (594x841 mm)', 594, 841, 'mm')], null],
            ['design', 'cetak', 'Tent Card', [$o('A5 lipat (148x210 mm)', 148, 210, 'mm')], null],
            ['design', 'cetak', 'Backdrop', $bebas, null],
            ['design', 'cetak', 'Spanduk / Baliho', $bebas, null],
            ['design', 'cetak', 'Stiker Lantai / Kaca', $bebas, null],
            ['design', 'cetak', 'ID Card / Lanyard', [$o('54x86 mm', 54, 86, 'mm')], null],
            // Motion Graphic - Video
            ['motion_graphic', 'video', 'Video 9:16', [$o('1080x1920 (9:16)', 1080, 1920, 'px')], $durasi],
            ['motion_graphic', 'video', 'Video 4:5', [$o('1080x1350 (4:5)', 1080, 1350, 'px')], $durasi],
            ['motion_graphic', 'video', 'Video 1:1', [$o('1080x1080 (1:1)', 1080, 1080, 'px')], $durasi],
            ['motion_graphic', 'video', 'Video 16:9', [$o('1920x1080 (16:9)', 1920, 1080, 'px')], $durasi],
            ['motion_graphic', 'video', 'Layar LED / TV (loop)', $bebas, $durasi],
        ];

        $urut = 0;
        foreach ($rows as [$induk, $kel, $nama, $ukuran, $dur]) {
            $this->db->table('creative_turunan_jenis')->insert([
                'induk' => $induk, 'kelompok' => $kel, 'nama' => $nama,
                'ukuran_baku' => json_encode($ukuran, JSON_UNESCAPED_UNICODE),
                'durasi_opsi' => $dur ? json_encode($dur) : null,
                'aktif' => 1, 'urutan' => ++$urut, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS creative_turunan_file");
        $this->db->query("DROP TABLE IF EXISTS creative_turunan");
        $this->db->query("DROP TABLE IF EXISTS creative_turunan_jenis");
    }
}
