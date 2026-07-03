<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Riwayat training per-karyawan (input manual) — untuk training eksternal
 * atau training lama sebelum modul Training program-based dibuat.
 * Berdampingan dengan training_participants (peserta program internal).
 */
class CreateEmployeeTrainings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'nama'               => ['type' => 'VARCHAR', 'constraint' => 200],
            'tipe'               => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'eksternal'],
            'penyelenggara'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_mulai'      => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'    => ['type' => 'DATE', 'null' => true],
            'sertifikat_file'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sertifikat_original'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'catatan'            => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('employee_trainings');
    }

    public function down(): void
    {
        $this->forge->dropTable('employee_trainings');
    }
}
