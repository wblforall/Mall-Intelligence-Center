<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEconomicIndicators extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS economic_indicators (
                `key`       VARCHAR(64)  NOT NULL PRIMARY KEY,
                `value`     TEXT         NOT NULL,
                `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Seed default BBM prices (Pertamina, Jan 2025)
        $bbm = json_encode([
            ['nama' => 'Pertalite RON 90', 'harga' => 10000, 'subsidi' => true],
            ['nama' => 'Pertamax RON 92',  'harga' => 12900, 'subsidi' => false],
            ['nama' => 'Pertamax Turbo',   'harga' => 14400, 'subsidi' => false],
            ['nama' => 'Biosolar B35',     'harga' =>  6800, 'subsidi' => true],
            ['nama' => 'Dexlite',          'harga' => 13950, 'subsidi' => false],
            ['nama' => 'Pertamina Dex',    'harga' => 14900, 'subsidi' => false],
        ]);

        $this->db->query("
            INSERT INTO economic_indicators (`key`, `value`) VALUES
                ('bbm_prices', ?),
                ('bbm_per',    'Jan 2025')
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ", [$bbm]);
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS economic_indicators');
    }
}
