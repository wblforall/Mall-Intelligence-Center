<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unit bisnis holding — E-Walk, Pentacity, MIC, WBL Developer, dst.
 *
 * `parent_id` disertakan sejak awal meski belum dipakai (semua NULL): kalau
 * holding menambah anak usaha nanti, struktur bertingkat sudah siap tanpa
 * membongkar tabel yang sudah terisi grant akses.
 */
class CreateCompaniesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'parent_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('companies');
    }

    public function down(): void
    {
        $this->forge->dropTable('companies');
    }
}
