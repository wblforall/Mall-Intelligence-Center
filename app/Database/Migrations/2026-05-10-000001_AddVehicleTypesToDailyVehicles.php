<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleTypesToDailyVehicles extends Migration
{
    public function up()
    {
        $fields = [
            'total_mobil_box' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_motor'],
            'total_bus'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_mobil_box'],
            'total_truck'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_bus'],
            'total_taxi'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'total_truck'],
        ];
        $this->forge->addColumn('daily_vehicles', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('daily_vehicles', ['total_mobil_box', 'total_bus', 'total_truck', 'total_taxi']);
    }
}
