<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Lengkapi arsip lokal parkir agar Vehicles Summary 100% dari DB (tanpa live):
 *  - Kolom free per jenis (box/truck/taxi/bus) di spi_vehicle_daily — mobil/motor sudah ada.
 *    Diisi mic:spi-sync dari table-casual-income (passout per jenis).
 *  - Tabel spi_duration_daily: distribusi lama parkir per HARI (8 bucket), diisi dari
 *    reporting2_api/statistik.php (di-chunk karena endpoint kosong utk rentang besar).
 */
class AddParkingFreeTypesAndDuration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('spi_vehicle_daily', [
            'box_free'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'motor_free'],
            'truck_free' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'box_free'],
            'taxi_free'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'truck_free'],
            'bus_free'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'taxi_free'],
        ]);

        $this->forge->addField([
            'tanggal'    => ['type' => 'DATE'],
            'le1'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h1_2'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h2_3'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h3_4'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h4_5'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h5_6'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'h6_7'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'gt7'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('tanggal', true);
        $this->forge->createTable('spi_duration_daily', true);
    }

    public function down()
    {
        $this->forge->dropColumn('spi_vehicle_daily', ['box_free', 'truck_free', 'taxi_free', 'bus_free']);
        $this->forge->dropTable('spi_duration_daily', true);
    }
}
