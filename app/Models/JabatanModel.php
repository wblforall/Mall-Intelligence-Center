<?php

namespace App\Models;

use CodeIgniter\Model;

class JabatanModel extends Model
{
    protected $table         = 'jabatans';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'grade', 'dept_id', 'division_id', 'parent_jabatan_id'];
    protected $useTimestamps = true;

    // All jabatans with dept+division+parent names, grouped for display
    public function getAllWithContext(): array
    {
        return db_connect()->table('jabatans j')
            ->select('j.*, d.name AS dept_name, dv.nama AS division_nama, pj.nama AS parent_nama')
            ->join('departments d',  'd.id = j.dept_id', 'left')
            ->join('divisions dv',   'dv.id = COALESCE(j.division_id, d.division_id)', 'left')
            ->join('jabatans pj',    'pj.id = j.parent_jabatan_id', 'left')
            ->orderBy('j.division_id IS NULL AND j.dept_id IS NULL', 'ASC', false)
            ->orderBy('dv.nama')
            ->orderBy('d.name')
            ->orderBy('j.grade')
            ->orderBy('j.nama')
            ->get()->getResultArray();
    }

    // Returns jabatans for a specific dept + division-level jabatans for that dept's division
    public function getByDept(int $deptId): array
    {
        return db_connect()->query("
            SELECT j.* FROM jabatans j
            WHERE j.dept_id = ?
            UNION
            SELECT j.* FROM jabatans j
            JOIN departments d ON d.id = ?
            WHERE j.dept_id IS NULL AND j.division_id = d.division_id
            ORDER BY grade, nama
        ", [$deptId, $deptId])->getResultArray();
    }

    // Returns [dept_id => [[id, nama, grade], ...]] for JS pre-loading in employee form
    public function getAllAsMap(): array
    {
        $rows = $this->orderBy('grade')->orderBy('nama')->findAll();
        $map  = ['_div' => []]; // division-level jabatans keyed by division_id
        foreach ($rows as $r) {
            if ($r['dept_id']) {
                $map[$r['dept_id']][] = ['id' => $r['id'], 'nama' => $r['nama'], 'grade' => $r['grade']];
            } elseif ($r['division_id']) {
                $map['_div'][$r['division_id']][] = ['id' => $r['id'], 'nama' => $r['nama'], 'grade' => $r['grade']];
            }
        }
        return $map;
    }
}
