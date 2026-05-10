<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table         = 'employees';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nik', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'tanggal_masuk',
        'dept_id', 'jabatan', 'jabatan_id', 'atasan_id',
        'no_hp', 'email', 'status', 'foto', 'user_id', 'catatan',
    ];
    protected $useTimestamps = true;

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
