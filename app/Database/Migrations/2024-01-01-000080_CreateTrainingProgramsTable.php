<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrainingProgramsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'tipe'              => ['type' => 'ENUM', 'constraint' => ['internal', 'eksternal'], 'default' => 'internal'],
            'vendor'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'lokasi'            => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'biaya_per_peserta' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'kuota'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'], 'default' => 'draft'],
            'deskripsi'         => ['type' => 'TEXT', 'null' => true],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('training_programs');
    }

    public function down()
    {
        $this->forge->dropTable('training_programs', true);
    }
}
