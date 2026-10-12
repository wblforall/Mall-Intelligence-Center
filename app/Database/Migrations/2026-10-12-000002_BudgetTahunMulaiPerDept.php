<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tahun mulai Budget Departemen per dept.
 *
 * Bawaan modul mulai 2027 (LPSS menyusun 2027). CCD melanjutkan budget &
 * realisasi 2026 dari Excel, jadi tahun mulai perlu bisa dimajukan per dept.
 * NULL = ikut bawaan modul (BudgetDept::TAHUN_MULAI).
 */
class BudgetTahunMulaiPerDept extends Migration
{
    public function up()
    {
        $this->forge->addColumn('budget_dept_setting', [
            'tahun_mulai' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true, 'null' => true, 'after' => 'pisah_mal'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('budget_dept_setting', 'tahun_mulai');
    }
}
