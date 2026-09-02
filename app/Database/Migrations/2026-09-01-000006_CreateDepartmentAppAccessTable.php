<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Default akses aplikasi per departemen — pola sama persis dengan
 * `department_menu_access`: sekali ditetapkan, karyawan baru di departemen
 * itu otomatis mewarisi aksesnya tanpa diketik ulang satu per satu.
 *
 * `company_id` NULL berarti berlaku di SEMUA unit bisnis (dipakai departemen
 * bertingkat 'holding' seperti HR-GA & Legal). `company_id` terisi berarti
 * grant ini hanya berlaku untuk karyawan departemen itu DI unit bisnis
 * tersebut (dipakai departemen bertingkat 'unit' seperti Engineering, yang
 * punya tim dan kebutuhan akses berbeda per mall).
 */
class CreateDepartmentAppAccessTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true],
            'company_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'app_id'        => ['type' => 'INT', 'unsigned' => true],
            'app_role_id'   => ['type' => 'INT', 'unsigned' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        // company_id boleh NULL, MySQL menganggap tiap NULL unik — jadi baris
        // "semua unit" dan baris per-unit untuk (dept, app) yang sama tidak
        // saling bentrok di constraint ini. Cukup, karena tingkat departemen
        // menentukan yang mana yang dipakai, bukan keduanya sekaligus.
        $this->forge->addUniqueKey(['department_id', 'company_id', 'app_id']);
        $this->forge->createTable('department_app_access');
    }

    public function down(): void
    {
        $this->forge->dropTable('department_app_access');
    }
}
