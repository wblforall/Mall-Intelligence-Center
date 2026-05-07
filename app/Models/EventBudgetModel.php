<?php

namespace App\Models;

use CodeIgniter\Model;

class EventBudgetModel extends Model
{
    protected $table         = 'event_budgets';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'department_id', 'kategori', 'keterangan', 'jumlah', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->db->table('event_budgets eb')
            ->select('eb.*, d.name AS dept_name')
            ->join('departments d', 'd.id = eb.department_id', 'left')
            ->where('eb.event_id', $eventId)
            ->orderBy('d.name')->orderBy('eb.kategori')
            ->get()->getResultArray();
    }

    public function getByEventAndDept(int $eventId, int $deptId): array
    {
        return $this->where('event_id', $eventId)->where('department_id', $deptId)->findAll();
    }

    public function getTotalByDept(int $eventId): array
    {
        return $this->db->table('event_budgets eb')
            ->select('d.name AS dept_name, SUM(eb.jumlah) AS total')
            ->join('departments d', 'd.id = eb.department_id', 'left')
            ->where('eb.event_id', $eventId)
            ->groupBy('eb.department_id')
            ->orderBy('d.name')
            ->get()->getResultArray();
    }
}
