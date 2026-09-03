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
        $this->seedCompanyMappings($companies);
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
            // `url` dipakai WBL One sebagai tujuan kartu aplikasi. Domainnya
            // mengikuti KONTEKS.md §1. FlowStore SENGAJA null: subdomain
            // `flowstore.wbl-bsb.com` belum dibuat, dan menebaknya di sini
            // akan menghasilkan kartu yang tampak siap tapi mengarah ke
            // alamat mati. Portal menampilkan "Alamat belum diatur" untuk
            // yang null — keadaan yang jujur.
            // Deskripsi ditulis dari sudut pandang orang yang MEMAKAI, bukan
            // dari daftar modul. Yang perlu ia tahu: kalau saya ke sini, saya
            // sedang mengerjakan apa.
            'mic'       => ['nama' => 'Mall Intelligence Center', 'ikon' => 'bi-buildings',
                'url' => 'https://mic.wbl-bsb.com',
                'deskripsi' => 'Data mal dan kepegawaian — traffic, parkir, karyawan, legal, dan laporan.',
                'peran' => ['admin' => 'Admin', 'manager' => 'Manager', 'operator' => 'Operator',
                            'staff' => 'Staff', 'operasional' => 'Operasional', 'manager_lpss' => 'Manager LPSS']],
            'flowstore' => ['nama' => 'FlowStore', 'ikon' => 'bi-cart-check',
                'url' => null,
                'deskripsi' => 'Permintaan barang dan pengadaan — MR, PR, dan barang usulan.',
                'peran' => ['superadmin' => 'Superadmin', 'admin' => 'Admin', 'purchasing' => 'Purchasing',
                            'store' => 'Store', 'divisi' => 'Divisi']],
            'esign'     => ['nama' => 'PAM e-Sign', 'ikon' => 'bi-file-earmark-check',
                'url' => 'https://esign.wbl-bsb.com',
                'deskripsi' => 'Tanda tangan dan paraf dokumen secara digital.',
                // `unit_admin` BUKAN nilai kolom `users.role` di PAM e-Sign — ia kolom
                // boolean tersendiri. Disemai sebagai peran di sini karena INILAH
                // dimensi wewenang yang sungguhan: `role` isinya 60 `user` + 1 `admin`
                // (praktis tak membedakan apa pun), sementara `unit_admin` membuka
                // kelola pengguna unit DAN ubah template alur persetujuan.
                'peran' => ['admin' => 'Admin', 'user' => 'User',
                            'unit_admin' => 'Unit Admin']],
            'clara'     => ['nama' => 'Clara', 'ikon' => 'bi-house-door',
                'url' => 'https://clara.wbl-bsb.com',
                // Diambil dari tagline di logo Clara sendiri: "Casual Leasing
                // Achievement & Revenue Analytics" — lebih tepat daripada
                // menebak dari daftar peran.
                'deskripsi' => 'Casual leasing — permintaan kontrak, SKP, dan capaian pendapatan.',
                // 'finance' & 'supervisor' ada di role_permissions Clara tapi tidak dipakai
                // siapa pun saat pemeriksaan — sengaja tidak disemai, tambahkan manual
                // lewat layar kalau memang mulai dipakai.
                'peran' => ['superadmin' => 'Superadmin', 'administrasi' => 'Administrasi',
                            'sales' => 'Sales', 'viewer' => 'Viewer']],
            'opsjobs'   => ['nama' => 'OpsJobs', 'ikon' => 'bi-tools',
                'url' => 'https://opsjobs.id',
                'deskripsi' => 'Pekerjaan lapangan — relokasi tenant dan pembacaan meter utilitas.',
                'peran' => ['l1_super_admin' => 'L1 · Super Admin', 'l1_admin_org' => 'L1 · Admin Organization',
                            'l2_auditor' => 'L2 · Auditor', 'l3_admin_dept' => 'L3 · Admin Dept',
                            'l3_manager' => 'L3 · Manager', 'l3_supervisor' => 'L3 · Supervisor']],
        ];

        foreach ($apps as $kode => $def) {
            $app = $this->db->table('apps')->where('kode', $kode)->get()->getRowArray();
            if (! $app) {
                $this->db->table('apps')->insert([
                    'kode' => $kode, 'nama' => $def['nama'], 'ikon' => $def['ikon'],
                    'deskripsi' => $def['deskripsi'], 'url' => $def['url'], 'aktif' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $appId = (int) $this->db->insertID();
            } else {
                $appId = (int) $app['id'];

                // Hanya diisi bila masih kosong. Alamat yang sudah diubah admin
                // lewat layar TIDAK ditimpa — seeder ini idempoten dan boleh
                // dijalankan ulang kapan saja, termasuk di produksi.
                if ($def['url'] !== null && ($app['url'] ?? null) === null) {
                    $this->db->table('apps')->where('id', $appId)
                        ->update(['url' => $def['url'], 'updated_at' => $now]);
                }

                if (($app['deskripsi'] ?? null) === null) {
                    $this->db->table('apps')->where('id', $appId)
                        ->update(['deskripsi' => $def['deskripsi'], 'updated_at' => $now]);
                }
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

    /**
     * Pemetaan unit bisnis MIC ke id lokal di tiap aplikasi.
     *
     * PENTING: ID tidak sinkron antar sistem, dan tidak boleh diasumsikan
     * sama. Hasil pemeriksaan langsung 2 Sep 2026:
     *
     *   PAM e-Sign `units`   : EP=1 (eWalk & Pentacity DIGABUNG), PP=2 (PAM Plus)
     *   FlowStore  `skema`   : 1=Pentacity, 2=Ewalk   <- TERBALIK dari MIC
     *   Clara `properties`   : 1=E-Walk, 2=Pentacity
     *
     * Untuk esign, DUA unit MIC menunjuk SATU unit lokal (EP) — itu kenyataan,
     * bukan kesalahan data: PAM e-Sign memang tidak memisah eWalk dari
     * Pentacity. Constraint UNIQUE(company_id, app_id) tetap terpenuhi karena
     * company_id-nya berbeda.
     *
     * Hanya esign yang disemai di sini; FlowStore dan Clara menyusul saat
     * masing-masing benar-benar disambungkan, supaya tidak ada pemetaan yang
     * tercatat sebelum diverifikasi.
     */
    private function seedCompanyMappings(array $idByKode): void
    {
        $esign = $this->db->table('apps')->select('id')->where('kode', 'esign')->get()->getRowArray();
        if (! $esign) return;

        $peta = [
            'EWM' => ['kode_lokal' => 'EP', 'id_lokal' => '1'],
            'PSV' => ['kode_lokal' => 'EP', 'id_lokal' => '1'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($peta as $kode => $lokal) {
            if (! isset($idByKode[$kode])) continue;

            $ada = $this->db->table('company_mappings')
                ->where('company_id', $idByKode[$kode])
                ->where('app_id', (int) $esign['id'])
                ->get()->getRowArray();
            if ($ada) continue;

            $this->db->table('company_mappings')->insert([
                'company_id' => $idByKode[$kode],
                'app_id'     => (int) $esign['id'],
                'kode_lokal' => $lokal['kode_lokal'],
                'id_lokal'   => $lokal['id_lokal'],
                'created_at' => $now,
            ]);
        }
    }
}
