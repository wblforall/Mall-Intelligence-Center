<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data acuan untuk portal akses aplikasi: unit bisnis, katalog aplikasi, dan
 * kosakata peran nyata tiap aplikasi — hasil pemeriksaan langsung ke kelima
 * basis data (lihat KONTEKS.md, bagian 4 & 7).
 *
 * Idempotent: aman dijalankan berulang. Yang menulis data cuma sekali per
 * baris (dicek lebih dulu), KECUALI backfill company_id yang selalu menimpa
 * ulang dari project — supaya tetap konsisten kalau seeder ini dijalankan
 * lagi setelah ada karyawan baru.
 */
class AppAccessSeeder extends Seeder
{
    public function run(): void
    {
        $companies = $this->seedCompanies();
        $this->seedApps();
        $this->markHoldingDepartments();
        $this->backfillEmployeeCompany($companies);
    }

    /** 6 unit bisnis — disemai dari daftar `units` yang sudah berjalan di PAM e-Sign. */
    private function seedCompanies(): array
    {
        $daftar = [
            ['kode' => 'EWM', 'nama' => 'E-Walk Mall · Balikpapan'],
            ['kode' => 'PSV', 'nama' => 'Pentacity Mall · Balikpapan'],
            ['kode' => 'MIC', 'nama' => 'Mall Intelligence Center'],
            ['kode' => 'DEV', 'nama' => 'WBL Developer'],
            ['kode' => 'MKT', 'nama' => 'Marketing Grup'],
            ['kode' => 'EST', 'nama' => 'WBL Estate'],
        ];

        $now = date('Y-m-d H:i:s');
        $idByKode = [];
        foreach ($daftar as $c) {
            $ada = $this->db->table('companies')->where('kode', $c['kode'])->get()->getRowArray();
            if ($ada) {
                $idByKode[$c['kode']] = (int) $ada['id'];
                continue;
            }
            $this->db->table('companies')->insert($c + ['aktif' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $idByKode[$c['kode']] = (int) $this->db->insertID();
        }
        return $idByKode;
    }

    /** Katalog 5 aplikasi + kosakata peran nyata tiap aplikasi. */
    private function seedApps(): void
    {
        $now = date('Y-m-d H:i:s');

        $apps = [
            'mic'       => ['nama' => 'Mall Intelligence Center', 'ikon' => 'bi-buildings',
                'peran' => ['admin' => 'Admin', 'manager' => 'Manager', 'operator' => 'Operator',
                            'staff' => 'Staff', 'operasional' => 'Operasional', 'manager_lpss' => 'Manager LPSS']],
            'flowstore' => ['nama' => 'FlowStore', 'ikon' => 'bi-cart-check',
                'peran' => ['superadmin' => 'Superadmin', 'admin' => 'Admin', 'purchasing' => 'Purchasing',
                            'store' => 'Store', 'divisi' => 'Divisi']],
            'esign'     => ['nama' => 'PAM e-Sign', 'ikon' => 'bi-file-earmark-check',
                'peran' => ['admin' => 'Admin', 'user' => 'User']],
            'clara'     => ['nama' => 'Clara', 'ikon' => 'bi-house-door',
                // 'finance' & 'supervisor' ada di role_permissions Clara tapi tidak dipakai
                // siapa pun saat pemeriksaan — sengaja tidak disemai, tambahkan manual
                // lewat layar kalau memang mulai dipakai.
                'peran' => ['superadmin' => 'Superadmin', 'administrasi' => 'Administrasi',
                            'sales' => 'Sales', 'viewer' => 'Viewer']],
            'opsjobs'   => ['nama' => 'OpsJobs', 'ikon' => 'bi-tools',
                'peran' => ['l1_super_admin' => 'L1 · Super Admin', 'l1_admin_org' => 'L1 · Admin Organization',
                            'l2_auditor' => 'L2 · Auditor', 'l3_admin_dept' => 'L3 · Admin Dept',
                            'l3_manager' => 'L3 · Manager', 'l3_supervisor' => 'L3 · Supervisor']],
        ];

        foreach ($apps as $kode => $def) {
            $app = $this->db->table('apps')->where('kode', $kode)->get()->getRowArray();
            if (! $app) {
                $this->db->table('apps')->insert([
                    'kode' => $kode, 'nama' => $def['nama'], 'ikon' => $def['ikon'],
                    'aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $appId = (int) $this->db->insertID();
            } else {
                $appId = (int) $app['id'];
            }

            foreach ($def['peran'] as $kodePeran => $label) {
                $adaPeran = $this->db->table('app_roles')
                    ->where('app_id', $appId)->where('kode', $kodePeran)->get()->getRowArray();
                if ($adaPeran) continue;
                $this->db->table('app_roles')->insert([
                    'app_id' => $appId, 'kode' => $kodePeran, 'label' => $label,
                    'aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * HANYA HR-GA & Legal ditandai 'holding' — satu-satunya yang terverifikasi
     * bersih (ketiga atasannya identik di eWalk & Pentacity, tanpa nama
     * tambahan). 12 departemen lain diuji juga, tapi sinyalnya bercampur
     * dengan konvergensi di puncak struktur (atasan senior yang muncul di
     * banyak departemen sekaligus) — jadi SENGAJA dibiarkan default 'unit'.
     * Ini keputusan HR untuk ditinjau lewat layar Pengaturan Departemen,
     * bukan sesuatu yang boleh ditebak di seeder.
     */
    private function markHoldingDepartments(): void
    {
        $this->db->table('departments')->where('name', 'HR-GA & Legal')->update(['tingkat' => 'holding']);
    }

    /**
     * employees.project (varchar, 2 nilai) -> employees.company_id.
     * "eWalk dan Pentacity" dan baris kosong SENGAJA dilewati — butuh
     * keputusan manual (karyawan itu company_id-nya diisi yang mana), bukan
     * ditebak seeder ini.
     */
    private function backfillEmployeeCompany(array $idByKode): void
    {
        if (isset($idByKode['EWM'])) {
            $this->db->table('employees')->where('project', 'eWalk')->update(['company_id' => $idByKode['EWM']]);
        }
        if (isset($idByKode['PSV'])) {
            $this->db->table('employees')->where('project', 'Pentacity')->update(['company_id' => $idByKode['PSV']]);
        }
    }
}
