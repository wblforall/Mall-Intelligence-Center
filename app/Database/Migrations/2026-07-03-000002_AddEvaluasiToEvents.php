<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom evaluasi/kesimpulan naratif untuk Laporan Post Event.
 * Diisi setelah event selesai; tampil sebagai section naratif di laporan.
 */
class AddEvaluasiToEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('events', [
            'eval_kesimpulan'  => ['type' => 'TEXT', 'null' => true, 'after' => 'tema'],
            'eval_pencapaian'  => ['type' => 'TEXT', 'null' => true, 'after' => 'eval_kesimpulan'],
            'eval_kendala'     => ['type' => 'TEXT', 'null' => true, 'after' => 'eval_pencapaian'],
            'eval_rekomendasi' => ['type' => 'TEXT', 'null' => true, 'after' => 'eval_kendala'],
            'eval_updated_at'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'eval_rekomendasi'],
            'eval_updated_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'eval_updated_at'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('events', [
            'eval_kesimpulan', 'eval_pencapaian', 'eval_kendala',
            'eval_rekomendasi', 'eval_updated_at', 'eval_updated_by',
        ]);
    }
}
