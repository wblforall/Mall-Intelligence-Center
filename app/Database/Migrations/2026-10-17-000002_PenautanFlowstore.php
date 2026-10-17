<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penautan FlowStore → Budget Departemen (keputusan pemilik 17 Okt 2026).
 *
 * Pembelian dianggap realisasi SAAT BARANG DITERIMA (BTB final). Pemetaan ke pos &
 * bulan TIDAK otomatis: user memetakan per item di tab "FlowStore"; sistem hanya
 * memberi rekomendasi. Tak ada angka masuk budget_realisasi tanpa user menekan "Catat".
 *
 * Hanya MENAMBAH tabel (tidak menyentuh tabel lama):
 * - fs_divisi            : cermin daftar divisi FlowStore (/api/feed/divisi), diisi awal dari daftar yang sudah dikenal.
 * - fs_btb_item          : cermin baris BTB dari feed baca-saja FlowStore (unik per id_btb_item).
 * - budget_fs_divisi_map : divisi FlowStore ↔ dept MIC (satu divisi hanya milik satu dept).
 * - budget_fs_pemetaan   : status & pilihan user per item (UNIK per id_btb_item → satu item dicatat sekali).
 * - budget_fs_aturan     : memori rekomendasi (jenis 'memori') + kata kunci (jenis 'kata_kunci').
 * - budget_fs_sinkron    : catatan tiap sinkron (untuk "terakhir sinkron" dan galat ramah).
 */
