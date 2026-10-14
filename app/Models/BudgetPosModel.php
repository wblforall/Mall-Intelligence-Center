<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetPosModel extends Model
{
    protected $table         = 'budget_pos';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'nama', 'keterangan', 'urutan', 'aktif'];
    protected $useTimestamps = true;

    public function milikDept(int $deptId, bool $hanyaAktif = false): array
    {
        $q = $this->where('dept_id', $deptId);
        if ($hanyaAktif) $q->where('aktif', 1);
        return $q->orderBy('urutan')->orderBy('id')->findAll();
    }

    /** Pos dengan id ini milik departemen ini? — penjaga sebelum menulis rencana/realisasi. */
    public function milik(int $posId, int $deptId): ?array
    {
        return $this->where('id', $posId)->where('dept_id', $deptId)->first();
    }

    /**
     * Jumlah baris rencana & realisasi per pos — pos yang punya data tak boleh dihapus.
     * Event / item Event Content yang memakai pos ini ikut dihitung sebagai realisasi
     * (kalau pos dihapus, biaya event kehilangan pos-nya).
     */
    public function jumlahData(int $deptId): array
    {
        $rows = $this->db->query(
            "SELECT p.id,
                    (SELECT COUNT(*) FROM budget_rencana r WHERE r.pos_id = p.id AND r.jumlah <> 0) AS n_rencana,
                    (SELECT COUNT(*) FROM budget_realisasi x WHERE x.pos_id = p.id)
                  + (SELECT COUNT(*) FROM event_content_items i WHERE i.pos_id = p.id)
                  + (SELECT COUNT(*) FROM events e WHERE e.pos_id = p.id) AS n_realisasi
               FROM budget_pos p WHERE p.dept_id = ?", [$deptId]
        )->getResultArray();
        return array_column($rows, null, 'id');
    }

    /** Pos aktif dept yang terhubung ke modul Events (pilihan pos di event & item Content). */
    public function untukEvent(): array
    {
        $dept = array_keys((new BudgetDeptSettingModel())->deptEvent());
        if (! $dept) return [];
        return $this->select('budget_pos.id, budget_pos.nama, budget_pos.dept_id, d.name AS dept_nama')
            ->join('departments d', 'd.id = budget_pos.dept_id')
            ->whereIn('budget_pos.dept_id', $dept)->where('budget_pos.aktif', 1)
            ->orderBy('d.name')->orderBy('budget_pos.urutan')->orderBy('budget_pos.id')->findAll();
    }
}
