<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThemePeriods extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_date' => ['type' => 'DATE'],
            'end_date'   => ['type' => 'DATE'],
            'alert_days' => ['type' => 'TINYINT', 'default' => 7],
            'animation'  => ['type' => 'ENUM', 'constraint' => ['none','confetti','balloons','snow','fireworks','stars'], 'default' => 'confetti'],
            'emoji'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'pesan'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('theme_periods');
    }

    public function down()
    {
        $this->forge->dropTable('theme_periods', true);
    }
}
