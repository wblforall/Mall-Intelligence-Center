<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeCertificateModel extends Model
{
    protected $table         = 'employee_certificates';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'nama_sertifikat', 'jenis', 'bidang', 'level', 'competency_id',
        'nomor_sertifikat', 'penerbit', 'url_verifikasi', 'pembiayaan',
        'tanggal_terbit', 'tanggal_kadaluarsa', 'file_name', 'file_original', 'catatan',
        'status', 'uploaded_by', 'reviewed_by', 'reviewed_at', 'catatan_review',
    ];
    protected $useTimestamps = true;

    /** Jenis sertifikat — dasar pengelompokan saat data diolah. */
    public const JENIS = [
        'kompetensi'  => 'Kompetensi / BNSP',
        'k3'          => 'K3 / Keselamatan Kerja',
        'pelatihan'   => 'Pelatihan / Training',
        'profesi'     => 'Profesi',
        'lisensi'     => 'Lisensi / Izin Operasional',
        'penghargaan' => 'Penghargaan',
        'lainnya'     => 'Lainnya',
    ];

    /**
     * Jenjang sertifikat. KKNI dipakai lembaga sertifikasi resmi (BNSP/LSP),
     * sedangkan pelatihan internal biasanya memakai istilah umum — keduanya
     * disediakan supaya karyawan tak dipaksa mengarang padanan.
     */
    public const LEVEL = [
        'dasar'     => 'Dasar',
        'menengah'  => 'Menengah',
        'lanjutan'  => 'Lanjutan / Ahli',
        'kkni_1'    => 'KKNI Level 1',  'kkni_2' => 'KKNI Level 2',  'kkni_3' => 'KKNI Level 3',
        'kkni_4'    => 'KKNI Level 4',  'kkni_5' => 'KKNI Level 5',  'kkni_6' => 'KKNI Level 6',
        'kkni_7'    => 'KKNI Level 7',  'kkni_8' => 'KKNI Level 8',  'kkni_9' => 'KKNI Level 9',
    ];

    public const PEMBIAYAAN = [
        'perusahaan' => 'Dibiayai Perusahaan',
        'pribadi'    => 'Biaya Pribadi',
    ];

    public static function label(array $peta, ?string $key): string
    {
        return $peta[(string) $key] ?? '';
    }

    public function getByEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('tanggal_kadaluarsa', 'ASC')
            ->findAll();
    }

    /** Sertifikat menunggu verifikasi HR, untuk inbox & Kotak Persetujuan. */
    public function pendingInbox(int $limit = 0): array
    {
        $q = $this->db->table('employee_certificates c')
            ->select('c.*, e.nama AS employee_nama, d.name AS dept_name')
            ->join('employees e', 'e.id = c.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('c.status', 'pending')
            ->orderBy('c.created_at', 'ASC');

        return $limit > 0 ? $q->get($limit)->getResultArray() : $q->get()->getResultArray();
    }

    public static function getCertStatus(?string $kadaluarsa): array
    {
        if (! $kadaluarsa) return ['label' => 'Permanen', 'color' => 'success'];
        $days = (int) ceil((strtotime($kadaluarsa) - time()) / 86400);
        if ($days < 0)  return ['label' => 'Kadaluarsa',         'color' => 'danger'];
        if ($days <= 30) return ['label' => 'Segera Kadaluarsa', 'color' => 'warning'];
        return ['label' => 'Aktif', 'color' => 'success'];
    }
}
