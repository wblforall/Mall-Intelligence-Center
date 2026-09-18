<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * UNIQUE(mall, tanggal) → UNIQUE(mall, tanggal, sumber).
 *
 * Ditemukan saat pengujian: baris hasil impor ('rekap_legacy') mewakili SATU
 * BULAN dan diberi tanggal hari terakhir bulan itu, sedangkan baris
 * 'kunjungan' mewakili SATU HARI. Keduanya jenis catatan yang berbeda, tapi
 * kunci lama memaksa mereka berebut satu tanggal — akibatnya kunjungan
 * sungguhan pada tanggal itu tidak bisa dicatat sama sekali, dan pengguna
 * hanya melihat pesan "tidak dapat disunting" tanpa jalan keluar.
 *
 * Aturan "satu kunjungan per mall per hari" TETAP berlaku: ia sekarang
 * ditegakkan dalam lingkup sumber='kunjungan' saja, yang memang lingkup yang
 * dimaksud sejak awal.
 */
class PestVisitUniquePerSumber extends Migration
{
    public function up()
    {
        // Nama kunci dihasilkan otomatis oleh Forge saat tabel dibuat.
        $this->db->query('ALTER TABLE `pest_visits` DROP INDEX `mall_tanggal`');
        $this->db->query('ALTER TABLE `pest_visits` ADD UNIQUE KEY `mall_tanggal_sumber` (`mall`, `tanggal`, `sumber`)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `pest_visits` DROP INDEX `mall_tanggal_sumber`');
        $this->db->query('ALTER TABLE `pest_visits` ADD UNIQUE KEY `mall_tanggal` (`mall`, `tanggal`)');
    }
}
