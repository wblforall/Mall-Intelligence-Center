<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordResets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 128, 'unique' => true],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('email');
        $this->forge->createTable('password_resets');
    }

    public function down()
    {
        $this->forge->dropTable('password_resets');
    }
}
