<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkInitiativeComments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'initiative_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'parent_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'body'           => ['type' => 'TEXT', 'null' => false],
            'author_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            // dept_deputy = komentar Deputy ke Dept (terlihat Deputy + Dept Head)
            // gm_deputy   = catatan GM ke Deputy atau balasan Deputy ke GM (terlihat GM + Deputy)
            'visibility'     => ['type' => 'ENUM', 'constraint' => ['dept_deputy', 'gm_deputy'], 'null' => false],
            'created_at'     => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('initiative_id');
        $this->forge->createTable('work_initiative_comments');
    }

    public function down(): void
    {
        $this->forge->dropTable('work_initiative_comments', true);
    }
}
