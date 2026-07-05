<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Talent Portfolio (9-Box) — Performance × Potential.
 * - talent_periods    : periode talent review (draft/active/locked).
 * - talent_placements : penempatan per karyawan per periode (skala 1-3) + status rantai.
 * - talent_viewers    : daftar user yang boleh melihat peta penuh (dikelola admin).
 */
class CreateTalentTables extends Migration
{
    public function up(): void
    {
        // ── talent_periods ──
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'], // draft | active | locked
            'created_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'locked_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'locked_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('talent_periods');

        // ── talent_placements ──
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'period_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'employee_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'performance'     => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true], // 1-3
            'potential'       => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true], // 1-3
            'catatan'         => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'input'], // input | in_review | verified | locked
            'current_actor_id'=> ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true], // user giliran menilai/review
            'placed_by'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],  // atasan langsung pertama
            'verified_by'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'verified_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['period_id', 'employee_id']);
        $this->forge->addKey('period_id');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('talent_placements');

        // ── talent_viewers ──
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'added_by'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('talent_viewers');
    }

    public function down(): void
    {
        $this->forge->dropTable('talent_placements');
        $this->forge->dropTable('talent_viewers');
        $this->forge->dropTable('talent_periods');
    }
}
