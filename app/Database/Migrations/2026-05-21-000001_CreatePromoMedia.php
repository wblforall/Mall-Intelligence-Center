<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromoMedia extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'tipe'        => ['type' => 'ENUM', 'constraint' => ['t_banner', 'hanging', 'digital']],
            'area'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ukuran'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'total_slots' => ['type' => 'TINYINT', 'constraint' => 3, 'default' => 1],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'catatan'     => ['type' => 'TEXT', 'null' => true],
            'created_by'  => ['type' => 'INT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('promo_media_spots');

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'spot_id'           => ['type' => 'INT'],
            'slot_number'       => ['type' => 'TINYINT', 'constraint' => 3, 'null' => true],
            'dept'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'requested_by'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'event_id'          => ['type' => 'INT', 'null' => true],
            'nama_materi'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'deskripsi_materi'  => ['type' => 'TEXT', 'null' => true],
            'tanggal_mulai'     => ['type' => 'DATE'],
            'tanggal_selesai'   => ['type' => 'DATE'],
            'status'            => ['type' => 'ENUM', 'constraint' => ['draft', 'pending', 'approved', 'rejected', 'done'], 'default' => 'draft'],
            'catatan_pemohon'   => ['type' => 'TEXT', 'null' => true],
            'catatan_approver'  => ['type' => 'TEXT', 'null' => true],
            'rejection_reason'  => ['type' => 'TEXT', 'null' => true],
            'submitted_at'      => ['type' => 'DATETIME', 'null' => true],
            'approved_by'       => ['type' => 'INT', 'null' => true],
            'approved_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_by'        => ['type' => 'INT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('spot_id');
        $this->forge->addKey('status');
        $this->forge->addKey(['tanggal_mulai', 'tanggal_selesai']);
        $this->forge->createTable('promo_media_usage');
    }

    public function down(): void
    {
        $this->forge->dropTable('promo_media_usage', true);
        $this->forge->dropTable('promo_media_spots', true);
    }
}
