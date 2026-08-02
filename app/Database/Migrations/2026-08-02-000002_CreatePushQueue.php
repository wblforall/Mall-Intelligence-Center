<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Antrian push notification.
 *
 * Sengaja TIDAK mengirim langsung di dalam request: penerima notifikasi MIC
 * bisa puluhan orang (mis. seluruh pengelola HR), dan satu panggilan FCM per
 * perangkat akan menahan request web sampai belasan detik. Notify::send()
 * cukup menulis antrian (cepat), lalu cron `mic:push-dispatch` yang mengirim
 * — sekaligus memberi kita percobaan ulang bila FCM sedang bermasalah.
 */
class CreatePushQueue extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'notification_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'body'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'url'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'module'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed', 'skipped'], 'default' => 'pending'],
            'attempts'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'last_error'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'sent_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('push_queue');
    }

    public function down()
    {
        $this->forge->dropTable('push_queue', true);
    }
}
