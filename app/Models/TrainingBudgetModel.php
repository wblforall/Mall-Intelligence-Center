<?php

namespace App\Models;

use CodeIgniter\Model;

class TrainingBudgetModel extends Model
{
    protected $table         = 'training_budgets';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'tahun', 'anggaran', 'catatan'];
    protected $useTimestamps = true;

    // Returns [dept_id => {id, anggaran, catatan}] for a given year
    public function getMapByYear(int $tahun): array
    {
        $rows = $this->where('tahun', $tahun)->findAll();
        return array_column($rows, null, 'dept_id');
    }

    public function saveBudget(int $deptId, int $tahun, float $anggaran, ?string $catatan): void
    {
        $existing = $this->where('dept_id', $deptId)->where('tahun', $tahun)->first();
        if ($existing) {
            $this->update($existing['id'], ['anggaran' => $anggaran, 'catatan' => $catatan]);
        } else {
            $this->insert(['dept_id' => $deptId, 'tahun' => $tahun, 'anggaran' => $anggaran, 'catatan' => $catatan]);
        }
    }

    // Years that have at least one budget entry or training
    public function getAvailableYears(): array
    {
        $budgetYears   = array_column($this->db->query("SELECT DISTINCT tahun FROM training_budgets ORDER BY tahun DESC")->getResultArray(), 'tahun');
        $trainingYears = array_column($this->db->query("SELECT DISTINCT YEAR(tanggal_mulai) AS tahun FROM training_programs WHERE tanggal_mulai IS NOT NULL ORDER BY tahun DESC")->getResultArray(), 'tahun');
        $all = array_unique(array_merge($budgetYears, $trainingYears, [(int)date('Y')]));
        rsort($all);
        return $all;
    }
}
