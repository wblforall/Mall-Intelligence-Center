<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menandai apakah sebuah departemen punya tim sendiri per unit bisnis
 * ('unit' — Engineering, Operational) atau layanan bersama lintas unit
 * ('holding' — HR-GA & Legal). Default 'unit' karena itu pola paling umum;
 * departemen yang benar-benar bersama harus ditandai sadar oleh HR lewat
 * layar Pengaturan Departemen, bukan ditebak dari data yang ambigu.
 */
class AddTingkatToDepartments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('departments', [
            'tingkat' => [
                'type'       => 'ENUM',
                'constraint' => ['unit', 'holding'],
                'default'    => 'unit',
                'null'       => false,
                'after'      => 'is_outsource',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('departments', 'tingkat');
    }
}
