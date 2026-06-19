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
        'ijazah'  => 'Ijazah',
        'lainnya' => 'Lainnya',
    ];

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
