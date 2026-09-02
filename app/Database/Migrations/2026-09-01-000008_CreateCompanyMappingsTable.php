<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ID unit bisnis TIDAK sinkron antar sistem — E-Walk adalah unit id 1 di
 * PAM e-Sign, properti id 1 di Clara, tapi skema id 2 di FlowStore. Tabel ini
 * memetakan id/kode lokal tiap sistem ke `companies.id` MIC, supaya tidak ada
 * kode yang mengasumsikan angkanya sama di semua tempat.
 */
class CreateCompanyMappingsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'app_id'     => ['type' => 'INT', 'unsigned' => true],
            'kode_lokal' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'id_lokal'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['company_id', 'app_id']);
        $this->forge->createTable('company_mappings');
    }

    public function down(): void
    {
        $this->forge->dropTable('company_mappings');
    }
}
