<?php

namespace App\Models;

use CodeIgniter\Model;

class EventCompletionModel extends Model
{
    protected $table         = 'event_completions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'module', 'completed_by', 'completed_at'];
    protected $useTimestamps = false;

    public const REQUIRED_MODULES = [
        'content'    => 'Content & Rundown',
        'loyalty'    => 'Program Loyalty',
        'vm'         => 'Dekorasi & VM',
        'creative'   => 'Creative & Design',
        'exhibitors' => 'Exhibitor',
        'sponsors'   => 'Sponsor',
    ];

    public function getByEvent(int $eventId): array
    {
        $rows = $this->db->table('event_completions ec')
            ->select('ec.module, ec.completed_at, u.name AS completed_by_name')
            ->join('users u', 'u.id = ec.completed_by', 'left')
            ->where('ec.event_id', $eventId)
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['module']] = $r;
        }
        return $map;
    }

    public function mark(int $eventId, string $module, int $userId): void
    {
        $existing = $this->where('event_id', $eventId)->where('module', $module)->first();
        if ($existing) return;

        $this->insert([
            'event_id'     => $eventId,
            'module'       => $module,
            'completed_by' => $userId,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function unmark(int $eventId, string $module): void
    {
        $this->where('event_id', $eventId)->where('module', $module)->delete();
    }

    public function getCompletedModules(int $eventId): array
    {
        return array_column(
            $this->where('event_id', $eventId)->select('module')->findAll(),
            'module'
        );
    }
}
