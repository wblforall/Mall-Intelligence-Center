<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeCertificatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'         => ['type' => 'INT', 'unsigned' => true],
            'nama_sertifikat'     => ['type' => 'VARCHAR', 'constraint' => 200],
            'nomor_sertifikat'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'penerbit'            => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'tanggal_terbit'      => ['type' => 'DATE', 'null' => true],
            'tanggal_kadaluarsa'  => ['type' => 'DATE', 'null' => true],
            'file_name'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'file_original'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'catatan'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('employee_certificates');
    }

    public function down()
    {
        $this->forge->dropTable('employee_certificates', true);
    }
}
