<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppraisalPeriods extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 150], // mis. "Juli - Desember 2025"
            'tanggal_mulai'   => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'tahun'           => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['open', 'closed'], 'default' => 'open'],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('appraisal_periods');
    }

    public function down()
    {
        $this->forge->dropTable('appraisal_periods', true);
    }
}
