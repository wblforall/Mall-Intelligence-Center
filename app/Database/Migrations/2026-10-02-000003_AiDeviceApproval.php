<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pemantauan AI — enrollment berbasis PERSETUJUAN (tanpa kunci).
 *
 * Laptop memasang agen tanpa kunci apa pun; perangkat masuk daftar sebagai
 * "menunggu persetujuan" (aktif=0, disetujui_at=NULL) dan baru boleh mengirim
 * data setelah IT menekan "Setujui" di dashboard. disetujui_at menandai KAPAN
 * dan SEKALIGUS membedakan perangkat yang belum pernah disetujui (NULL) dari
 * yang pernah disetujui lalu dinonaktifkan (aktif=0 tapi disetujui_at terisi).
 */
class AiDeviceApproval extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ai_devices', [
            'disetujui_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'enrolled_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ai_devices', 'disetujui_at');
    }
}
