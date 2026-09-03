<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Urutan tampil kartu aplikasi di portal.
 *
 * Sebelumnya kartu diurutkan menurut NAMA aplikasi. Itu stabil — susunannya
 * tidak berubah-ubah setiap muat — tapi urutannya kebetulan, bukan pilihan:
 * aplikasi yang dibuka tiap hari bisa terdorong ke belakang hanya karena
 * namanya berawalan huruf akhir.
 *
 * Kecil (TINYINT) dan diberi bawaan 50, bukan 0: aplikasi baru mendarat di
 * tengah, jadi ia tidak diam-diam melompat ke urutan pertama sebelum ada yang
 * memutuskan tempatnya.
 */
class AddUrutanToApps extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('apps', [
            'urutan' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 50,
                'after'      => 'ikon',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('apps', 'urutan');
    }
}
