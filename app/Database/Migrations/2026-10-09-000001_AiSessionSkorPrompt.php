<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — skor mutu prompt per sesi (dinilai LLM).
 *
 * skor_prompt   0-100 (jumlah 5 dimensi @0-20); NULL = belum dinilai / tidak dinilai.
 * skor_rincian  JSON {tujuan,konteks,kriteria,kekhususan,iterasi}.
 * skor_saran    1-2 kalimat saran perbaikan.
 * skor_metode   'llm' = dinilai AI (satu-satunya yang tampil/dihitung);
 *               'lewati' = sudah diperiksa tapi tak layak dinilai (mis. <2 prompt manusia);
 *               NULL = belum diproses (dicoba lagi oleh cron).
 * skor_at       kapan terakhir diproses; skor_at < terakhir_at = sesi bertambah → dinilai ulang.
 *
 * Aditif & idempoten: kolom yang sudah ada dilewati.
 */
class AiSessionSkorPrompt extends Migration
{
    public function up()
    {
        $db = $this->db;
        $kolom = [
            'skor_prompt'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'skor_rincian' => ['type' => 'TEXT', 'null' => true],
            'skor_saran'   => ['type' => 'TEXT', 'null' => true],
            'skor_metode'  => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'skor_at'      => ['type' => 'DATETIME', 'null' => true],
        ];
        $baru = [];
        foreach ($kolom as $nama => $def) {
            if (! $db->fieldExists($nama, 'ai_sessions')) $baru[$nama] = $def;
        }
        if ($baru) $this->forge->addColumn('ai_sessions', $baru);

        $ada = $db->query("SHOW INDEX FROM ai_sessions WHERE Key_name = 'idx_ai_sessions_skor'")->getResultArray();
        if (! $ada) {
            $db->query('CREATE INDEX idx_ai_sessions_skor ON ai_sessions (employee_id, skor_metode, terakhir_at)');
        }
    }

    public function down()
    {
        $db = $this->db;
        if ($db->query("SHOW INDEX FROM ai_sessions WHERE Key_name = 'idx_ai_sessions_skor'")->getResultArray()) {
            $db->query('DROP INDEX idx_ai_sessions_skor ON ai_sessions');
        }
        foreach (['skor_prompt', 'skor_rincian', 'skor_saran', 'skor_metode', 'skor_at'] as $k) {
            if ($db->fieldExists($k, 'ai_sessions')) $this->forge->dropColumn('ai_sessions', $k);
        }
    }
}
