<?php

namespace App\Models;

use App\Libraries\ActivityLog;
use App\Libraries\AppSync;
use CodeIgniter\Model;

/**
 * Tambahan/pengecualian akses aplikasi per orang — ADITIF di atas default
 * departemen (department_app_access), pola sama dengan UserMenuModel
 * terhadap DepartmentMenuModel.
 *
 * Bisa diisi HR (lewat profil karyawan) MAUPUN admin sistem (lewat layar
 * Akses Aplikasi) — keduanya lewat grant()/revoke() di sini, jadi jejaknya
 * konsisten siapa pun pengisinya. WAJIB tercatat: setiap panggilan menulis
 * ke `employee_app_access` (untuk tampilan cepat) DAN ke `activity_logs`
 * lewat ActivityLog::write() (untuk jejak audit yang tidak bisa diedit
 * ulang) — dua tempat, satu kejadian.
 */
class EmployeeAppAccessModel extends Model
{
    protected $table         = 'employee_app_access';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'app_id', 'app_role_id', 'company_id', 'id_lokal', 'aktif', 'catatan',
        'diberikan_oleh', 'diberikan_pada', 'dicabut_oleh', 'dicabut_pada',
    ];
    protected $useTimestamps = false;

    /**
     * Akses aplikasi EFEKTIF seorang karyawan — inilah yang dipakai portal.
     *
     * Menggabungkan dua lapis, sama seperti MenuAccess menggabungkan grant
     * departemen dengan grant per-user:
     *
     *   1. Default departemennya (`department_app_access`)
     *   2. Grant/pengecualian per orang (`employee_app_access`, aktif saja)
     *
     * Grant per orang MENIMPA default departemen untuk aplikasi yang sama —
     * itu memang gunanya: menampung orang yang butuh peran berbeda dari
     * timnya.
     *
     * SENGAJA TIDAK ADA BYPASS ADMIN. Admin MIC belum tentu punya akun di
     * FlowStore atau PAM e-Sign; menampilkan kartu aplikasi yang orangnya tak
     * bisa masuki hanya memindahkan kebingungan ke halaman login aplikasi
     * tujuan. Portal menampilkan apa yang tercatat, bukan apa yang mungkin.
     *
     * Default departemen dicocokkan dengan company_id karyawan: baris
     * `company_id IS NULL` berlaku lintas unit (dipakai departemen bertingkat
     * `holding`), baris yang terisi hanya berlaku untuk unit bisnis itu.
     *
     * @return array<int, array<string,mixed>> per aplikasi, sudah digabung
     */
    public function aksesEfektif(int $employeeId): array
    {
        $emp = $this->db->table('employees')
            ->select('dept_id, company_id')
            ->where('id', $employeeId)->get()->getRowArray();

        if (! $emp) return [];

        $hasil = [];

        // ── Lapis 1: default departemen ──
        if (! empty($emp['dept_id'])) {
            $q = $this->db->table('department_app_access d')
                ->select('d.app_id, a.kode AS app_kode, a.nama AS app_nama, a.deskripsi, a.url, a.ikon, a.urutan,
                          r.kode AS peran_kode, r.label AS peran_label')
                ->join('apps a', 'a.id = d.app_id')
                ->join('app_roles r', 'r.id = d.app_role_id')
                ->where('d.department_id', (int) $emp['dept_id'])
                ->where('a.aktif', 1);

            if (! empty($emp['company_id'])) {
                $q->groupStart()
                    ->where('d.company_id', null)
                    ->orWhere('d.company_id', (int) $emp['company_id'])
                  ->groupEnd();
            } else {
                $q->where('d.company_id', null);
            }

            foreach ($q->get()->getResultArray() as $b) {
                $b['sumber'] = 'departemen';
                $hasil[(int) $b['app_id']] = $b;
            }
        }

        // ── Lapis 2: grant per orang (menimpa) ──
        $pribadi = $this->db->table('employee_app_access x')
            ->select('x.app_id, x.id_lokal, a.kode AS app_kode, a.nama AS app_nama, a.deskripsi, a.url, a.ikon, a.urutan,
                      r.kode AS peran_kode, r.label AS peran_label')
            ->join('apps a', 'a.id = x.app_id')
            ->join('app_roles r', 'r.id = x.app_role_id')
            ->where('x.employee_id', $employeeId)
            ->where('x.aktif', 1)
            ->where('a.aktif', 1)
            ->get()->getResultArray();

        foreach ($pribadi as $b) {
            $b['sumber'] = 'perorangan';
            $hasil[(int) $b['app_id']] = $b;
        }

        // Urutan tampil ditentukan `apps.urutan`, bukan nama. Mengurutkan
        // menurut nama memang stabil, tapi urutannya kebetulan: aplikasi yang
        // dibuka tiap hari bisa terdorong ke belakang hanya karena namanya
        // berawalan huruf akhir. Nama tetap jadi pemecah seri supaya susunan
        // kartu tidak berubah-ubah ketika dua aplikasi bernilai sama.
        usort($hasil, function ($a, $b) {
            return [(int) $a['urutan'], $a['app_nama']] <=> [(int) $b['urutan'], $b['app_nama']];
        });

        return $hasil;
    }

    /** Seluruh riwayat (termasuk yang sudah dicabut) untuk satu karyawan — buat ditampilkan di profil. */
    public function riwayatByEmployee(int $employeeId): array
    {
        return $this->db->table('employee_app_access ea')
            ->select('ea.*, a.kode AS app_kode, a.nama AS app_nama, r.label AS peran_label, c.nama AS company_nama')
            ->join('apps a', 'a.id = ea.app_id')
            ->join('app_roles r', 'r.id = ea.app_role_id')
            ->join('companies c', 'c.id = ea.company_id', 'left')
            ->where('ea.employee_id', $employeeId)
            ->orderBy('ea.aktif', 'DESC')->orderBy('ea.diberikan_pada', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Beri atau ubah akses seorang karyawan ke satu aplikasi.
     *
     * Kalau karyawan itu sudah punya grant AKTIF untuk aplikasi yang sama,
     * ini MENGUBAH baris itu (peran/unit berganti, bukan tumpukan baris
     * baru) — supaya satu aplikasi hanya punya satu baris aktif per orang.
     * Kalau belum ada, baris baru dibuat.
     *
     * @param int    $employeeId
     * @param int    $appId
     * @param int    $appRoleId
     * @param ?int   $companyId  null = ikut company_id karyawan sendiri
     * @param int    $olehUserId id user yang melakukan (WAJIB — HR atau admin sistem)
     * @param ?string $catatan
     * @param ?string $idLokal   id akun di aplikasi tujuan. Inilah yang membuat
     *                           tautan jadi fakta tersimpan, bukan hasil
     *                           pencocokan yang dihitung ulang tiap kali:
     *                           setelah tersimpan, email dan nama boleh
     *                           berubah tanpa merusak tautannya. Tanpa ini
     *                           dispatcher resign tidak tahu akun mana yang
     *                           harus dinonaktifkan.
     */
    public function grant(
        int $employeeId,
        int $appId,
        int $appRoleId,
        ?int $companyId,
        int $olehUserId,
        ?string $catatan = null,
        ?string $idLokal = null
    ): void {
        $now = date('Y-m-d H:i:s');

        $existing = $this->where('employee_id', $employeeId)
            ->where('app_id', $appId)->where('aktif', 1)->first();

        $konteks = $this->konteksLog($employeeId, $appId, $appRoleId, $companyId);

        if ($existing) {
            ActivityLog::captureBefore($existing);
            $ubah = [
                'app_role_id' => $appRoleId,
                'company_id'  => $companyId,
                'catatan'     => $catatan,
            ];

            // id_lokal hanya ditulis bila memang dikirim. Pemanggil yang tidak
            // tahu id_lokal (mis. layar HR) tidak boleh menghapus tautan yang
            // sudah susah payah dicocokkan importer.
            if ($idLokal !== null) $ubah['id_lokal'] = $idLokal;

            $this->update($existing['id'], $ubah);
            ActivityLog::captureAfter(['app_role_id' => $appRoleId, 'company_id' => $companyId]);
            ActivityLog::write('update', 'employee_app_access', (string) $existing['id'], $konteks['label'], [
                'karyawan' => $konteks['nama_karyawan'], 'aplikasi' => $konteks['nama_app'],
                'peran_baru' => $konteks['label_peran'], 'unit' => $konteks['nama_company'],
            ]);
            return;
        }

        $this->insert([
            'employee_id'    => $employeeId,
            'app_id'         => $appId,
            'app_role_id'    => $appRoleId,
            'company_id'     => $companyId,
            'id_lokal'       => $idLokal,
            'aktif'          => 1,
            'catatan'        => $catatan,
            'diberikan_oleh' => $olehUserId,
            'diberikan_pada' => $now,
        ]);
        $newId = $this->getInsertID();

        ActivityLog::write('create', 'employee_app_access', (string) $newId, $konteks['label'], [
            'karyawan' => $konteks['nama_karyawan'], 'aplikasi' => $konteks['nama_app'],
            'peran' => $konteks['label_peran'], 'unit' => $konteks['nama_company'],
        ]);
    }

    /**
     * Cabut akses aktif seorang karyawan ke satu aplikasi.
     *
     * Baris TIDAK dihapus — cukup ditandai nonaktif, supaya riwayatnya tetap
     * bisa dibaca.
     *
     * @param bool $cabutDiTujuan Antrekan penonaktifan akun di aplikasi tujuan.
     *
     * Pembedaan ini WAJIB ada, dan sebelumnya tidak: revoke() hanya menandai
     * baris di MIC dan tidak mengantre apa pun, sehingga mencabut akses
     * seseorang meninggalkan akunnya di aplikasi tujuan tetap HIDUP. Yang
     * menutupi bug itu adalah propagasi resign — satu-satunya jalur yang
     * mengantre — sehingga pengujian lewat resign selalu lolos.
     *
     * Tapi tidak semua pencabutan berarti orangnya harus dinonaktifkan:
     *
     *   true  — akses dicabut karena orangnya memang tidak boleh lagi masuk.
     *           Akun di tujuan harus ikut mati.
     *   false — tautannya SALAH dan sedang dibetulkan (lihat layar Penautan
     *           Akun). Menonaktifkan di sini berarti mematikan akun ORANG
     *           LAIN gara-gara kekeliruan pencatatan.
     *
     * Bawaannya `true`: kalau lupa diisi, akibatnya akses tercabut di dua
     * tempat — bukan akses yang tampak tercabut padahal masih terbuka.
     */
    public function revoke(
        int $employeeId,
        int $appId,
        int $olehUserId,
        ?string $catatan = null,
        bool $cabutDiTujuan = true
    ): bool {
        $existing = $this->where('employee_id', $employeeId)
            ->where('app_id', $appId)->where('aktif', 1)->first();
        if (! $existing) return false;

        $now = date('Y-m-d H:i:s');
        $konteks = $this->konteksLog($employeeId, $appId, (int) $existing['app_role_id'], $existing['company_id']);

        $this->update($existing['id'], [
            'aktif'        => 0,
            'catatan'      => $catatan ?: $existing['catatan'],
            'dicabut_oleh' => $olehUserId,
            'dicabut_pada' => $now,
        ]);

        ActivityLog::write('delete', 'employee_app_access', (string) $existing['id'], $konteks['label'], [
            'karyawan' => $konteks['nama_karyawan'], 'aplikasi' => $konteks['nama_app'],
            'peran_dicabut' => $konteks['label_peran'],
            'cabut_di_tujuan' => $cabutDiTujuan,
        ]);

        // Hanya baris yang punya id_lokal bisa diantre: tanpa itu aplikasi
        // tujuan tidak tahu akun mana yang dimaksud. Diantre, TIDAK dikirim
        // langsung — alasannya sama seperti propagasi resign: satu tujuan
        // yang lambat tidak boleh menahan layar yang sedang dipakai orang.
        if ($cabutDiTujuan && ! empty($existing['id_lokal'])) {
            AppSync::antre(
                $appId,
                $employeeId,
                AppSync::AKSI_NONAKTIFKAN,
                (string) $existing['id_lokal'],
                'akses dicabut di MIC'
            );
        }

        return true;
    }

    /** Kumpulan info bacaan-manusia untuk detail log — supaya activity_logs tetap terbaca tanpa join balik. */
    private function konteksLog(int $employeeId, int $appId, int $appRoleId, ?int $companyId): array
    {
        $emp     = $this->db->table('employees')->select('nama')->where('id', $employeeId)->get()->getRowArray();
        $app     = $this->db->table('apps')->select('nama')->where('id', $appId)->get()->getRowArray();
        $peran   = $this->db->table('app_roles')->select('label')->where('id', $appRoleId)->get()->getRowArray();
        $company = $companyId
            ? $this->db->table('companies')->select('nama')->where('id', $companyId)->get()->getRowArray()
            : null;

        $nama = $emp['nama'] ?? "#$employeeId";
        $namaApp = $app['nama'] ?? "#$appId";

        return [
            'nama_karyawan' => $nama,
            'nama_app'      => $namaApp,
            'label_peran'   => $peran['label'] ?? "#$appRoleId",
            'nama_company'  => $company['nama'] ?? 'ikut default karyawan',
            'label'         => "$nama · $namaApp",
        ];
    }
}
