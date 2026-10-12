<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetRealisasiModel extends Model
{
    protected $table         = 'budget_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'pos_id', 'tanggal', 'periode_asal', 'uraian', 'keterangan',
                                'nilai', 'mall', 'referensi', 'created_by', 'updated_by'];
    protected $useTimestamps = true;

    /** Baris ringkas untuk BudgetRekap::susun() — bulan = bulan pembebanan. */
    public function untukRekap(int $deptId, int $tahun): array
    {
        return $this->db->query(
            "SELECT pos_id, MONTH(tanggal) AS bulan, mall, SUM(nilai) AS nilai
               FROM budget_realisasi WHERE dept_id = ? AND tanggal BETWEEN ? AND ?
              GROUP BY pos_id, MONTH(tanggal), mall",
            [$deptId, "$tahun-01-01", "$tahun-12-31"]
        )->getResultArray();
    }

    public function totalTahun(int $deptId, int $tahun): array
    {
        return $this->db->query(
            "SELECT pos_id, mall, SUM(nilai) AS nilai FROM budget_realisasi
              WHERE dept_id = ? AND tanggal BETWEEN ? AND ? GROUP BY pos_id, mall",
            [$deptId, "$tahun-01-01", "$tahun-12-31"]
        )->getResultArray();
    }

    public function daftar(int $deptId, string $dari, string $sampai, ?int $posId, string $cari): array
    {
        $q = $this->db->table('budget_realisasi x')
            ->select('x.*, p.nama AS pos_nama, u.name AS pembuat')
            ->join('budget_pos p', 'p.id = x.pos_id')
            ->join('users u', 'u.id = x.created_by', 'left')
            ->where('x.dept_id', $deptId)
            ->where('x.tanggal >=', $dari)->where('x.tanggal <=', $sampai);
        if ($posId) $q->where('x.pos_id', $posId);
        if ($cari !== '') {
            $q->groupStart()->like('x.uraian', $cari)->orLike('x.keterangan', $cari)->orLike('x.referensi', $cari)->groupEnd();
        }
        return $q->orderBy('x.tanggal', 'DESC')->orderBy('x.id', 'DESC')->get()->getResultArray();
    }
}
