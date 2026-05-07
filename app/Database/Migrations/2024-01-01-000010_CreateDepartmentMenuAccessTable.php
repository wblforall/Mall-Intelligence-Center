<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentMenuAccessTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true],
            'menu_key'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'section_type'  => ['type' => 'ENUM', 'constraint' => ['all', 'traffic', 'loyalty', 'commercial', 'tenant'], 'default' => 'all'],
            'can_view'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'can_edit'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['department_id', 'menu_key']);
        $this->forge->createTable('department_menu_access');
    }

    public function down()
    {
        $this->forge->dropTable('department_menu_access');
    }
}
