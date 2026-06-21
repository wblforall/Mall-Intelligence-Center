<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rekaman snapshot LIVE parkir SPI (okupansi + income berjalan + payment) tiap ±15 menit
 * via command mic:spi-snapshot (cron). SPI tak menyimpan histori real-time — kita rekam sendiri
 * untuk: tren okupansi intraday, angka harian preliminary, & rekonsiliasi vs data final SPI.
 */
class CreateSpiLiveSnapshot extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'captured_at'      => ['type' => 'DATETIME'],
            'tanggal'          => ['type' => 'DATE'],
            'total_in'         => ['type' => 'INT', 'default' => 0],
            'mobil_in'         => ['type' => 'INT', 'default' => 0],
            'motor_in'         => ['type' => 'INT', 'default' => 0],
            'lot_mobil_avail'  => ['type' => 'INT', 'default' => 0],
            'lot_motor_avail'  => ['type' => 'INT', 'default' => 0],
            'total_income'     => ['type' => 'BIGINT', 'default' => 0],
            'tunai'            => ['type' => 'BIGINT', 'default' => 0],
            'nontunai'         => ['type' => 'BIGINT', 'default' => 0],
            'payments_json'    => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->addKey('captured_at');
        $this->forge->createTable('spi_live_snapshot', true);
    }

    public function down()
    {
        $this->forge->dropTable('spi_live_snapshot', true);
    }
}
