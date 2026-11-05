<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * PO FlowStore ditautkan dari realisasi Content Event dan Dekorasi & VM (keputusan pemilik 5 Nov 2026, lanjutan v2.32.0).
 *
 * Berbeda dengan Creative, realisasi kedua modul ini TIDAK menulis budget_realisasi: Content Event sudah dibaca langsung
 * ke Budget Departemen E&P (BudgetRealisasiModel::dariEvent), dan VM belum memakai Budget Departemen. Baris BTB cukup
 * ditandai 'dicatat' dengan pemiliknya: tautan_modul ('content' | 'vm') + tautan_id (id event_content_realisasi /
 * event_vm_realisasi). realisasi_id tetap NULL.
 *
 * Hanya MENAMBAH kolom + indeks.
 */
class TautanPoEvent extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('tautan_modul', 'budget_fs_pemetaan')) {
            $this->forge->addColumn('budget_fs_pemetaan', [
                'tautan_modul' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'creative_realisasi_id'],
                'tautan_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tautan_modul'],
            ]);
            $this->db->query('ALTER TABLE budget_fs_pemetaan ADD INDEX idx_pem_tautan (tautan_modul, tautan_id)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('tautan_modul', 'budget_fs_pemetaan')) {
            $this->db->query('ALTER TABLE budget_fs_pemetaan DROP INDEX idx_pem_tautan');
            $this->forge->dropColumn('budget_fs_pemetaan', ['tautan_modul', 'tautan_id']);
        }
    }
}
