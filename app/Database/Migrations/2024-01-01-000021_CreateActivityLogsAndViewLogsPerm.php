<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsAndViewLogsPerm extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
            'user_role'    => ['type' => 'VARCHAR', 'constraint' => 50,  'default' => ''],
            'action'       => ['type' => 'ENUM', 'constraint' => ['login','logout','login_failed','create','update','delete'], 'default' => 'create'],
            'module'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'target_id'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'target_label' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'detail'       => ['type' => 'TEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45,  'null' => true],
            'created_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['module', 'created_at']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('activity_logs');

        $this->db->query("ALTER TABLE roles ADD COLUMN can_view_logs TINYINT(1) NOT NULL DEFAULT 0 AFTER can_delete_traffic");
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
        $this->db->query("ALTER TABLE roles DROP COLUMN can_view_logs");
    }
}
