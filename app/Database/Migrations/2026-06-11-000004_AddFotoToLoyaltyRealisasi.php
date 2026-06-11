<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFotoToLoyaltyRealisasi extends Migration
{
    private array $tables = [
        'loyalty_hadiah_realisasi',
        'loyalty_voucher_realisasi',
        'event_loyalty_hadiah_realisasi',
        'event_loyalty_voucher_realisasi',
    ];

    public function up()
    {
        foreach ($this->tables as $t) {
            if (! $this->db->fieldExists('foto', $t)) {
                $this->forge->addColumn($t, [
                    'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'catatan'],
                ]);
            }
        }
    }

    public function down()
    {
        foreach ($this->tables as $t) {
            if ($this->db->fieldExists('foto', $t)) {
                $this->forge->dropColumn($t, 'foto');
            }
        }
    }
}
