<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalReviewComments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'review_id'   => ['type' => 'INT', 'unsigned' => true],
            'version_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'parent_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'user_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null, 'comment' => 'NULL = external commenter'],
            'ext_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'komentar'    => ['type' => 'TEXT'],
            'tipe'        => ['type' => 'ENUM', 'constraint' => ['comment','request_revision','mark_final'], 'default' => 'comment'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('review_id');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('legal_review_comments');
    }

    public function down()
    {
        $this->forge->dropTable('legal_review_comments', true);
    }
}
