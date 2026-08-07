<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penghitung label opsi yang tidak pernah mundur.
 *
 * Sebelumnya label berikutnya dihitung dari label tertinggi yang masih
 * ada. Begitu sebuah opsi dihapus, labelnya bebas dipakai ulang — dan
 * riwayat keputusan yang berbunyi "Disetujui — opsi C" mendadak menunjuk
 * gambar lain yang tak pernah ditinjau siapa pun.
 *
 * Dengan penghitung ini label sekali terbit tidak pernah terbit lagi,
 * sehingga catatan riwayat tetap sahih sepanjang umur item.
 *
 * Nilai awal diisi dari jumlah opsi yang ada sekarang supaya data lama
 * tidak menerbitkan ulang label yang sudah dipakai.
 */
class AddOpsiTerakhirToCreativeItems extends Migration
{
    /** tabel item => tabel file */
    private array $pasangan = [
        'creative_items'       => 'creative_files',
        'event_creative_items' => 'event_creative_files',
    ];

    public function up(): void
    {
        foreach ($this->pasangan as $tabelItem => $tabelFile) {
            $this->db->query("ALTER TABLE {$tabelItem}
                ADD COLUMN opsi_terakhir INT UNSIGNED NOT NULL DEFAULT 0 AFTER versi");

            $this->db->query("UPDATE {$tabelItem} i
                SET i.opsi_terakhir = (
                    SELECT COUNT(*) FROM {$tabelFile} f
                    WHERE f.creative_item_id = i.id AND f.is_opsi = 1
                )");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->pasangan) as $tabelItem) {
            $this->db->query("ALTER TABLE {$tabelItem} DROP COLUMN opsi_terakhir");
        }
    }
}
