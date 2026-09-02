<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kosakata peran milik masing-masing aplikasi, APA ADANYA — bukan peran
 * "universal" yang dipetakan ke lima sistem. MIC menyimpan kode peran milik
 * sistem itu sendiri dan mengirimkannya mentah-mentah saat SSO; sistem
 * tujuan yang menerjemahkannya ke izin menunya sendiri.
 */
class CreateAppRolesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'app_id'     => ['type' => 'INT', 'unsigned' => true],
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['app_id', 'kode']);
        $this->forge->createTable('app_roles');
    }

    public function down(): void
    {
        $this->forge->dropTable('app_roles');
    }
}
