<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RAB Event & Promotion — modul Events dilengkapi, RAB jadi dokumen pembungkus
 * (keputusan 14 Okt 2026).
 *
 * - events: jenis (event / rutin = kegiatan rutin satu mal satu bulan) dan pos
 *   budget bawaan untuk item-itemnya.
 * - event_content_items: rincian RAB (qty × freq × harga satuan), kelompok
 *   (Opening Ceremony, Competition, …), pos & mal per item (opsional, ikut event).
 * - budget_dept_setting.event_mulai: sejak bulan apa realisasi Event Content
 *   dihitung ke Budget Departemen dept ini (E&P: 2026-09 — Jan–Agu sudah dari Excel).
 * - rab_dokumen: pengajuan RAB satu periode, berversi; isi dibekukan (snapshot)
 *   saat diajukan; disetujui saat admin E&P mengunggah PDF bertanda tangan e-Sign.
 * - rab_laporan: laporan realisasi akhir bulan, alurnya sama (e-Sign → unggah).
 */
class RabEvent extends Migration
{
    public function up()
    {
        $this->forge->addColumn('events', [
            'jenis'  => ['type' => 'ENUM', 'constraint' => ['event', 'rutin'], 'default' => 'event', 'after' => 'tema'],
            'pos_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'jenis'],
        ]);

        $this->forge->addColumn('event_content_items', [
            'kelompok'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'tipe'],
            'qty'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'budget'],
            'satuan'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'qty'],
            'freq'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'satuan'],
            'satuan_freq'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'freq'],
            'harga_satuan' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'satuan_freq'],
            'pos_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'harga_satuan'],
            'mall'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'pos_id'],
        ]);

        $this->forge->addColumn('budget_dept_setting', [
            'event_mulai' => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'after' => 'tahun_mulai'], // YYYY-MM
        ]);

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'induk_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // id versi pertama
            'versi'           => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
            'nomor'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'periode_mulai'   => ['type' => 'CHAR', 'constraint' => 7],   // YYYY-MM
            'periode_selesai' => ['type' => 'CHAR', 'constraint' => 7],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draf', 'diajukan', 'disetujui', 'digantikan', 'batal'], 'default' => 'draf'],
            'snapshot'        => ['type' => 'LONGTEXT', 'null' => true],
            'catatan'         => ['type' => 'TEXT', 'null' => true],       // alasan revisi
            'file_ttd'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dibuat_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'diajukan_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'diajukan_at'     => ['type' => 'DATETIME', 'null' => true],
            'disetujui_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'disetujui_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dept_id', 'periode_mulai']);
        $this->forge->addKey('induk_id');
        $this->forge->createTable('rab_dokumen');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'bulan'        => ['type' => 'CHAR', 'constraint' => 7],          // YYYY-MM
            'rab_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // RAB berlaku saat dibuat
            'nomor'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['diajukan', 'disahkan', 'batal'], 'default' => 'diajukan'],
            'snapshot'     => ['type' => 'LONGTEXT', 'null' => true],
            'file_ttd'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dibuat_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'disahkan_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'disahkan_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dept_id', 'bulan']);
        $this->forge->createTable('rab_laporan');
    }

    public function down()
    {
        $this->forge->dropTable('rab_laporan', true);
        $this->forge->dropTable('rab_dokumen', true);
        $this->forge->dropColumn('budget_dept_setting', 'event_mulai');
        $this->forge->dropColumn('event_content_items', ['kelompok', 'qty', 'satuan', 'freq', 'satuan_freq', 'harga_satuan', 'pos_id', 'mall']);
        $this->forge->dropColumn('events', ['jenis', 'pos_id']);
    }
}
