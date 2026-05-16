<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePublicHolidays extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => false],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'jenis'      => ['type' => 'ENUM', 'constraint' => ['nasional', 'bersama', 'lokal'], 'default' => 'nasional'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->createTable('public_holidays');
    }

    public function down(): void
    {
        $this->forge->dropTable('public_holidays', true);
    }
}
