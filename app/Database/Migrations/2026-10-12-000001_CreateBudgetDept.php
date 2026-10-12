<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Budget Departemen — catatan internal budget & realisasi tiap departemen.
 *
 * Bermula dari Excel "BUDGET LPSS" (Loyalty Program & Sponsorship) yang
 * totalnya meleset karena rumus menjumlah kolom yang salah; di sini rekap
 * selalu dihitung dari baris transaksi, bukan disimpan.
 *
 * - budget_pos          : daftar pos milik satu departemen (bisa dinonaktifkan,
 *                         tak bisa dihapus selama masih punya data).
 * - budget_rencana      : rincian budget per pos × tahun × bulan × mal; satu sel
 *                         boleh berisi beberapa baris berketerangan (mis. Januari:
 *                         Tahun Baru 5 jt + Isra Mi'raj 5 jt) — nilai sel = jumlahnya.
 *                         mall '' = tanpa pembagian mal.
 * - budget_realisasi    : satu baris per pengeluaran. mall 'both' dibagi 50:50
 *                         saat rekap; tanggal = bulan pembebanan, periode_asal
 *                         untuk tagihan bulan lalu (mis. reimburse poin Des
 *                         dibayar Jan).
 * - budget_dept_setting : apakah departemen memisah budget per mal.
 */
class CreateBudgetDept extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'dept_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pisah_mal'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('dept_id', true);
        $this->forge->createTable('budget_dept_setting');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'urutan'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dept_id', 'urutan']);
        $this->forge->createTable('budget_pos');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pos_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tahun'      => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true],
            'bulan'      => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true],
            'mall'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'urutan'     => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'jumlah'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['pos_id', 'tahun', 'bulan', 'mall']);
        $this->forge->addKey('tahun');
        $this->forge->createTable('budget_rencana');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pos_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tanggal'      => ['type' => 'DATE'],
            'periode_asal' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],  // YYYY-MM
            'uraian'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'keterangan'   => ['type' => 'TEXT', 'null' => true],
            'nilai'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'mall'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'referensi'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // no. memo / PR
            'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dept_id', 'tanggal']);
        $this->forge->addKey('pos_id');
        $this->forge->createTable('budget_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('budget_realisasi', true);
        $this->forge->dropTable('budget_rencana', true);
        $this->forge->dropTable('budget_pos', true);
        $this->forge->dropTable('budget_dept_setting', true);
    }
}
