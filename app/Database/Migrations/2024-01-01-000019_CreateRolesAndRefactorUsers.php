<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesAndRefactorUsers extends Migration
{
    public function up()
    {
        // Create roles table
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_admin'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_create_event' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_delete_event' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_manage_users' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('roles');

        // Seed default roles
        $now = date('Y-m-d H:i:s');
        $this->db->table('roles')->insertBatch([
            ['name' => 'Admin',    'slug' => 'admin',    'description' => 'Full system access', 'is_admin' => 1, 'can_create_event' => 1, 'can_delete_event' => 1, 'can_manage_users' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manager',  'slug' => 'manager',  'description' => 'Can create and delete events', 'is_admin' => 0, 'can_create_event' => 1, 'can_delete_event' => 1, 'can_manage_users' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Operator', 'slug' => 'operator', 'description' => 'Standard input access', 'is_admin' => 0, 'can_create_event' => 0, 'can_delete_event' => 0, 'can_manage_users' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Add role_id to users
        $this->forge->addColumn('users', [
            'role_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'role'],
        ]);

        // Populate role_id from existing role slug
        $this->db->query("UPDATE users u JOIN roles r ON r.slug = u.role SET u.role_id = r.id");

        // Change users.role from ENUM to VARCHAR(50) to support custom role slugs
        $this->db->query("ALTER TABLE users MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'operator'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE users MODIFY COLUMN `role` ENUM('admin','manager','operator') NOT NULL DEFAULT 'operator'");
        $this->forge->dropColumn('users', 'role_id');
        $this->forge->dropTable('roles');
    }
}
