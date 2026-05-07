<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrafficDoors extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'mall'       => ['type' => 'ENUM', 'constraint' => ['ewalk', 'pentacity'], 'null' => false],
            'nama_pintu' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'urutan'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['mall', 'urutan']);
        $this->forge->createTable('traffic_doors');

        // Seed default doors
        $this->db->table('traffic_doors')->insertBatch([
            ['mall' => 'ewalk',     'nama_pintu' => 'Pintu Utama',    'urutan' => 1],
            ['mall' => 'ewalk',     'nama_pintu' => 'Pintu Samping',  'urutan' => 2],
            ['mall' => 'ewalk',     'nama_pintu' => 'Pintu Belakang', 'urutan' => 3],
            ['mall' => 'pentacity', 'nama_pintu' => 'Pintu Utama',    'urutan' => 1],
            ['mall' => 'pentacity', 'nama_pintu' => 'Pintu Samping',  'urutan' => 2],
            ['mall' => 'pentacity', 'nama_pintu' => 'Pintu Belakang', 'urutan' => 3],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('traffic_doors');
    }
}
