<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Arsip arus masuk/keluar per jam & per pintu (dari dashboard SPI /home, semua jenis kendaraan).
 * Diisi mic:spi-snapshot — data kumulatif hari ini, jadi tiap rekaman menimpa hari berjalan.
 * + kolom other_in di snapshot (okupansi "lainnya" selain mobil/motor dari load2.php).
 */
class CreateSpiFlowAndGate extends Migration
{
    public function up()
    {
        $this->forge->addColumn('spi_live_snapshot', [
            'other_in' => ['type' => 'INT', 'default' => 0, 'after' => 'motor_in'],
        ]);

        // Arus per jam (semua jenis kendaraan dijumlah)
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE'],
            'jam'        => ['type' => 'TINYINT', 'unsigned' => true],
            'masuk'      => ['type' => 'INT', 'default' => 0],
            'keluar'     => ['type' => 'INT', 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->createTable('spi_hourly_flow', true);

        // Arus per pintu (gate) per arah
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE'],
            'gate'       => ['type' => 'VARCHAR', 'constraint' => 16],
            'arah'       => ['type' => 'VARCHAR', 'constraint' => 8], // masuk | keluar
            'jumlah'     => ['type' => 'INT', 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->createTable('spi_gate_daily', true);
    }

    public function down()
    {
        $this->forge->dropColumn('spi_live_snapshot', 'other_in');
        $this->forge->dropTable('spi_hourly_flow', true);
        $this->forge->dropTable('spi_gate_daily', true);
    }
}
