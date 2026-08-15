<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table         = 'employees';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nik', 'nik_ktp', 'no_kk', 'no_npwp', 'no_npwp16', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'tanggal_masuk',
        'dept_id', 'division_id', 'jabatan', 'jabatan_id', 'atasan_id',
        'no_hp', 'email', 'email_kerja', 'status', 'status_kontrak', 'tanggal_akhir_kontrak', 'project',
        'pendidikan', 'institusi', 'jurusan', 'ipk', 'tahun_lulus',
        'status_pernikahan', 'agama', 'jabatan_sebelumnya', 'alamat', 'alamat_non_bpn',
        'foto', 'user_id', 'catatan',
    ];
    protected $useTimestamps = true;

    /**
     * Status akun & login tiap karyawan aktif — untuk memantau sejauh mana
     * karyawan sudah benar-benar memakai MIC setelah didaftarkan.
     *
     * Empat keadaan yang dibedakan, karena tindak lanjutnya berbeda:
     *   belum_akun   → IT perlu membuatkan akun
     *   belum_login  → akun sudah ada, kredensialnya mungkin tak sampai
     *   belum_ganti  → sempat masuk tapi berhenti di layar ganti password,
     *                  jadi akunnya MASIH memakai password awal
     *   aktif        → beres
     */
    public function statusLogin(): array
    {
        $rows = $this->db->table('employees e')
            ->select("e.id, e.nama, e.dept_id, e.email AS email_karyawan,
                      d.name AS dept_name,
                      u.id AS user_id, u.email AS email_login, u.is_active,
                      u.last_login_at, u.must_change_password, u.created_at AS akun_dibuat")
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('users u', 'u.id = e.user_id AND u.is_active = 1', 'left')
            ->where('e.status', 'aktif')
            ->orderBy('d.name', 'ASC')
            ->orderBy('e.nama', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['keadaan'] = $r['user_id'] === null            ? 'belum_akun'
                          : ($r['last_login_at'] === null     ? 'belum_login'
                          : ((int) $r['must_change_password'] ? 'belum_ganti' : 'aktif'));
            $r['umur_akun'] = $r['akun_dibuat']
                ? (int) floor((time() - strtotime($r['akun_dibuat'])) / 86400) : null;
        }
        return $rows;
    }

    /** Ringkasan per keadaan + rekap per departemen dari hasil statusLogin(). */
    public static function rekapStatusLogin(array $rows): array
    {
        $kosong = ['belum_akun' => 0, 'belum_login' => 0, 'belum_ganti' => 0, 'aktif' => 0];
        $urut   = $kosong;
        $dept   = [];
        foreach ($rows as $r) {
            $urut[$r['keadaan']]++;
            $nama = $r['dept_name'] ?: '(tanpa departemen)';
            if (! isset($dept[$nama])) $dept[$nama] = ['total' => 0] + $kosong;
            $dept[$nama]['total']++;
            $dept[$nama][$r['keadaan']]++;
        }
        // Departemen paling bermasalah di atas — itu yang perlu ditagih duluan.
        uasort($dept, static fn ($a, $b) =>
            ($b['belum_akun'] + $b['belum_login']) <=> ($a['belum_akun'] + $a['belum_login']));

        return ['total' => count($rows), 'per_keadaan' => $urut, 'per_dept' => $dept];
    }

    public function getWithDept(): array
    {
        return $this->db->table('employees e')
            ->select('e.*, d.name AS dept_name, dv.nama AS division_nama,
                      j.grade AS jabatan_grade, atasan.nama AS atasan_nama')
            ->join('departments d',  'd.id = e.dept_id',      'left')
            ->join('divisions dv',   'dv.id = d.division_id', 'left')
            ->join('jabatans j',     'j.id = e.jabatan_id',   'left')
            ->join('employees atasan','atasan.id = e.atasan_id','left')
            ->orderBy('e.status', 'ASC')
            ->orderBy('dv.nama',  'ASC')
            ->orderBy('d.name',   'ASC')
            ->orderBy('e.nama',   'ASC')
            ->get()->getResultArray();
    }

    public function findWithDept(int $id): ?array
    {
        return $this->db->table('employees e')
            ->select('e.*, d.name AS dept_name, dv.nama AS division_nama,
                      j.grade AS jabatan_grade, atasan.nama AS atasan_nama')
            ->join('departments d',   'd.id = e.dept_id',       'left')
            ->join('divisions dv',    'dv.id = d.division_id',  'left')
            ->join('jabatans j',      'j.id = e.jabatan_id',    'left')
            ->join('employees atasan','atasan.id = e.atasan_id', 'left')
            ->where('e.id', $id)
            ->get()->getRowArray();
    }

    public static function getMasaKerja(string $tanggalMasuk): string
    {
        $start = new \DateTime($tanggalMasuk);
        $diff  = $start->diff(new \DateTime());
        $parts = [];
        if ($diff->y > 0) $parts[] = $diff->y . ' thn';
        if ($diff->m > 0) $parts[] = $diff->m . ' bln';
        return $parts ? implode(' ', $parts) : '< 1 bln';
    }
}
