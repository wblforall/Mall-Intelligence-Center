<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('app_settings');

        // Seed: traffic summary recipients (JSON array of emails)
        db_connect()->table('app_settings')->insert([
            'key'        => 'traffic_summary_emails',
            'value'      => '[]',
            'label'      => 'Penerima Email Traffic Summary',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('app_settings');
    }
}
