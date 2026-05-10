<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSponsorshipPrograms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_program'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal_mulai'   => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'deskripsi'       => ['type' => 'TEXT', 'null' => true],
            'target_sponsor'  => ['type' => 'INT', 'null' => true],
            'target_nilai'    => ['type' => 'BIGINT', 'null' => true],
            'budget'          => ['type' => 'BIGINT', 'default' => 0],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'locked'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'locked_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'locked_at'       => ['type' => 'DATETIME', 'null' => true],
            'catatan'         => ['type' => 'TEXT', 'null' => true],
            'created_by'      => ['type' => 'INT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sponsorship_programs');
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_programs', true);
    }
}
