<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToEmployees extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'nik_ktp'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'nik'],
            'status_kontrak'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'status'],
            'project'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'status_kontrak'], // sumber gaji / payroll
            'pendidikan'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'project'],
            'jurusan'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'pendidikan'],
            'status_pernikahan'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'jurusan'],
            'agama'              => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'status_pernikahan'],
            'jabatan_sebelumnya' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'agama'],
            'alamat'             => ['type' => 'TEXT', 'null' => true, 'after' => 'jabatan_sebelumnya'],
            'alamat_non_bpn'     => ['type' => 'TEXT', 'null' => true, 'after' => 'alamat'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', [
            'nik_ktp', 'status_kontrak', 'project', 'pendidikan', 'jurusan',
            'status_pernikahan', 'agama', 'jabatan_sebelumnya', 'alamat', 'alamat_non_bpn',
        ]);
    }
}
