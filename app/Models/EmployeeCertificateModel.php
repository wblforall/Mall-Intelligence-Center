<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeCertificateModel extends Model
{
    protected $table         = 'employee_certificates';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'nama_sertifikat', 'nomor_sertifikat', 'penerbit',
        'tanggal_terbit', 'tanggal_kadaluarsa', 'file_name', 'file_original', 'catatan',
    ];
    protected $useTimestamps = true;

    public function getByEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('tanggal_kadaluarsa', 'ASC')
            ->findAll();
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
