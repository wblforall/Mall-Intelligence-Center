<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Riwayat keputusan approval materi creative.
 *
 * Sebelumnya keputusan hanya meninggalkan jejak berupa kolom `status`
 * terakhir pada item — siapa yang menyetujui, kapan, dengan alasan apa,
 * dan sudah revisi berapa kali semuanya hilang. Alasan revisi bahkan
 * menumpang kolom `catatan` milik item, sehingga notifikasi revisi kerap
 * mengirim catatan lama atau kosong.
 *
 * Satu tabel dipakai bersama oleh creative standalone dan creative
 * per-event (dibedakan lewat kolom `scope`) karena kedua alurnya identik
 * dan dilayani oleh service yang sama.
 */
class CreateCreativeReviews extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'scope'         => ['type' => 'ENUM', 'constraint' => ['standalone', 'event'], 'default' => 'standalone'],
            'item_id'       => ['type' => 'INT', 'unsigned' => true],
            'event_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'versi'         => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            // ajukan = desainer mengirim untuk ditinjau
            // setujui / revisi = keputusan peninjau
            // buka = materi yang sudah disetujui dibuka kembali untuk diubah
            'aksi'          => ['type' => 'ENUM', 'constraint' => ['ajukan', 'setujui', 'revisi', 'buka']],
            'catatan'       => ['type' => 'TEXT', 'null' => true],
            // Opsi yang dijadikan acuan saat meminta revisi ("lanjutkan opsi B")
            'basis_file_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            // Label opsi yang terpilih saat aksi ini, mis. "A" atau "A, C".
            // Disimpan sebagai teks agar riwayat tetap terbaca walau filenya dihapus.
            'opsi_label'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'jumlah_opsi'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'oleh'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['scope', 'item_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('creative_reviews', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('creative_reviews', true);
    }
}
