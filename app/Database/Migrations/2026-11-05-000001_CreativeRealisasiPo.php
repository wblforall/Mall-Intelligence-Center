<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Realisasi Creative bertaut PO FlowStore (keputusan pemilik 5 Nov 2026).
 *
 * CCD mencatat realisasi di modul Creative (dengan bukti) dan memilih baris BTB dari PO FlowStore; baris itu langsung
 * tercatat di Budget Departemen CCD. Pemiliknya disimpan di budget_fs_pemetaan.creative_realisasi_id — id di
 * event_creative_realisasi (creative_scope 'event') atau creative_realisasi ('standalone'). Baris dengan kolom ini terisi
 * sudah terhitung lewat nilai realisasi Creative-nya, jadi tidak dijumlah lagi sebagai "Biaya dari FlowStore".
 *
 * Hanya MENAMBAH kolom + indeks.
 */
class CreativeRealisasiPo extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('creative_realisasi_id', 'budget_fs_pemetaan')) {
            $this->forge->addColumn('budget_fs_pemetaan', [
                'creative_realisasi_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'creative_item_id'],
            ]);
            $this->db->query('ALTER TABLE budget_fs_pemetaan ADD INDEX idx_pem_cr_real (creative_scope, creative_realisasi_id)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('creative_realisasi_id', 'budget_fs_pemetaan')) {
            $this->db->query('ALTER TABLE budget_fs_pemetaan DROP INDEX idx_pem_cr_real');
            $this->forge->dropColumn('budget_fs_pemetaan', 'creative_realisasi_id');
        }
    }
}
