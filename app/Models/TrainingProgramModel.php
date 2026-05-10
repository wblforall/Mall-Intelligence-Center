<?php

namespace App\Models;

use CodeIgniter\Model;

class TrainingProgramModel extends Model
{
    protected $table         = 'training_programs';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nama', 'tipe', 'vendor', 'tanggal_mulai', 'tanggal_selesai',
        'lokasi', 'biaya_per_peserta', 'kuota', 'status', 'deskripsi', 'catatan',
    ];
    protected $useTimestamps = true;

    public function getAllWithStats(?int $tahun = null, ?string $status = null): array
    {
        $builder = $this->db->table('training_programs p')
            ->select('p.*,
                COUNT(DISTINCT tp.employee_id) AS peserta_count,
                SUM(tp.status_kehadiran = "hadir") AS hadir_count,
                SUM(tp.status_kehadiran = "tidak_hadir") AS tidak_hadir_count,
                AVG(CASE WHEN tp.post_test IS NOT NULL THEN tp.post_test END) AS avg_post_test,
                AVG(CASE WHEN tp.pre_test IS NOT NULL THEN tp.pre_test END) AS avg_pre_test')
            ->join('training_participants tp', 'tp.program_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.tanggal_mulai', 'DESC')
            ->orderBy('p.id', 'DESC');

        if ($tahun) $builder->where('YEAR(p.tanggal_mulai)', $tahun);
        if ($status) $builder->where('p.status', $status);

        return $builder->get()->getResultArray();
    }

    // Returns [dept_id => total_realisasi] — biaya_per_peserta × hadir peserta per dept per year
    public function getRealisasiByDeptYear(int $tahun): array
    {
        $rows = $this->db->query("
            SELECT e.dept_id, SUM(p.biaya_per_peserta) AS total_realisasi, COUNT(tp.id) AS peserta_count
            FROM training_participants tp
            JOIN training_programs p ON p.id = tp.program_id
            JOIN employees e ON e.id = tp.employee_id
            WHERE YEAR(p.tanggal_mulai) = ?
              AND tp.status_kehadiran = 'hadir'
              AND p.biaya_per_peserta IS NOT NULL
            GROUP BY e.dept_id
        ", [$tahun])->getResultArray();

        return array_column($rows, null, 'dept_id');
    }

    // Returns programs for a dept in a year (for budget breakdown)
    public function getProgramsByDeptYear(int $deptId, int $tahun): array
    {
        return $this->db->query("
            SELECT p.nama, p.tanggal_mulai, p.biaya_per_peserta, p.tipe,
                   COUNT(tp.id) AS peserta_hadir,
                   SUM(p.biaya_per_peserta) AS total_biaya
            FROM training_participants tp
            JOIN training_programs p ON p.id = tp.program_id
            JOIN employees e ON e.id = tp.employee_id
            WHERE e.dept_id = ?
              AND YEAR(p.tanggal_mulai) = ?
              AND tp.status_kehadiran = 'hadir'
              AND p.biaya_per_peserta IS NOT NULL
            GROUP BY p.id
            ORDER BY p.tanggal_mulai DESC
        ", [$deptId, $tahun])->getResultArray();
    }

    // For employee training history
    public function getByEmployee(int $empId): array
    {
        return $this->db->table('training_programs p')
            ->select('p.nama, p.tipe, p.vendor, p.tanggal_mulai, p.tanggal_selesai, p.status,
                      tp.status_kehadiran, tp.pre_test, tp.post_test')
            ->join('training_participants tp', 'tp.program_id = p.id')
            ->where('tp.employee_id', $empId)
            ->orderBy('p.tanggal_mulai', 'DESC')
            ->get()->getResultArray();
    }
}
