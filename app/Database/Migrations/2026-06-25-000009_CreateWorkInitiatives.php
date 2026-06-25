<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkInitiatives extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dept_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'divisi_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'judul'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'deskripsi'           => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'pic_employee_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'target_mulai'        => ['type' => 'DATE', 'null' => true, 'default' => null],
            'target_selesai'      => ['type' => 'DATE', 'null' => true, 'default' => null],
            // null = dibuat Dept Head sendiri; diisi = Deputy assign ke dept ini
            'assigned_to_dept_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('dept_id');
        $this->forge->addKey('divisi_id');
        $this->forge->addKey('assigned_to_dept_id');
        $this->forge->createTable('work_initiatives');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiatives', true);
    }
}
