<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambahan/pengecualian akses aplikasi per orang, ADITIF di atas default
 * departemen — pola sama dengan `user_menu_access` terhadap
 * `department_menu_access`.
 *
 * Bisa diisi HR (lewat profil karyawan) MAUPUN admin sistem (lewat layar
 * Akses Aplikasi) — keduanya lewat model yang sama, jadi jejaknya konsisten
 * siapa pun pengisinya. `diberikan_oleh`/`dicabut_oleh` WAJIB terisi di setiap
 * perubahan (lihat EmployeeAppAccessModel::grant()/revoke()), dan setiap
 * perubahan JUGA ditulis ke `activity_logs` lewat ActivityLog::write() —
 * kolom ini untuk tampilan cepat di layar, activity_logs untuk jejak audit
 * penuh yang tidak bisa diedit ulang.
 *
 * Baris yang dicabut TIDAK dihapus (aktif=0), supaya riwayat siapa pernah
 * diberi apa tetap tersimpan.
 */
class CreateEmployeeAppAccessTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'   => ['type' => 'INT', 'unsigned' => true],
            'app_id'        => ['type' => 'INT', 'unsigned' => true],
            'app_role_id'   => ['type' => 'INT', 'unsigned' => true],
            'company_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'aktif'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'catatan'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'diberikan_oleh'=> ['type' => 'INT', 'unsigned' => true],
            'diberikan_pada'=> ['type' => 'DATETIME'],
            'dicabut_oleh'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'dicabut_pada'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['employee_id', 'app_id', 'aktif']);
        $this->forge->createTable('employee_app_access');
    }

    public function down(): void
    {
        $this->forge->dropTable('employee_app_access');
    }
}
