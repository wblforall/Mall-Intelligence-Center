<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table         = 'events';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'tema', 'content', 'mall', 'start_date', 'event_days', 'status', 'created_by'];
    protected $useTimestamps = true;

    protected $afterFind = ['applyAutoStatus'];

    protected function applyAutoStatus(array $data): array
    {
        if (empty($data['data'])) return $data;

        $today = date('Y-m-d');

        if (isset($data['data']['start_date'])) {
            $completed = array_column(
                $this->db->table('event_completions')->where('event_id', $data['data']['id'])->select('module')->get()->getResultArray(),
                'module'
            );
            $data['data']['status'] = $this->calcStatus($data['data'], $today, $completed);
        } else {
            $ids = array_column($data['data'], 'id');
            $compRows = empty($ids) ? [] : $this->db->table('event_completions')->whereIn('event_id', $ids)->select('event_id, module')->get()->getResultArray();
            $compMap  = [];
            foreach ($compRows as $r) {
                $compMap[$r['event_id']][] = $r['module'];
            }
            foreach ($data['data'] as &$row) {
                if (isset($row['start_date'])) {
                    $row['status'] = $this->calcStatus($row, $today, $compMap[$row['id']] ?? []);
                }
            }
        }

        return $data;
    }

    private function calcStatus(array $event, string $today, array $completedModules = []): string
    {
        $start    = $event['start_date'];
        $end      = date('Y-m-d', strtotime($start . ' +' . ($event['event_days'] - 1) . ' days'));
        $required = array_keys(\App\Models\EventCompletionModel::REQUIRED_MODULES);

        if ($today < $start) return 'draft';
        if ($today <= $end)  return 'active';

        foreach ($required as $module) {
            if (! in_array($module, $completedModules)) return 'waiting_data';
        }
        return 'completed';
    }

    public function getEventsForUser(int $userId, string $role): array
    {
        return $this->orderBy('start_date', 'ASC')->findAll();
    }

    public function canUserAccess(int $eventId, int $userId, string $role): bool
    {
        if ($role === 'admin') return true;
        $event = $this->find($eventId);
        return $event !== null;
    }
}
