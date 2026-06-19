<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanggalAkhirKontrakToEmployees extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'tanggal_akhir_kontrak' => ['type' => 'DATE', 'null' => true, 'after' => 'status_kontrak'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', ['tanggal_akhir_kontrak']);
    }
}
