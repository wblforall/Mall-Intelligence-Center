<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table      = 'activity_logs';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    public function getFiltered(array $filters, int $perPage = 50): array
    {
        $builder = $this->db->table('activity_logs');

        if (!empty($filters['module']))  $builder->where('module', $filters['module']);
        if (!empty($filters['action']))  $builder->where('action', $filters['action']);
        if (!empty($filters['user_id'])) $builder->where('user_id', $filters['user_id']);
        if (!empty($filters['from']))    $builder->where('created_at >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))      $builder->where('created_at <=', $filters['to'] . ' 23:59:59');
        if (!empty($filters['q']))       $builder->like('target_label', $filters['q']);

        $builder->orderBy('created_at', 'DESC');

        $offset = (((int)($filters['page'] ?? 1)) - 1) * $perPage;
        return $builder->limit($perPage, $offset)->get()->getResultArray();
    }

    public function countFiltered(array $filters): int
    {
        $builder = $this->db->table('activity_logs');

        if (!empty($filters['module']))  $builder->where('module', $filters['module']);
        if (!empty($filters['action']))  $builder->where('action', $filters['action']);
        if (!empty($filters['user_id'])) $builder->where('user_id', $filters['user_id']);
        if (!empty($filters['from']))    $builder->where('created_at >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))      $builder->where('created_at <=', $filters['to'] . ' 23:59:59');
        if (!empty($filters['q']))       $builder->like('target_label', $filters['q']);

        return $builder->countAllResults();
    }

    public function getModules(): array
    {
        return $this->db->table('activity_logs')
            ->select('module')->distinct()
            ->orderBy('module')->get()->getResultArray();
    }

    public function getActiveUsers(): array
    {
        return $this->db->table('activity_logs')
            ->select('user_id, user_name')->distinct()
            ->where('user_id IS NOT NULL')
            ->orderBy('user_name')->get()->getResultArray();
    }
}
