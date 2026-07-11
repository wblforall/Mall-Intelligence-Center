<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Foto bukti pada update progress Progress Report (khusus gambar).
 * File fisik di public/uploads/work_report/{initiative_id}/.
 */
class CreateWorkInitiativeUpdateImages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'update_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'initiative_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'file_name'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('update_id');
        $this->forge->addKey('initiative_id');
        $this->forge->createTable('work_initiative_update_images');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiative_update_images');
    }
}
