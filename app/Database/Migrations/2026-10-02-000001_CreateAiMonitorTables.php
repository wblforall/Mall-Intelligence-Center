<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — catatan pemakaian Claude Code di laptop tim.
 *
 * Laptop mengirim baris mentah transkrip Claude Code (JSONL) lewat hook yang
 * dipasang di managed settings; server mengurainya ke tiga tabel di bawah.
 * Satuan simpan = SATU BARIS TRANSKRIP (prompt, balasan, atau pemakaian alat),
 * dikunci dengan uuid baris itu sendiri — pengiriman ulang (jaringan putus,
 * dua sapuan bersamaan) jadi aman karena INSERT IGNORE menelan duplikatnya.
 */
class CreateAiMonitorTables extends Migration
{
    public function up()
    {
        // ── Perangkat ────────────────────────────────────────────────────
        // Satu baris = satu laptop. Token hanya disimpan hash-nya; token asli
        // hanya pernah ada di berkas pemasang yang diunduh, sehingga mengunduh
        // pemasang lagi = memutar token (yang lama otomatis mati).
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'   => ['type' => 'INT', 'unsigned' => true],
            'label'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'token_hash'    => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'aktif'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'host_terakhir' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'akun_terakhir' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'lapor_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('ai_devices');

        // ── Sesi Claude Code ─────────────────────────────────────────────
        // Angka agregat (jumlah prompt, token) disimpan di sini dan dihitung
        // ulang tiap kiriman, agar halaman ringkasan harian tak perlu
        // menjumlah ribuan baris entri.
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'device_id'     => ['type' => 'INT', 'unsigned' => true],
            'employee_id'   => ['type' => 'INT', 'unsigned' => true],
            'session_uuid'  => ['type' => 'VARCHAR', 'constraint' => 64],
            'judul'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cwd'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'proyek'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'git_branch'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'model'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'versi_cc'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'mulai_at'      => ['type' => 'DATETIME', 'null' => true],
            'terakhir_at'   => ['type' => 'DATETIME', 'null' => true],
            'jml_prompt'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'jml_alat'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'token_masuk'   => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'token_keluar'  => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['device_id', 'session_uuid']);
        $this->forge->addKey(['employee_id', 'terakhir_at']);
        $this->forge->createTable('ai_sessions');

        // ── Entri: prompt / balasan / alat ───────────────────────────────
        // `waktu` per entri (bukan per sesi) karena satu sesi bisa melewati
        // tengah malam; rekap harian menyaring entri, bukan sesi.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'ai_session_id' => ['type' => 'INT', 'unsigned' => true],
            'uuid'          => ['type' => 'VARCHAR', 'constraint' => 64],
            'jenis'         => ['type' => 'ENUM', 'constraint' => ['prompt', 'balasan', 'alat']],
            'waktu'         => ['type' => 'DATETIME'],
            'alat'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sasaran'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'isi'           => ['type' => 'MEDIUMTEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['ai_session_id', 'uuid']);
        $this->forge->addKey(['ai_session_id', 'waktu']);
        $this->forge->createTable('ai_entries');

        // ── Pemakaian token per permintaan API ───────────────────────────
        // Tabel terpisah karena Claude Code menulis SATU baris per blok
        // jawaban (thinking, teks, alat) dan mengulang `usage` yang sama di
        // tiap baris. Kunci unik request_id membuang ulangan itu.
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'ai_session_id' => ['type' => 'INT', 'unsigned' => true],
            'request_id'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'waktu'         => ['type' => 'DATETIME'],
            'model'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'token_masuk'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'token_keluar'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['ai_session_id', 'request_id']);
        $this->forge->addKey('waktu');
        $this->forge->createTable('ai_usage');
    }

    public function down()
    {
        $this->forge->dropTable('ai_usage', true);
        $this->forge->dropTable('ai_entries', true);
        $this->forge->dropTable('ai_sessions', true);
        $this->forge->dropTable('ai_devices', true);
    }
}
