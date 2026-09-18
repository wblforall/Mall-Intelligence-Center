<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Modul Pest Control — lima tabel, semuanya berdiri sendiri (tidak terikat
 * event_id), sejajar daily_traffic / daily_vehicles.
 *
 * Satuan simpan = SATU KUNJUNGAN pada SATU TANGGAL. Nomor minggu tidak pernah
 * disimpan; ia dihitung saat menampilkan lewat YEARWEEK(tanggal, 1). Lihat
 * PESTCARE_DESIGN.md §2 — inilah yang membuat pertanyaan "bulan ini 4 atau 5
 * minggu" tidak perlu dijawab di tingkat skema.
 */
class CreatePestTables extends Migration
{
    public function up()
    {
        // ── Master item temuan ────────────────────────────────────────────
        // Tabel master, BUKAN ENUM: daftar item terbukti berubah antar tahun
        // (2026 menambah Kelelawar & Kupu-kupu ke 6 item 2025).
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'urutan'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['aktif', 'urutan']);
        $this->forge->createTable('pest_items');

        // ── Master area/lokasi, per mall ──────────────────────────────────
        // Dibuat sekarang walau antarmukanya baru dibangun di Fase 2, supaya
        // pest_findings.area_id punya rujukan sah dan Fase 2 tidak menuntut
        // migrasi ulang.
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'mall'       => ['type' => 'ENUM', 'constraint' => ['ewalk', 'pentacity']],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'urutan'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['mall', 'aktif', 'urutan']);
        $this->forge->createTable('pest_areas');

        // ── Kunjungan ─────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'mall'       => ['type' => 'ENUM', 'constraint' => ['ewalk', 'pentacity']],
            'tanggal'    => ['type' => 'DATE'],
            'vendor'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'petugas'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // 'rekap_legacy' = hasil impor Excel bulanan 2025–2026. Baris ini
            // TIDAK punya resolusi mingguan, jadi wajib dikecualikan dari
            // tampilan tren mingguan.
            'sumber'     => ['type' => 'ENUM', 'constraint' => ['kunjungan', 'rekap_legacy'], 'default' => 'kunjungan'],
            'catatan'    => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['mall', 'tanggal']);
        $this->forge->addKey('tanggal');
        $this->forge->createTable('pest_visits');

        // ── Temuan ────────────────────────────────────────────────────────
        // area_id SENGAJA NOT NULL DEFAULT 0 (0 = tidak dirinci), bukan
        // nullable: MySQL mengizinkan banyak NULL dalam satu unique key, jadi
        // kalau nullable, UNIQUE di bawah tidak menggigit untuk temuan yang
        // tak dirinci lokasinya — dan baris ganda masuk diam-diam.
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'visit_id'   => ['type' => 'INT', 'unsigned' => true],
            'item_id'    => ['type' => 'INT', 'unsigned' => true],
            'area_id'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'jumlah'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['visit_id', 'item_id', 'area_id']);
        $this->forge->addKey('item_id');
        $this->forge->addForeignKey('visit_id', 'pest_visits', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'pest_items', 'id', '', 'RESTRICT');
        $this->forge->createTable('pest_findings');

        // ── Foto bukti ────────────────────────────────────────────────────
        // Tabel anak terpisah supaya satu temuan boleh punya banyak foto.
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'finding_id'    => ['type' => 'INT', 'unsigned' => true],
            'file_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('finding_id');
        $this->forge->addForeignKey('finding_id', 'pest_findings', 'id', '', 'CASCADE');
        $this->forge->createTable('pest_finding_photos');

        // ── Seed item ─────────────────────────────────────────────────────
        // Ejaan diperbaiki dari berkas sumber: Excel 2026 menulis "Kekelawar"
        // dan "Kupu - Kupu".
        $now  = date('Y-m-d H:i:s');
        $seed = ['Tikus', 'Kucing', 'Biawak', 'Kecoa', 'Lalat', 'Ular', 'Kelelawar', 'Kupu-kupu'];
        $rows = [];
        foreach ($seed as $i => $nama) {
            $rows[] = ['nama' => $nama, 'urutan' => $i + 1, 'aktif' => 1, 'created_at' => $now];
        }
        $this->db->table('pest_items')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('pest_finding_photos', true);
        $this->forge->dropTable('pest_findings', true);
        $this->forge->dropTable('pest_visits', true);
        $this->forge->dropTable('pest_areas', true);
        $this->forge->dropTable('pest_items', true);
    }
}
