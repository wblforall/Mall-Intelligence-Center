<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'jenis'        => ['type' => 'VARCHAR', 'constraint' => 20], // ktp|npwp|kk|ijazah|lainnya
            'nama_dokumen' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true], // label, terutama untuk "lainnya"
            'file_name'    => ['type' => 'VARCHAR', 'constraint' => 191], // nama file tersimpan
            'file_asli'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true], // nama asli upload
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'uploaded_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'reviewed_by'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'reviewed_at'  => ['type' => 'DATETIME', 'null' => true],
            'catatan'      => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['employee_id']);
        $this->forge->addKey(['status']);
        $this->forge->createTable('employee_documents');
    }

    public function down()
    {
        $this->forge->dropTable('employee_documents');
    }
}
