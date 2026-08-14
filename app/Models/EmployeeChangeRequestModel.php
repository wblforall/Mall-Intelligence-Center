<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeChangeRequestModel extends Model
{
    protected $table         = 'employee_change_requests';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'requested_by', 'field', 'label', 'value_old', 'value_new',
        'status', 'reviewed_by', 'reviewed_at', 'catatan',
    ];
    protected $useTimestamps = true;

    // Field yang boleh diajukan-ubah sendiri oleh karyawan (label untuk tampilan)
    public const EDITABLE = [
        'no_hp'             => 'No. HP',
        'email'             => 'Email',
        'alamat'            => 'Alamat',
        'alamat_non_bpn'    => 'Alamat (Non-BPN)',
        'pendidikan'        => 'Pendidikan Terakhir',
        'institusi'         => 'Nama Sekolah / Perguruan Tinggi',
        'jurusan'           => 'Jurusan / Fakultas',
        'ipk'               => 'IPK',
        'tahun_lulus'       => 'Tahun Lulus',
        'status_pernikahan' => 'Status Pernikahan',
        'agama'             => 'Agama',
        'foto'              => 'Foto Profil',
    ];

    /** Jenjang baku — satu-satunya nilai yang boleh masuk kolom `pendidikan`. */
    public const JENJANG = ['SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

    /**
     * Jenjang yang mengenal IPK. SD s/d SMK memakai nilai rapor/UN, bukan IPK,
     * jadi mengisinya di sana hanya menghasilkan angka yang tak bisa dibandingkan.
     */
    public const JENJANG_BER_IPK = ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

    public static function jenjangPunyaIpk(?string $jenjang): bool
    {
        return in_array(strtoupper(trim((string) $jenjang)), self::JENJANG_BER_IPK, true);
    }

    public function pendingForEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    // Daftar pengajuan untuk inbox HR, join nama karyawan
    public function inbox(string $status = 'pending'): array
    {
        return $this->db->table('employee_change_requests cr')
            ->select('cr.*, e.nama AS employee_nama, e.foto AS employee_foto, d.name AS dept_name')
            ->join('employees e', 'e.id = cr.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('cr.status', $status)
            ->orderBy('cr.created_at', $status === 'pending' ? 'ASC' : 'DESC')
            ->get()->getResultArray();
    }

    public function countPending(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }
}
