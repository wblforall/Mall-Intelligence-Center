<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'ip'          => ['type' => 'VARCHAR', 'constraint' => 45],
            'hostname'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'browser'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'browser_ver' => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],
            'platform'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'device_type' => ['type' => 'ENUM', 'constraint' => ['desktop','mobile','tablet'], 'default' => 'desktop'],
            'device_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'login_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('login_logs');

        // Add last_login_at to users
        $this->db->query("ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER is_active");
    }

    public function down()
    {
        $this->forge->dropTable('login_logs', true);
        $this->db->query("ALTER TABLE users DROP COLUMN last_login_at");
    }
}
