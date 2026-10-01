<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — klasifikasi sesi (jenis aktivitas, tema, kantor/pribadi).
 *
 * Tahap pertama memakai kata kunci (gratis, tanpa API). Kolom klasifikasi_metode
 * dibuat sejak awal agar nanti sebagian sesi bisa di-reklasifikasi dengan AI
 * tanpa migrasi lagi — tinggal diisi 'ai' alih-alih 'kata_kunci'.
 */
class AiSessionKlasifikasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ai_sessions', [
            'klasifikasi_jenis'  => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'judul'],
            'klasifikasi_tema'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'klasifikasi_jenis'],
            'klasifikasi_kantor' => ['type' => 'VARCHAR', 'constraint' => 12,  'null' => true, 'after' => 'klasifikasi_tema'],
            'klasifikasi_metode' => ['type' => 'VARCHAR', 'constraint' => 12,  'null' => true, 'after' => 'klasifikasi_kantor'],
            'klasifikasi_at'     => ['type' => 'DATETIME', 'null' => true, 'after' => 'klasifikasi_metode'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ai_sessions', [
            'klasifikasi_jenis', 'klasifikasi_tema', 'klasifikasi_kantor',
            'klasifikasi_metode', 'klasifikasi_at',
        ]);
    }
}
