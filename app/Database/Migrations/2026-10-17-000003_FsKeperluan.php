<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Keterangan order ("keperluan": barang dibeli untuk kebutuhan apa) pada penautan FlowStore.
 *
 * Hanya MENAMBAH kolom (migrasi 2026-10-17-000002 tidak diubah):
 * - fs_btb_item.keperluan / spesifikasi / no_mr / keterangan_pr / keterangan_po / keterangan_btb
 *   (semua NULL-able: feed lama tanpa field ini tetap terbaca; perubahan keterangan hanya memperbarui cermin,
 *    tidak pernah menjadikan item 'perlu_ditinjau').
 * - budget_fs_aturan.sasaran: kata kunci dicocokkan ke 'nama' barang (bawaan, perilaku lama) atau ke 'keperluan'.
 *   Aturan sasaran 'keperluan' berlaku berurutan (urutan terkecil yang cocok menang) dan didahulukan atas nama barang.
 * - Seed aturan kata kunci 'keperluan' untuk dept IT (dicocokkan ke pos lewat NAMA pos).
 */
class FsKeperluan extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $db->fieldExists('keperluan', 'fs_btb_item')) {
            $this->forge->addColumn('fs_btb_item', [
                'keperluan'      => ['type' => 'TEXT', 'null' => true, 'after' => 'sumber_nilai'],
                'spesifikasi'    => ['type' => 'TEXT', 'null' => true, 'after' => 'keperluan'],
                'no_mr'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'spesifikasi'],
                'keterangan_pr'  => ['type' => 'TEXT', 'null' => true, 'after' => 'no_mr'],
                'keterangan_po'  => ['type' => 'TEXT', 'null' => true, 'after' => 'keterangan_pr'],
                'keterangan_btb' => ['type' => 'TEXT', 'null' => true, 'after' => 'keterangan_po'],
            ]);
        }
        if (! $db->fieldExists('sasaran', 'budget_fs_aturan')) {
            $this->forge->addColumn('budget_fs_aturan', [
                'sasaran' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'nama', 'after' => 'jenis'],
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $it = $db->table('departments')->select('id')->where('name', 'IT / EDP')->get()->getRowArray();
        if ($it && ! $db->table('budget_fs_aturan')->where('dept_id', (int) $it['id'])->where('sasaran', 'keperluan')->countAllResults()) {
            // Urutan = prioritas (yang pertama cocok menang). Pos '' = "tidak ada pos khusus".
            $aturan = [
                ['PENGGANTIAN MOUSE|PENGGANTIAN ADAPTOR|GANTI PRINTER|UPGRADE PC',                      'Maintenance Komputer Kantor'],   // sebelum CCTV: "mouse NVR CCTV" bukan CCTV
                ['VIDEOTRON',                                                                           ''],                              // tak ada pos videotron
                ['MONITOR CCTV',                                                                        ''],                              // pos "TV monitoring CCTV" nonaktif
                ['PENGGANTIAN KAMERA CCTV|PERBAIKAN CCTV|PERBAIKAN KAMERA|MAINTENANCE CCTV',            'Maintenance CCTV Gedung'],
                ['PENAMBAHAN KAMERA CCTV|PEMASANGAN BARU CCTV|PEMASANGAN CCTV|PENAMBAHAN CCTV|BOX PANEL CCTV|JALUR CORE KAMERA CCTV', 'Penambahan CCTV'],
                ['PENGGANTIAN KOMPUTER',                                                                'Replacement Computer to Mobile devices'],
                ['PEREMAJAAN MONITOR|PENGGANTIAN MONITOR|PEREMAJAAN KOMPUTER',                          'Peremajaan Komputer'],
                ['KWH METER|METERAN LISTRIK',                                                           'RTU Meteran Listrik IoT'],
                ['AKSES PINTU|ACCESSDOOR|ACCESS DOOR',                                                  'Pengadaan Smart DoorLock'],
            ];
            foreach ($aturan as $i => [$kunci, $pos]) {
                $db->table('budget_fs_aturan')->insert([
                    'dept_id' => (int) $it['id'], 'jenis' => 'kata_kunci', 'sasaran' => 'keperluan', 'kunci' => $kunci, 'pos_id' => null,
                    'pos_nama' => $pos, 'urutan' => ($i + 1) * 10, 'dipakai_berapa_kali' => 0, 'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        if ($db->fieldExists('sasaran', 'budget_fs_aturan')) {
            $db->table('budget_fs_aturan')->where('sasaran', 'keperluan')->delete();
            $this->forge->dropColumn('budget_fs_aturan', 'sasaran');
        }
        if ($db->fieldExists('keperluan', 'fs_btb_item')) {
            $this->forge->dropColumn('fs_btb_item', ['keperluan', 'spesifikasi', 'no_mr', 'keterangan_pr', 'keterangan_po', 'keterangan_btb']);
        }
    }
}
