<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockTables extends Migration
{
    public function up(): void
    {
        // Master barang / hadiah fisik
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'nama_barang'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'satuan'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'pcs'],
            'nilai_satuan'   => ['type' => 'BIGINT', 'default' => 0],
            'stok_awal'      => ['type' => 'INT', 'default' => 0],
            'stok_tersedia'  => ['type' => 'INT', 'default' => 0],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stock_barang');

        // Log keluar/masuk stok barang
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'barang_id'       => ['type' => 'INT', 'null' => false],
            'tipe'            => ['type' => 'ENUM', 'constraint' => ['masuk', 'keluar'], 'default' => 'keluar'],
            'jumlah'          => ['type' => 'INT', 'null' => false],
            'stok_sebelum'    => ['type' => 'INT', 'null' => false],
            'stok_sesudah'    => ['type' => 'INT', 'null' => false],
            'referensi_tipe'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'referensi_id'    => ['type' => 'INT', 'null' => true],
            'tanggal'         => ['type' => 'DATE', 'null' => false],
            'catatan'         => ['type' => 'TEXT', 'null' => true],
            'created_by'      => ['type' => 'INT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('barang_id');
        $this->forge->createTable('stock_barang_log');

        // Batch voucher fisik
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'nama_voucher'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'nilai_voucher'  => ['type' => 'BIGINT', 'default' => 0],
            'expired_date'   => ['type' => 'DATE', 'null' => true],
            'total_kode'     => ['type' => 'INT', 'default' => 0],
            'sisa_kode'      => ['type' => 'INT', 'default' => 0],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stock_voucher_batch');

        // Kode unik per batch
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'batch_id'       => ['type' => 'INT', 'null' => false],
            'kode'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'status'         => ['type' => 'ENUM', 'constraint' => ['available', 'assigned', 'expired'], 'default' => 'available'],
            'nama_penerima'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'assigned_at'    => ['type' => 'DATETIME', 'null' => true],
            'program_type'   => ['type' => 'ENUM', 'constraint' => ['standalone', 'event', 'manual'], 'null' => true],
            'program_id'     => ['type' => 'INT', 'null' => true],
            'item_id'        => ['type' => 'INT', 'null' => true],
            'realisasi_id'   => ['type' => 'INT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('batch_id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('stock_voucher_kode');
    }

    public function down(): void
    {
        $this->forge->dropTable('stock_voucher_kode', true);
        $this->forge->dropTable('stock_voucher_batch', true);
        $this->forge->dropTable('stock_barang_log', true);
        $this->forge->dropTable('stock_barang', true);
    }
}