class PenautanFlowstore extends Migration
{
    public function up()
    {
        $db = $this->db;

        $this->forge->addField([
            'id_divisi'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'divisi'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'diperbarui_pada' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_divisi', true);
        $this->forge->createTable('fs_divisi', true);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_btb_item'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'id_btb'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'no_btb'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'tanggal_btb'      => ['type' => 'DATE', 'null' => true],
            'tanggal_diterima' => ['type' => 'DATE', 'null' => true],
            'status_btb'       => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'draft'],
            'id_po'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'no_po'            => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'tanggal_po'       => ['type' => 'DATE', 'null' => true],
            'id_supplier'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'nama_supplier'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_skema'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'skema'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'id_pr'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'no_pr'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'id_divisi'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'divisi'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kode_divisi'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'kode_barang'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'nama_barang'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'kategori'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'satuan'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'jumlah_diterima'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'harga_satuan_po'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'nilai_diterima'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'nilai_sebelum_ppn' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'ppn_rupiah'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'sumber_nilai'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'dibuat_pada'      => ['type' => 'DATETIME', 'null' => true],
            'diambil_pada'     => ['type' => 'DATETIME', 'null' => true],
            'diperbarui_pada'  => ['type' => 'DATETIME', 'null' => true],
            'batal_sejak'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('id_btb_item');
        $this->forge->addKey(['id_divisi', 'tanggal_diterima']);
        $this->forge->addKey('status_btb');
        $this->forge->createTable('fs_btb_item', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'id_divisi_fs' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['dept_id', 'id_divisi_fs']);
        $this->forge->createTable('budget_fs_divisi_map', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_btb_item'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'dept_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['baru', 'dicatat', 'diabaikan', 'perlu_ditinjau'], 'default' => 'baru'],
            'pos_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'bulan_beban'   => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],   // YYYY-MM
            'mall'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'realisasi_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'alasan_abaikan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'catatan_tinjau' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'rekomendasi_pos_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rekomendasi_sumber' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'rekomendasi_skor'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'rekomendasi_alasan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dibuat_oleh'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'diperbarui_oleh' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('id_btb_item');
        $this->forge->addKey(['dept_id', 'status']);
        $this->forge->addKey('realisasi_id');
        $this->forge->createTable('budget_fs_pemetaan', true);

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'dept_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'jenis'           => ['type' => 'ENUM', 'constraint' => ['memori', 'kata_kunci'], 'default' => 'memori'],
            'kunci'           => ['type' => 'VARCHAR', 'constraint' => 191],   // memori: "K:<kode>" / "N:<nama ternormalisasi>"; kata_kunci: kata dipisah "|" atau /regex/
            'pos_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'pos_nama'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],  // kata_kunci: dicocokkan lewat nama pos
            'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'dipakai_berapa_kali' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'terakhir_dipakai' => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['dept_id', 'jenis', 'kunci', 'pos_id']);
        $this->forge->createTable('budget_fs_aturan', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'mulai'      => ['type' => 'DATETIME'],
            'selesai'    => ['type' => 'DATETIME', 'null' => true],
            'dari'       => ['type' => 'DATE', 'null' => true],
            'sampai'     => ['type' => 'DATE', 'null' => true],
            'dept_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sumber'     => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],   // http | fixture
            'ok'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'jumlah'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'baru'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'diperbarui' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'batal'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'galat'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'oleh'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dept_id', 'ok']);
        $this->forge->createTable('budget_fs_sinkron', true);

        // ── Isi awal ────────────────────────────────────────────────
        $now = date('Y-m-d H:i:s');

        // Daftar divisi FlowStore yang sudah dikenal (disegarkan dari /api/feed/divisi bila feed hidup).
        $divisi = [18 => ['MARKETING', 'MKT'], 21 => ['CIVIL', 'CVL'], 22 => ['EVENT & PROMOSI', 'PRM'], 26 => ['PURCHASING & STORE', 'STR'],
            27 => ['TENANT RELATION', 'TR'], 33 => ['ENGINEERING', 'ENG'], 34 => ['K3', 'K3'], 35 => ['DGM', 'DGM'], 36 => ['OPERASIONAL', 'OPS'],
            37 => ['HR-GA LEGAL DEPARTMENT', 'HRGALGL'], 38 => ['CUSTOMER CARE', 'CC'], 39 => ['FINANCE & ACCOUNTING', 'FA'],
            40 => ['INFORMASI TEKNOLOGI', 'IT'], 43 => ['VISUAL MERCHANDISE', 'VM'], 44 => ['FOODCOURT', 'FC'],
            46 => ['LOYALTY PROGRAM & SPONSORSHIP', 'LPS'], 50 => ['CASUAL LEASING', 'CL'], 52 => ['TENANT DESIGN COORDINATOR', 'TDC'],
            53 => ['VISUAL CREATIVE DESIGN', 'VCD']];
        foreach ($divisi as $id => [$nama, $kode]) {
            if (! $db->table('fs_divisi')->where('id_divisi', $id)->countAllResults()) {
                $db->table('fs_divisi')->insert(['id_divisi' => $id, 'divisi' => $nama, 'kode' => $kode, 'diperbarui_pada' => $now]);
            }
        }

        // Pemetaan divisi ↔ dept lewat kecocokan nama — HANYA yang yakin; sisanya diatur admin di tab FlowStore.
        $yakin = ['IT / EDP' => 40, 'Event & Promotion' => 22, 'Loyalty Program & Sponsorship' => 46, 'Creative Concept & Design' => 53,
            'Casual Leasing' => 50, 'Tenant Relation' => 27, 'Customer Care' => 38, 'Visual Merchandiser' => 43,
            'Engineering' => 33, 'HR-GA & Legal' => 37, 'Finance & Accounting' => 39, 'Store & Purchasing' => 26];
        foreach ($yakin as $nama => $idDivisi) {
            $d = $db->table('departments')->select('id')->where('name', $nama)->get()->getRowArray();
            if ($d && ! $db->table('budget_fs_divisi_map')->where('id_divisi_fs', $idDivisi)->countAllResults()) {
                $db->table('budget_fs_divisi_map')->insert(['dept_id' => (int) $d['id'], 'id_divisi_fs' => $idDivisi, 'created_at' => $now]);
            }
        }

        // Kata kunci bawaan dept IT (dicocokkan ke pos lewat NAMA pos, jadi aman antar-lingkungan).
        $it = $db->table('departments')->select('id')->where('name', 'IT / EDP')->get()->getRowArray();
        if ($it) {
            $aturan = [
                ['MIKROTIK|SWITCH|UTP|RJ45|ROUTER|ACCESS POINT|SFP|FIBER|FIBER OPTIK',   ''],   // perangkat jaringan: sengaja TANPA pos (tak ada pos jaringan perangkat keras; "Vendor Jaringan CCTV" = jasa vendor)
                ['CCTV|CAMERA|KAMERA|NVR|DVR|IPCAM|IP CAM',                                   'Penambahan CCTV'],
                ['DOORLOCK|DOOR LOCK|SMART LOCK|CYLINDER LOCK|KUNCI|LOCK',                    'Pengadaan Smart DoorLock'],
                ['STEKER|SMART PLUG|STOPKONTAK|STOP KONTAK',                                  'Pengadaan Smart Steker'],
                ['RTU|LORA|LORAWAN|IFEMTO|GATEWAY|ENERGY METER|KWH',                          'RTU Meteran Listrik IoT'],
                ['TEMPERATUR|TEMPERATURE|HUMIDITY|SENSOR SUHU',                               'Penambahan Temperatur dan Humidity Sensor'],
                ['SENSOR PINTU|MAGNETIC DOOR|DOOR SENSOR',                                    'Sensor Pintu Tangga Darurat IoT Integrated'],
                ['VPS|VIRTUAL PRIVATE',                                                       'Subs Virtual Private Server (VPS)'],
                ['HOSTING|DOMAIN|SSL',                                                        'Hosting dan Domain'],
                ['TRAINING|SERTIFIKASI|PELATIHAN',                                            'Training / Sertifikasi'],
                ['LAPTOP|NOTEBOOK|MACBOOK|PC|MINI PC|KOMPUTER|MONITOR|IPAD|TABLET|ALL IN ONE', 'Peremajaan Komputer'],
                ['SSD|RAM|HDD|HARDDISK|FLASHDISK|KABEL|CABLE|BATERAI|BATTERY|MOUSE|KEYBOARD|PRINTER|TINTA|TONER|ADAPTOR|ADAPTER|CHARGER|HDMI|SPAREPART|SPARE PART', 'Maintenance Komputer Kantor'],
            ];
            foreach ($aturan as $i => [$kunci, $pos]) {
                $db->table('budget_fs_aturan')->insert([
                    'dept_id' => (int) $it['id'], 'jenis' => 'kata_kunci', 'kunci' => $kunci, 'pos_id' => null,
                    'pos_nama' => $pos, 'urutan' => ($i + 1) * 10, 'dipakai_berapa_kali' => 0, 'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        foreach (['budget_fs_sinkron', 'budget_fs_aturan', 'budget_fs_pemetaan', 'budget_fs_divisi_map', 'fs_btb_item', 'fs_divisi'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
