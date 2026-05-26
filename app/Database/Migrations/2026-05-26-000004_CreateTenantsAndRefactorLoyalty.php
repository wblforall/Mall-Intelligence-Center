<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsAndRefactorLoyalty extends Migration
{
    public function up()
    {
        // Master tenant
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'kategori'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'lantai'         => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'nomor_unit'     => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true],
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'no_hp'          => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants');

        // Replace nama_tenant (text) → tenant_id (FK)
        $this->forge->addColumn('loyalty_programs', [
            'tenant_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'nama_tenant',
            ],
        ]);
        $this->forge->dropColumn('loyalty_programs', 'nama_tenant');
    }

    public function down()
    {
        $this->forge->dropTable('tenants');
        $this->forge->addColumn('loyalty_programs', [
            'nama_tenant' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'jenis',
            ],
        ]);
        $this->forge->dropColumn('loyalty_programs', 'tenant_id');
    }
}
