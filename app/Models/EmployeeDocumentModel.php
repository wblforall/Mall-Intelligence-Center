<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeDocumentModel extends Model
{
    protected $table         = 'employee_documents';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'jenis', 'nama_dokumen', 'file_name', 'file_asli',
        'status', 'uploaded_by', 'reviewed_by', 'reviewed_at', 'catatan',
    ];
    protected $useTimestamps = true;

    public const JENIS = [
        'ktp'     => 'KTP',
        'npwp'    => 'NPWP',
        'kk'      => 'Kartu Keluarga',
        'ijazah'    => 'Ijazah',
        'transkrip' => 'Transkrip Nilai',
        'lainnya'   => 'Lainnya',
    ];

    /**
     * Dokumen yang WAJIB dimiliki setiap karyawan.
     *
     * "Wajib" di sini berarti dipantau dan ditagih, BUKAN memblokir akses:
     * saat aturan ini dibuat belum ada satu pun dokumen terunggah dari 202
     * karyawan, sehingga penegakan keras akan mengunci semua orang sekaligus.
     * Kelengkapannya tampil sebagai daftar periksa di /profile dan rekap di
     * sisi HR.
     */
    public const WAJIB = ['ktp', 'kk', 'npwp'];

    /**
     * Status kelengkapan dokumen wajib satu karyawan.
     *
     * Hanya dokumen berstatus `approved` yang dihitung lengkap — dokumen yang
     * masih menunggu verifikasi ditandai tersendiri supaya karyawan tidak
     * mengira urusannya sudah selesai padahal HR belum memeriksa.
     *
     * @return array{lengkap:bool, terverifikasi:int, total:int, per_jenis:array}
     */
    public function kelengkapanWajib(int $employeeId): array
    {
        $rows = $this->where('employee_id', $employeeId)
            ->whereIn('jenis', self::WAJIB)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $perJenis = [];
        foreach (self::WAJIB as $j) {
            $doc = null;
            foreach ($rows as $r) {
                // Baris terbaru per jenis menang; 'approved' mengalahkan
                // 'rejected' yang lebih baru agar dokumen yang sudah sah tidak
                // dianggap hilang hanya karena ada unggahan gagal sesudahnya.
                if ($r['jenis'] !== $j) continue;
                if ($doc === null || ($r['status'] === 'approved' && $doc['status'] !== 'approved')) $doc = $r;
            }
            $perJenis[$j] = [
                'label'  => self::JENIS[$j],
                'status' => $doc['status'] ?? null,   // null = belum diunggah
                'id'     => $doc['id'] ?? null,
            ];
        }

        $ok = count(array_filter($perJenis, static fn ($d) => $d['status'] === 'approved'));

        return [
            'lengkap'       => $ok === count(self::WAJIB),
            'terverifikasi' => $ok,
            'total'         => count(self::WAJIB),
            'per_jenis'     => $perJenis,
        ];
    }

    public static function jenisLabel(string $jenis, ?string $nama = null): string
    {
        if ($jenis === 'lainnya' && $nama) return $nama;
        return self::JENIS[$jenis] ?? ucfirst($jenis);
    }

    public function forEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)->orderBy('created_at', 'DESC')->findAll();
    }

    // Inbox HR: dokumen menunggu verifikasi, join nama karyawan
    public function pendingInbox(): array
    {
        return $this->db->table('employee_documents dc')
            ->select('dc.*, e.nama AS employee_nama, d.name AS dept_name')
            ->join('employees e', 'e.id = dc.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('dc.status', 'pending')
            ->orderBy('dc.created_at', 'ASC')
            ->get()->getResultArray();
    }

    public function countPending(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }
}
