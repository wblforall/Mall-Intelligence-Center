<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Antrian perintah ke aplikasi lain (nonaktifkan akun, dst).
 *
 * Sengaja TIDAK memanggil aplikasi tujuan langsung di dalam request —
 * alasannya sama seperti `push_queue`: panggilan luar di tengah request
 * menahan penyimpanan HR sampai jaringan menjawab, GAGAL TOTAL kalau tujuan
 * sedang mati (padahal penandaan resign-nya sendiri sudah benar dan harus
 * tetap tersimpan), dan tidak memberi kesempatan mencoba ulang.
 *
 * Jadi: `employees.status` berubah → tulis baris di sini (cepat, lokal) →
 * cron `mic:sync-dispatch` yang mengirim dan mencatat hasilnya.
 *
 * `id_lokal` DISALIN saat antre, tidak dibaca ulang saat kirim. Kalau
 * tautannya diubah atau dicabut setelah antrian dibuat, perintah ini tetap
 * menunjuk akun yang dimaksud saat kejadiannya — bukan akun lain yang
 * kebetulan tertaut belakangan.
 *
 * `status` mengikuti kosakata `push_queue`:
 *   pending  — menunggu dikirim
 *   sent     — berhasil
 *   failed   — tujuan menjawab galat / tidak terjangkau (bisa dicoba ulang)
 *   skipped  — belum dikonfigurasi (token layanan belum diset); BUKAN galat,
 *              supaya alurnya sudah bisa jalan sebelum tokennya siap
 */
class CreateAppSyncQueue extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'app_id'      => ['type' => 'INT', 'unsigned' => true],
            'employee_id' => ['type' => 'INT', 'unsigned' => true],
            'id_lokal'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'aksi'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'alasan'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed', 'skipped'], 'default' => 'pending'],
            'attempts'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'last_error'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'sent_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('app_sync_queue');
    }

    public function down(): void
    {
        $this->forge->dropTable('app_sync_queue', true);
    }
}
