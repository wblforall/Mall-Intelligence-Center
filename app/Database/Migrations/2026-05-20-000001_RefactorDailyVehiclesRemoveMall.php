<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorDailyVehiclesRemoveMall extends Migration
{
    public function up()
    {
        // Clear existing per-mall data
        $this->db->query('TRUNCATE TABLE daily_vehicles');

        // Drop old unique key (tanggal, mall)
        $this->db->query('ALTER TABLE daily_vehicles DROP INDEX tanggal_mall');

        // Drop mall column
        $this->forge->dropColumn('daily_vehicles', 'mall');

        // Add new unique key on tanggal only
        $this->db->query('ALTER TABLE daily_vehicles ADD UNIQUE KEY tanggal (tanggal)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE daily_vehicles DROP INDEX tanggal');

        $this->forge->addColumn('daily_vehicles', [
            'mall' => [
                'type'       => 'ENUM',
                'constraint' => ['ewalk', 'pentacity'],
                'null'       => false,
                'after'      => 'tanggal',
            ],
        ]);

        $this->db->query('ALTER TABLE daily_vehicles ADD UNIQUE KEY tanggal (tanggal, mall)');
    }
}
