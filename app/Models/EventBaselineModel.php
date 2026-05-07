<?php

namespace App\Models;

use CodeIgniter\Model;

class EventBaselineModel extends Model
{
    protected $table      = 'event_baselines';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_id', 'day_label', 'comparable_period', 'day_type',
        'baseline_traffic', 'baseline_event_area_visitors', 'baseline_transactions',
        'baseline_tenant_sales', 'baseline_parking_revenue',
    ];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('day_label')->findAll();
    }

    public function getByDayLabel(int $eventId, string $dayLabel): ?array
    {
        return $this->where('event_id', $eventId)->where('day_label', $dayLabel)->first();
    }
}
