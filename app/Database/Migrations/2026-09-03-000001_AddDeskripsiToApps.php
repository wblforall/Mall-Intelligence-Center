<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Satu kalimat penjelas per aplikasi, untuk kartu di portal WBL One.
 *
 * Tanpa ini portal hanya menampilkan nama aplikasi dan peran — dan nama saja
 * tidak memberi tahu apa pun kepada orang yang belum pernah memakainya.
 * "Clara" tidak menjelaskan dirinya sendiri; "OpsJobs" juga tidak. Portal yang
 * menampilkan lima nama tanpa keterangan memindahkan pekerjaan menebak ke
 * penggunanya.
 *
 * Disimpan di basis data, bukan ditulis keras di portal: kalau ditanam di
 * kode portal, ia jadi salinan kedua yang perlahan berbeda dari kenyataan —
 * dan portal bukan pemilik data aplikasi, MIC yang pemilik.
 *
 * 160 aksara: cukup untuk satu kalimat yang benar-benar menjelaskan, terlalu
 * pendek untuk berubah jadi paragraf pemasaran di dalam kartu.
 */
class AddDeskripsiToApps extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('apps', [
            'deskripsi' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
                'after'      => 'nama',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('apps', 'deskripsi');
    }
}
