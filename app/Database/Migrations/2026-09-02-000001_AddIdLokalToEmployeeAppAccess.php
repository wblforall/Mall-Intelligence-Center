<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Id akun di aplikasi tujuan (mis. `pamsign.users.id`).
 *
 * Tanpa ini, tautan MIC↔aplikasi lain hanya bisa ditemukan dengan MENCOCOKKAN
 * email atau nama setiap kali dibutuhkan — dan pencocokan itu rapuh: dari 61
 * akun PAM e-Sign, hanya 3 yang emailnya sama dengan `email_kerja` di MIC,
 * 31 cocok lewat email pribadi, dan 13 hanya cocok lewat nama.
 *
 * Dengan `id_lokal` tersimpan, tautannya jadi FAKTA: email di sisi mana pun
 * boleh berubah tanpa memutus hubungan. Itu juga yang membuat penyeragaman
 * email kerja nanti aman dikerjakan (lihat PROSEDUR-EMAIL-KERJA.md §0).
 *
 * VARCHAR, bukan INT: tidak semua sistem memakai id numerik — Clara memakai
 * `property_id` numerik tapi OpsJobs punya `orgId`/`companyId` berupa teks.
 */
class AddIdLokalToEmployeeAppAccess extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employee_app_access', [
            'id_lokal' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'company_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employee_app_access', 'id_lokal');
    }
}
