<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — tambah kolom `ringkasan` pada ai_sessions.
 *
 * RINGKASAN adalah 1–2 kalimat Bahasa Indonesia yang dibuat AI dari SELURUH
 * prompt sesi, menjelaskan APA yang sebenarnya dikerjakan/dihasilkan di sesi
 * itu — lebih kaya dari klasifikasi_tema, dan tidak sekadar menyalin judul
 * bawaan Claude Code (yang kerap hanya diambil dari prompt pertama).
 */
class AiSessionRingkasan extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ai_sessions', [
            'ringkasan' => ['type' => 'TEXT', 'null' => true, 'after' => 'klasifikasi_tema'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ai_sessions', 'ringkasan');
    }
}
