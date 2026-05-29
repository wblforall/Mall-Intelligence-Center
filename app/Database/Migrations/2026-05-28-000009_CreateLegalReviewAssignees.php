<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalReviewAssignees extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'review_id'   => ['type' => 'INT', 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'assigned_by' => ['type' => 'INT', 'unsigned' => true],
            'assigned_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['review_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('legal_review_assignees');
    }

    public function down()
    {
        $this->forge->dropTable('legal_review_assignees', true);
    }
}
