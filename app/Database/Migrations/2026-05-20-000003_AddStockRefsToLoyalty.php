<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStockRefsToLoyalty extends Migration
{
    public function up(): void
    {
        // loyalty_hadiah_items → barang_id
        $this->forge->addColumn('loyalty_hadiah_items', [
            'barang_id' => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'program_id'],
        ]);

        // event_loyalty_hadiah_items → barang_id
        $this->forge->addColumn('event_loyalty_hadiah_items', [
            'barang_id' => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'program_id'],
        ]);

        // loyalty_voucher_items → batch_id
        $this->forge->addColumn('loyalty_voucher_items', [
            'batch_id' => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'program_id'],
        ]);

        // event_loyalty_voucher_items → batch_id
        $this->forge->addColumn('event_loyalty_voucher_items', [
            'batch_id' => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'program_id'],
        ]);

        // loyalty_hadiah_realisasi → nama_penerima
        $this->forge->addColumn('loyalty_hadiah_realisasi', [
            'nama_penerima' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'item_id'],
        ]);

        // event_loyalty_hadiah_realisasi → nama_penerima
        $this->forge->addColumn('event_loyalty_hadiah_realisasi', [
            'nama_penerima' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'item_id'],
        ]);

        // loyalty_voucher_realisasi → kode_id + nama_penerima
        $this->forge->addColumn('loyalty_voucher_realisasi', [
            'kode_id'       => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'item_id'],
            'nama_penerima' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'kode_id'],
        ]);

        // event_loyalty_voucher_realisasi → kode_id + nama_penerima
        $this->forge->addColumn('event_loyalty_voucher_realisasi', [
            'kode_id'       => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'item_id'],
            'nama_penerima' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'kode_id'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('event_loyalty_voucher_realisasi', ['kode_id', 'nama_penerima']);
        $this->forge->dropColumn('loyalty_voucher_realisasi', ['kode_id', 'nama_penerima']);
        $this->forge->dropColumn('event_loyalty_hadiah_realisasi', 'nama_penerima');
        $this->forge->dropColumn('loyalty_hadiah_realisasi', 'nama_penerima');
        $this->forge->dropColumn('event_loyalty_voucher_items', 'batch_id');
        $this->forge->dropColumn('loyalty_voucher_items', 'batch_id');
        $this->forge->dropColumn('event_loyalty_hadiah_items', 'barang_id');
        $this->forge->dropColumn('loyalty_hadiah_items', 'barang_id');
    }
}
