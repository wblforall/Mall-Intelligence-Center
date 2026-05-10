<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetencyModel extends Model
{
    protected $table         = 'competencies';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'kategori', 'deskripsi', 'cluster_id',
                                'level_1', 'level_2', 'level_3', 'level_4', 'level_5'];
    protected $useTimestamps = true;

    // [kategori => [comps]] — used by TNA assess & result views
    public function getAllGrouped(): array
    {
        $rows = db_connect()->table('competencies c')
            ->select('c.*, cl.nama AS cluster_nama')
            ->join('competency_clusters cl', 'cl.id = c.cluster_id', 'left')
            ->orderBy('c.kategori')->orderBy('c.nama')
            ->get()->getResultArray();

        $grouped = ['hard' => [], 'soft' => []];
        foreach ($rows as $r) $grouped[$r['kategori']][] = $r;
        return $grouped;
    }

    public function getAssignedIdsByDept(int $deptId): array
    {
        $rows = db_connect()->table('competency_dept_map')
            ->where('dept_id', $deptId)->get()->getResultArray();
        return array_map('intval', array_column($rows, 'competency_id'));
    }

    public function saveAssignmentsForDept(int $deptId, array $competencyIds): void
    {
        $db = db_connect();
        $db->table('competency_dept_map')->where('dept_id', $deptId)->delete();
        $rows = [];
        foreach ($competencyIds as $cid) {
            $rows[] = ['dept_id' => $deptId, 'competency_id' => (int)$cid];
        }
        if ($rows) $db->table('competency_dept_map')->insertBatch($rows);
    }

    // [kategori => [comps]] filtered to dept assignments only
    public function getAllGroupedForDept(int $deptId): array
    {
        $assignedIds = $this->getAssignedIdsByDept($deptId);
        $all = $this->getAllGrouped();
        return [
            'hard' => array_values(array_filter($all['hard'], fn($c) => in_array((int)$c['id'], $assignedIds))),
            'soft' => array_values(array_filter($all['soft'], fn($c) => in_array((int)$c['id'], $assignedIds))),
        ];
    }

    // cluster-grouped, filtered to dept assignments — for Pemetaan Target view
    public function getAllGroupedByClusterForDept(int $deptId): array
    {
        $assignedIds = $this->getAssignedIdsByDept($deptId);
        $result = [];
        foreach ($this->getAllGroupedByCluster() as $key => $group) {
            $filtered = array_values(array_filter($group['comps'], fn($c) => in_array((int)$c['id'], $assignedIds)));
            if (! empty($filtered)) {
                $result[$key] = array_merge($group, ['comps' => $filtered]);
            }
        }
        return $result;
    }

    public function getAssignedIdsByJabatan(int $jabatanId): array
    {
        $rows = db_connect()->table('competency_jabatan_map')
            ->where('jabatan_id', $jabatanId)->get()->getResultArray();
        return array_map('intval', array_column($rows, 'competency_id'));
    }

    public function saveAssignmentsForJabatan(int $jabatanId, array $competencyIds): void
    {
        $db = db_connect();
        $db->table('competency_jabatan_map')->where('jabatan_id', $jabatanId)->delete();
        $rows = [];
        foreach ($competencyIds as $cid) {
            $rows[] = ['jabatan_id' => $jabatanId, 'competency_id' => (int)$cid];
        }
        if ($rows) $db->table('competency_jabatan_map')->insertBatch($rows);
    }

    // [kategori => [comps]] filtered to jabatan assignments; empty array if none assigned
    public function getAllGroupedForJabatan(int $jabatanId): array
    {
        $assignedIds = $this->getAssignedIdsByJabatan($jabatanId);
        if (empty($assignedIds)) return [];
        $all = $this->getAllGrouped();
        return [
            'hard' => array_values(array_filter($all['hard'], fn($c) => in_array((int)$c['id'], $assignedIds))),
            'soft' => array_values(array_filter($all['soft'], fn($c) => in_array((int)$c['id'], $assignedIds))),
        ];
    }

    // cluster-grouped filtered to jabatan assignments
    public function getAllGroupedByClusterForJabatan(int $jabatanId): array
    {
        $assignedIds = $this->getAssignedIdsByJabatan($jabatanId);
        $result = [];
        foreach ($this->getAllGroupedByCluster() as $key => $group) {
            $filtered = array_values(array_filter($group['comps'], fn($c) => in_array((int)$c['id'], $assignedIds)));
            if (! empty($filtered)) {
                $result[$key] = array_merge($group, ['comps' => $filtered]);
            }
        }
        return $result;
    }

    // [cluster_id|'none' => ['cluster_nama', 'cluster_deskripsi', 'comps'[]]]
    public function getAllGroupedByCluster(): array
    {
        $rows = db_connect()->table('competencies c')
            ->select('c.*, cl.nama AS cluster_nama, cl.deskripsi AS cluster_deskripsi, cl.urutan AS cluster_urutan')
            ->join('competency_clusters cl', 'cl.id = c.cluster_id', 'left')
            ->orderBy('cl.urutan IS NULL', 'ASC', false)
            ->orderBy('cl.urutan')
            ->orderBy('cl.nama')
            ->orderBy('c.kategori')
            ->orderBy('c.nama')
            ->get()->getResultArray();

        $grouped = [];
        foreach ($rows as $r) {
            $key = $r['cluster_id'] ?? 'none';
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'cluster_id'        => $r['cluster_id'],
                    'cluster_nama'      => $r['cluster_nama'] ?? 'Tanpa Cluster',
                    'cluster_deskripsi' => $r['cluster_deskripsi'] ?? null,
                    'comps'             => [],
                ];
            }
            $grouped[$key]['comps'][] = $r;
        }
        return $grouped;
    }
}
