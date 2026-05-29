<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'entity_type'   => ['type' => 'ENUM', 'constraint' => ['lease','permit','contract'], 'comment' => 'Tipe entitas terkait'],
            'entity_id'     => ['type' => 'INT', 'unsigned' => true],
            'nama_dokumen'  => ['type' => 'VARCHAR', 'constraint' => 200],
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_size'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'uploaded_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'uploaded_at'   => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('legal_documents');
    }

    public function down()
    {
        $this->forge->dropTable('legal_documents', true);
    }
}
