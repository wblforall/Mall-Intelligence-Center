<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tanggal mulai penarikan FlowStore (keputusan pemilik 17 Okt 2026): barang diterima SEBELUM tanggal ini sudah
 * diisi dari Excel dan tidak boleh masuk antrean (agar tak terhitung dobel). Bawaan 2026-10-01 (atau .env
 * flowstore.mulai). Tabel ini hanya menyimpan penimpaan yang diatur admin: dept_id 0 = semua dept, selain itu
 * khusus satu dept (menang atas baris 0). Hanya MENAMBAH tabel baru.
 */
class FsMulaiTarik extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'dept_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'mulai'      => ['type' => 'DATE'],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('dept_id', true);
        $this->forge->createTable('budget_fs_setelan', true);
    }

    public function down()
    {
        $this->forge->dropTable('budget_fs_setelan', true);
    }
}
