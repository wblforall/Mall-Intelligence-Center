<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalReviewVersions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'review_id'           => ['type' => 'INT', 'unsigned' => true],
            'versi_ke'            => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'file_path'           => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_size'           => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'catatan_perubahan'   => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'uploaded_by'         => ['type' => 'INT', 'unsigned' => true],
            'uploaded_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('review_id');
        $this->forge->createTable('legal_review_versions');
    }

    public function down()
    {
        $this->forge->dropTable('legal_review_versions', true);
    }
}
