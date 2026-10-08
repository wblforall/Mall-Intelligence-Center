<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — catat MODEL pemberi skor mutu prompt per sesi.
 *
 * skor_model: `host-singkat/model` (mis. `groq/qwen3.8-27b`) dari provider yang
 * berhasil menjawab; NULL bila belum dinilai/dilewati. Tidak menyimpan kunci
 * atau URL penuh. Dipakai untuk memeriksa konsistensi penilaian antar-model
 * (failover bisa membuat skor satu karyawan berasal dari model berbeda).
 * Idempoten: kolom yang sudah ada dilewati.
 */
class AiSessionSkorModel extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('skor_model', 'ai_sessions')) {
            $this->forge->addColumn('ai_sessions', [
                'skor_model' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'skor_metode'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('skor_model', 'ai_sessions')) {
            $this->forge->dropColumn('ai_sessions', 'skor_model');
        }
    }
}
