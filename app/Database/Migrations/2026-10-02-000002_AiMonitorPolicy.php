<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — enrollment otomatis + blokir akses + audit.
 *
 * Menambah kolom pada ai_devices untuk dua hal baru:
 *  - ENROLLMENT: laptop memasang diri sendiri lewat kunci bersama. machine_id
 *    menjaga idempotensi (pemasangan ulang memutar token, bukan menggandakan
 *    baris). enrolled_at mencatat kapan laptop pertama/terakhir mendaftar.
 *  - BLOKIR: diblokir + alasan/pelaku/waktu. Agen laptop membaca status ini
 *    dari respons ingest dan menghentikan diri sementara — stop yang bisa
 *    dicabut, bukan penghapusan.
 *
 * employee_id dibuat NULLABLE: perangkat yang baru enroll belum ditautkan ke
 * karyawan (ditautkan belakangan oleh IT). Ikut pula ai_sessions.employee_id,
 * karena sesi bisa datang dari perangkat yang belum ditautkan.
 */
class AiMonitorPolicy extends Migration
{
    public function up()
    {
        // ── Kolom baru di ai_devices ─────────────────────────────────────
        $this->forge->addColumn('ai_devices', [
            'machine_id'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'label'],
            'enrolled_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'lapor_at'],
            'diblokir'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'aktif'],
            'alasan_blokir' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'diblokir'],
            'blokir_oleh'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'alasan_blokir'],
            'blokir_at'     => ['type' => 'DATETIME', 'null' => true, 'after' => 'blokir_oleh'],
        ]);

        // Index unik pada machine_id. MySQL mengizinkan BANYAK baris NULL pada
        // kolom unik, jadi perangkat lama (token manual, machine_id NULL) tetap
        // hidup berdampingan; yang dijaga unik hanyalah machine_id yang terisi.
        $this->db->query('ALTER TABLE ai_devices ADD UNIQUE KEY ai_devices_machine_id (machine_id)');

        // ── employee_id → NULLABLE ───────────────────────────────────────
        $this->forge->modifyColumn('ai_devices', [
            'employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->modifyColumn('ai_sessions', [
            'employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        // Lepas index unik lalu kolom-kolomnya.
        $this->db->query('ALTER TABLE ai_devices DROP INDEX ai_devices_machine_id');
        $this->forge->dropColumn('ai_devices', [
            'machine_id', 'enrolled_at', 'diblokir', 'alasan_blokir', 'blokir_oleh', 'blokir_at',
        ]);

        // Balikkan employee_id ke NOT NULL (sederhana — asumsikan tak ada NULL).
        $this->forge->modifyColumn('ai_devices', [
            'employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
        $this->forge->modifyColumn('ai_sessions', [
            'employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
    }
}
