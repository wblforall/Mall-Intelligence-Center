<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyTrafficAndVehicles extends Migration
{
    public function up()
    {
        // Daily traffic per jam per pintu per mall
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'            => ['type' => 'DATE'],
            'mall'               => ['type' => 'ENUM', 'constraint' => ['ewalk', 'pentacity']],
            'jam'                => ['type' => 'TINYINT', 'unsigned' => true],
            'pintu'              => ['type' => 'VARCHAR', 'constraint' => 80],
            'jumlah_pengunjung'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tanggal', 'mall']);
        $this->forge->createTable('daily_traffic');

        // Daily vehicle count (1x per hari per mall)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'     => ['type' => 'DATE'],
            'mall'        => ['type' => 'ENUM', 'constraint' => ['ewalk', 'pentacity']],
            'total_mobil' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'total_motor' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tanggal', 'mall']);
        $this->forge->createTable('daily_vehicles');
    }

    public function down()
    {
        $this->forge->dropTable('daily_traffic');
        $this->forge->dropTable('daily_vehicles');
    }
}
