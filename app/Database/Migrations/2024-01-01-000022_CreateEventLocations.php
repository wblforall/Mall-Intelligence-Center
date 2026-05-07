<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventLocations extends Migration
{
    public function up(): void
    {
        // Master lokasi
        $this->forge->addField([
            'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama'  => ['type' => 'VARCHAR', 'constraint' => 200],
            'mall'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'aktif' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('event_locations');

        // Seed defaults
        $this->db->table('event_locations')->insertBatch([
            ['nama' => 'GF Atrium eWalk',           'mall' => 'ewalk',     'aktif' => 1],
            ['nama' => 'GF Atrium Pentacity',        'mall' => 'pentacity', 'aktif' => 1],
            ['nama' => 'LG Atrium Family Pentacity', 'mall' => 'pentacity', 'aktif' => 1],
        ]);

        // Pivot: satu event bisa punya banyak lokasi
        $this->forge->addField([
            'event_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'location_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['event_id', 'location_id']);
        $this->forge->createTable('event_location_map');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_location_map');
        $this->forge->dropTable('event_locations');
    }
}
