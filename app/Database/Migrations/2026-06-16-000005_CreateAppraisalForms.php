<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalForms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'period_id'        => ['type' => 'INT', 'unsigned' => true],
            'employee_id'      => ['type' => 'INT', 'unsigned' => true],
            'jabatan_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true], // snapshot saat generate
            'template_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],  // sumber snapshot
            // bobot final snapshot
            'bobot_kpi'        => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0.60],
            'bobot_kompetensi' => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0.40],
            // hasil (0-100), null sampai diisi
            'skor_kpi'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'skor_kompetensi'  => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'nilai_akhir'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            // alur: input(atasan langsung) -> in_review(naik rantai s/d Deputy) -> hr_review -> finalized
            'status'           => ['type' => 'ENUM', 'constraint' => ['input', 'in_review', 'hr_review', 'finalized'], 'default' => 'input'],
            'current_user_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true], // siapa yang harus aksi sekarang
            'penilai_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],  // atasan langsung yg input awal
            'pendapat_karyawan'=> ['type' => 'TEXT', 'null' => true],
            'finalized_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'finalized_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['period_id', 'employee_id']);
        $this->forge->addKey('current_user_id');
        $this->forge->createTable('appraisal_forms');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_forms', true);
    }
}
