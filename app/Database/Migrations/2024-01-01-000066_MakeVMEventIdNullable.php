<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeVMEventIdNullable extends Migration
{
    public function up()
    {
        $db = db_connect();
        $db->query('ALTER TABLE event_vm_items MODIFY event_id INT UNSIGNED NULL');
        $db->query('ALTER TABLE event_vm_realisasi MODIFY event_id INT UNSIGNED NULL');
    }

    public function down()
    {
        // Tidak bisa dibalik dengan aman — baris standalone akan melanggar NOT NULL.
    }
}
