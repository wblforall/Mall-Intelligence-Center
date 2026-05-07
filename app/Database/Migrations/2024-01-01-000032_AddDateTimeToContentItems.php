<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDateTimeToContentItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_content_items', [
            'tanggal'       => ['type' => 'DATE', 'null' => true, 'after' => 'nama'],
            'waktu_mulai'   => ['type' => 'TIME', 'null' => true, 'after' => 'tanggal'],
            'waktu_selesai' => ['type' => 'TIME', 'null' => true, 'after' => 'waktu_mulai'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_content_items', ['tanggal', 'waktu_mulai', 'waktu_selesai']);
    }
}
