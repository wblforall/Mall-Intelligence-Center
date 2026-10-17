<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Keputusan tim sponsorship atas kemungkinan sponsor DOBEL antara program mandiri
 * dan event (mis. September 2026 diinput dua kali: sebagai program dan sebagai
 * event Anniversary). Tabel ini HANYA menyimpan keputusan; tidak ada baris sponsor
 * atau realisasi yang diubah/dihapus. Keputusan 'dobel' menentukan sisi mana yang
 * dikecualikan dari total GABUNGAN; 'bukan_dobel' menyembunyikan pasangan itu dari
 * pendeteksi. Satu pasangan sponsor hanya punya satu keputusan (unik).
 * Hanya MENAMBAH tabel baru.
 */
class SponsorshipDobel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'program_sponsor_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'event_sponsor_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['dobel', 'bukan_dobel']],
            'sisi_kecuali'       => ['type' => 'ENUM', 'constraint' => ['program', 'event'], 'null' => true],
            'catatan'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'dibuat_oleh'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'dibuat_pada'        => ['type' => 'DATETIME', 'null' => true],
            'diubah_oleh'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'diubah_pada'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['program_sponsor_id', 'event_sponsor_id'], 'uq_pasangan');
        $this->forge->addKey('event_sponsor_id');
        $this->forge->createTable('sponsorship_dobel', true);
    }

    public function down()
    {
        $this->forge->dropTable('sponsorship_dobel', true);
    }
}
