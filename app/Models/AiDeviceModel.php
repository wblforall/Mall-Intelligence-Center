<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Perangkat Pemantauan AI — satu baris = satu laptop tim.
 *
 * Token hanya pernah tersimpan sebagai hash (token_hash); nilai aslinya hanya
 * ada di berkas pemasang yang diunduh sekali. Model ini karena itu tidak
 * pernah menulis/membaca token mentah — hanya hash-nya.
 */
class AiDeviceModel extends Model
{
    protected $table         = 'ai_devices';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'employee_id', 'label', 'token_hash', 'aktif',
        'host_terakhir', 'akun_terakhir', 'lapor_at', 'created_by',
        // Enrollment otomatis + blokir akses (migrasi 2026-10-02-000002).
        'machine_id', 'enrolled_at',
        'diblokir', 'alasan_blokir', 'blokir_oleh', 'blokir_at',
        // Enrollment berbasis persetujuan (migrasi 2026-10-02-000003).
        'disetujui_at',
    ];

    // Tabel ini punya created_at & updated_at → biarkan CI4 mengisinya.
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /**
     * Perangkat + karyawan + departemennya, perangkat yang paling baru melapor
     * lebih dulu. Satu karyawan bisa punya lebih dari satu laptop; pemanggil
     * (rekap per karyawan) mengambil baris pertama tiap karyawan sebagai label
     * perangkat aktifnya.
     *
     * Return: [['employee_id','nama','dept','label','lapor_at'], ...]
     */
    public function rekapKaryawan(): array
    {
        return $this->db->table('ai_devices d')
            ->select('d.employee_id, emp.nama AS nama, dept.name AS dept,
                      d.label AS label, d.lapor_at AS lapor_at')
            ->join('employees emp', 'emp.id = d.employee_id', 'left')
            ->join('departments dept', 'dept.id = emp.dept_id', 'left')
            ->orderBy('d.lapor_at IS NULL', 'ASC', false)
            ->orderBy('d.lapor_at', 'DESC')
            ->get()->getResultArray();
    }
}
