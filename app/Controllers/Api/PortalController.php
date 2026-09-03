<?php

namespace App\Controllers\Api;

use App\Models\EmployeeAppAccessModel;

/**
 * API untuk WBL One — portal aplikasi yang berdiri di DEPAN, bukan di dalam MIC.
 *
 * MIC di sini berperan sebagai sumber identitas: ia yang tahu siapa orangnya,
 * di departemen dan unit bisnis mana, serta boleh membuka aplikasi apa saja.
 * Portal cuma menampilkannya. MIC sendiri jadi salah satu kartu di portal itu,
 * sederajat dengan FlowStore, Clara, OpsJobs, dan PAM e-Sign.
 *
 * SENGAJA terpisah dari `auth/me` yang dipakai aplikasi mobile: portal
 * menyegarkan daftar aksesnya tiap kali halaman dibuka, dan `me` menghitung
 * seluruh peta hak menu MIC — beban yang tidak dibutuhkan portal. Memisahkan
 * keduanya juga berarti mengubah salah satunya tidak mengganggu konsumen yang
 * lain.
 *
 * Autentikasinya memakai Bearer token yang sudah ada (`POST /api/auth/login`),
 * termasuk seluruh pengamanannya: token dicabut otomatis begitu akun
 * dinonaktifkan atau hak aksesnya berubah — lihat BaseApiController::requireAuth().
 */
class PortalController extends BaseApiController
{
    /**
     * Aplikasi yang boleh dibuka oleh pemegang token ini, dan hanya itu.
     *
     * TIDAK ADA BYPASS ADMIN. Admin MIC belum tentu punya akun di FlowStore
     * atau PAM e-Sign; menawarkan kartu aplikasi yang orangnya tak bisa masuki
     * hanya memindahkan kebingungan ke halaman login aplikasi tujuan. Portal
     * menampilkan apa yang tercatat, bukan apa yang mungkin.
     */
    public function akses()
    {
        if (! $this->requireAuth()) return $this->response;

        $uid = (int) $this->apiUser['id'];

        $emp = $this->db->table('employees')
            ->select('id, nama, jabatan, dept_id, company_id, status')
            ->where('user_id', $uid)->get()->getRowArray();

        // Akun tanpa data karyawan tidak bisa dihitung aksesnya: akses melekat
        // pada KARYAWAN (departemen, unit bisnis), bukan pada akun login. Ini
        // kondisi nyata — sebagian akun MIC memang belum tertaut, dan sebagian
        // lain memang akun sistem yang tak akan pernah punya karyawan.
        // Dijawab dengan sebab yang bisa ditampilkan portal, bukan daftar kosong
        // yang menyesatkan.
        if (! $emp) {
            return $this->success([
                'keadaan'  => 'belum_tertaut',
                'identitas' => null,
                'aplikasi' => [],
            ], 'Akun belum tertaut ke data karyawan.');
        }

        if (($emp['status'] ?? 'aktif') !== 'aktif') {
            return $this->success([
                'keadaan'   => 'tidak_aktif',
                'identitas' => ['nama' => $emp['nama'], 'status' => $emp['status']],
                'aplikasi'  => [],
            ], 'Status kepegawaian tidak aktif.');
        }

        $dept = ! empty($emp['dept_id'])
            ? $this->db->table('departments')->select('name')
                ->where('id', $emp['dept_id'])->get()->getRowArray()
            : null;
        $company = ! empty($emp['company_id'])
            ? $this->db->table('companies')->select('kode, nama')
                ->where('id', $emp['company_id'])->get()->getRowArray()
            : null;

        $akses = (new EmployeeAppAccessModel())->aksesEfektif((int) $emp['id']);

        // Bentuknya dipangkas ke yang benar-benar dipakai portal. `id_lokal`
        // TIDAK dikirim: itu id akun di aplikasi tujuan, urusan internal SSO,
        // dan tidak ada gunanya di peramban.
        $aplikasi = array_map(fn ($a) => [
            'kode'        => $a['app_kode'],
            'nama'        => $a['app_nama'],
            'url'         => $a['url'] ?: null,
            'ikon'        => $a['ikon'] ?: null,
            'peran'       => $a['peran_kode'],
            'peran_label' => $a['peran_label'],
            'sumber'      => $a['sumber'],
        ], $akses);

        return $this->success([
            'keadaan'   => 'aktif',
            'identitas' => [
                'employee_id' => (int) $emp['id'],
                'nama'        => $emp['nama'],
                'jabatan'     => $emp['jabatan'],
                'departemen'  => $dept['name'] ?? null,
                'unit_kode'   => $company['kode'] ?? null,
                'unit_nama'   => $company['nama'] ?? null,
            ],
            'aplikasi' => $aplikasi,
        ]);
    }
}
