<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel salinan lokal (read-only mirror) data parkir SPI untuk arsip & ketahanan
 * (Hybrid sync). Diisi oleh command mic:spi-sync. Sumber kebenaran tetap di SPI.
 */
class CreateSpiParkingTables extends Migration
{
    public function up()
    {
        // Qty kendaraan harian (casual/bayar) + langganan (pass/free) per jenis
        $this->forge->addField([
            'tanggal'    => ['type' => 'DATE'],
            'mobil'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'motor'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'box'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'truck'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'taxi'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'bus'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'total'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'mobil_free' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'motor_free' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('tanggal', true); // unique by date
        $this->forge->createTable('spi_vehicle_daily', true);

        // Income harian per jenis (basis tanggal tiket)
        $this->forge->addField([
            'tanggal'    => ['type' => 'DATE'],
            'mobil'      => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'motor'      => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'box'        => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'truck'      => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'taxi'       => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'bus'        => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'total'      => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('tanggal', true);
        $this->forge->createTable('spi_income_daily', true);

        // Income bulanan resmi (basis tanggal bayar) — casual & member
        $this->forge->addField([
            'bulan'      => ['type' => 'CHAR', 'constraint' => 7], // YYYY-MM
            'casual'     => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'member'     => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('bulan', true);
        $this->forge->createTable('spi_income_monthly', true);
    }

    public function down()
    {
        $this->forge->dropTable('spi_vehicle_daily', true);
        $this->forge->dropTable('spi_income_daily', true);
        $this->forge->dropTable('spi_income_monthly', true);
    }
}
