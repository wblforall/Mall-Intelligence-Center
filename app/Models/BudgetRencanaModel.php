<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetRencanaModel extends Model
{
    protected $table         = 'budget_rencana';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['pos_id', 'tahun', 'bulan', 'mall', 'keterangan', 'urutan', 'jumlah', 'updated_at'];

    /** Baris rincian (satu sel bisa beberapa baris) — urut sesuai isian. */
    public function milikDept(int $deptId, int $tahun): array
    {
        return $this->db->table('budget_rencana r')
            ->select('r.pos_id, r.bulan, r.mall, r.keterangan, r.urutan, r.jumlah')
            ->join('budget_pos p', 'p.id = r.pos_id')
            ->where('p.dept_id', $deptId)->where('r.tahun', $tahun)
            ->orderBy('r.urutan')->orderBy('r.id')
            ->get()->getResultArray();
    }

    /** Tahun yang punya rencana atau realisasi untuk departemen ini, plus tahun berjalan & depan — tak kurang dari $mulai. */
    public function tahunTersedia(int $deptId, int $mulai): array
    {
        $a = $this->db->query(
            "SELECT DISTINCT r.tahun FROM budget_rencana r JOIN budget_pos p ON p.id = r.pos_id WHERE p.dept_id = ?
             UNION SELECT DISTINCT YEAR(tanggal) FROM budget_realisasi WHERE dept_id = ?", [$deptId, $deptId]
        )->getResultArray();
        $th = array_map('intval', array_column($a, 'tahun'));
        $th[] = (int) date('Y');
        $th[] = (int) date('Y') + 1;
        $th = array_unique(array_filter($th, fn ($t) => $t >= $mulai));
        if (! $th) $th = [$mulai];
        rsort($th);
        return $th;
    }
}
