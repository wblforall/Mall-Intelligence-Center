<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Capture per JENIS kendaraan (dari dashboard SPI /home): masuk (count) + income (rupiah),
 * dikelompokkan ke jenis dasar (mobil/motor/box/truck/taxi/bus). Untuk compare per-jenis
 * dengan data final SPI. Diisi mic:spi-snapshot (kumulatif hari ini → ganti penuh).
 */
class CreateSpiCaptureType extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE'],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 12],
            'masuk'      => ['type' => 'INT', 'default' => 0],
            'income'     => ['type' => 'BIGINT', 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->createTable('spi_capture_type_daily', true);
    }

    public function down()
    {
        $this->forge->dropTable('spi_capture_type_daily', true);
    }
}
