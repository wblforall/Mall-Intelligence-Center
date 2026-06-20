<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Arsip rincian metode pembayaran HARIAN. SPI hanya mengekspos breakdown payment
 * untuk HARI INI (tidak ada filter tanggal), jadi tabel ini diisi maju ke depan oleh
 * mic:spi-sync — lama-lama jadi history. Format long (1 baris per tanggal×metode).
 */
class CreateSpiPaymentDaily extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'tanggal'    => ['type' => 'DATE'],
            'method'     => ['type' => 'VARCHAR', 'constraint' => 40],
            'amount'     => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['tanggal', 'method'], true); // unique
        $this->forge->createTable('spi_payment_daily', true);
    }

    public function down()
    {
        $this->forge->dropTable('spi_payment_daily', true);
    }
}
