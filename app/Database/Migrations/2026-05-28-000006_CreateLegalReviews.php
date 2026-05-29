<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalReviews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'judul'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'deskripsi'        => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'entity_type'      => ['type' => 'ENUM', 'constraint' => ['standalone','lease','permit','contract'], 'default' => 'standalone'],
            'entity_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft','in_review','revision','final','signed'], 'default' => 'draft'],
            // External party share link
            'ext_token'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'ext_token_at'     => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'ext_link_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ext_party_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'created_by'       => ['type' => 'INT', 'unsigned' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('ext_token');
        $this->forge->addKey('status');
        $this->forge->addKey('created_by');
        $this->forge->createTable('legal_reviews');
    }

    public function down()
    {
        $this->forge->dropTable('legal_reviews', true);
    }
}
