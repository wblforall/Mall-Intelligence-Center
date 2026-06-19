<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserMenuAccess extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'menu_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'can_view'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_edit'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id']);
        $this->forge->createTable('user_menu_access');
    }

    public function down()
    {
        $this->forge->dropTable('user_menu_access');
    }
}
