<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePipReviews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pip_id'         => ['type' => 'INT', 'unsigned' => true],
            'tanggal_review' => ['type' => 'DATE'],
            'reviewer_name'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'progres'        => ['type' => 'ENUM', 'constraint' => ['baik','cukup','kurang'], 'default' => 'cukup'],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pip_id');
        $this->forge->createTable('pip_reviews');
    }

    public function down()
    {
        $this->forge->dropTable('pip_reviews', true);
    }
}
