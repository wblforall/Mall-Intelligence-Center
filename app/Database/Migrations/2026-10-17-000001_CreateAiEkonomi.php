<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Analisa AI Ekonomi harian (tab Market Intelligence di Dashboard).
 *
 * - eco_snapshots: angka indikator terakhir yang BERHASIL didapat (BI Rate,
 *   inflasi, IHSG, PDB, PDRB, BBM, daftar berita). Dipakai sebagai cadangan
 *   bila pengambilan data gagal; umurnya selalu disebut di analisis.
 * - ai_ekonomi: hasil analisis (JSON) + fakta yang dikirim ke AI. Satu baris
 *   terbaru dipakai tampilan; riwayat disimpan.
 */
class CreateAiEkonomi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kunci'        => ['type' => 'VARCHAR', 'constraint' => 40],
            'payload'      => ['type' => 'MEDIUMTEXT'],            // JSON
            'sumber'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'langsung'],
            'diambil_pada' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('kunci');
        $this->forge->createTable('eco_snapshots', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal'      => ['type' => 'DATE'],
            'penyedia'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'model'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'fakta_hash'   => ['type' => 'CHAR', 'constraint' => 64],
            'hasil'        => ['type' => 'MEDIUMTEXT'],            // JSON keluaran AI (tervalidasi)
            'fakta'        => ['type' => 'MEDIUMTEXT'],            // JSON fakta yang dikirim (tanpa data sensitif)
            'token_masuk'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'token_keluar' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'dibuat_oleh'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'dibuat_pada'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tanggal', 'id']);
        $this->forge->createTable('ai_ekonomi', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        $this->forge->dropTable('ai_ekonomi', true);
        $this->forge->dropTable('eco_snapshots', true);
    }
}
