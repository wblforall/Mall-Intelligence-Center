<?php

namespace App\Models;

use App\Libraries\ActivityLog;
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
        'employee_id', 'app_id', 'app_role_id', 'company_id', 'aktif', 'catatan',
        'diberikan_oleh', 'diberikan_pada', 'dicabut_oleh', 'dicabut_pada',
    ];
    protected $useTimestamps = false;

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
     */
    public function grant(
        int $employeeId,
        int $appId,
        int $appRoleId,
        ?int $companyId,
        int $olehUserId,
        ?string $catatan = null
    ): void {
        $now = date('Y-m-d H:i:s');

        $existing = $this->where('employee_id', $employeeId)
            ->where('app_id', $appId)->where('aktif', 1)->first();

        $konteks = $this->konteksLog($employeeId, $appId, $appRoleId, $companyId);

        if ($existing) {
            ActivityLog::captureBefore($existing);
            $this->update($existing['id'], [
                'app_role_id' => $appRoleId,
                'company_id'  => $companyId,
                'catatan'     => $catatan,
            ]);
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

    /** Cabut akses aktif seorang karyawan ke satu aplikasi. Baris TIDAK dihapus — cukup ditandai nonaktif. */
    public function revoke(int $employeeId, int $appId, int $olehUserId, ?string $catatan = null): bool
    {
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
        ]);
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
