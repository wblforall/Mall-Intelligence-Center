<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Katalog kelima sistem yang bisa diberi akses lewat portal. */
class CreateAppsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ikon'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('apps');
    }

    public function down(): void
    {
        $this->forge->dropTable('apps');
    }
}
