<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateVehicleTypesFreeRemoveTaxi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('daily_vehicles', [
            'total_mobil_free' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_truck'],
            'total_motor_free' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_mobil_free'],
        ]);
        if ($this->db->fieldExists('total_taxi', 'daily_vehicles')) {
            $this->forge->dropColumn('daily_vehicles', 'total_taxi');
        }
    }

    public function down()
    {
        $this->forge->addColumn('daily_vehicles', [
            'total_taxi' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_truck'],
        ]);
        $this->forge->dropColumn('daily_vehicles', ['total_mobil_free', 'total_motor_free']);
    }
}
