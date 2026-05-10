<?php

namespace App\Models;

use CodeIgniter\Model;

class DivisionModel extends Model
{
    protected $table         = 'divisions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'kode', 'deskripsi'];
    protected $useTimestamps = true;

    // Returns divisions with their department count and departments list
    public function getAllWithDepts(): array
    {
        $divs  = $this->orderBy('nama')->findAll();
        $depts = db_connect()->table('departments d')
            ->select('d.id, d.name, d.division_id')
            ->where('d.division_id IS NOT NULL', null, false)
            ->orderBy('d.name')
            ->get()->getResultArray();

        $deptMap = [];
        foreach ($depts as $d) {
            $deptMap[$d['division_id']][] = $d;
        }

        foreach ($divs as &$div) {
            $div['departments'] = $deptMap[$div['id']] ?? [];
            $div['dept_count']  = count($div['departments']);
        }
        return $divs;
    }
}
