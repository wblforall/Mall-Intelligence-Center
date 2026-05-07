<?php

namespace App\Models;

use CodeIgniter\Model;

class EventTenantModel extends Model
{
    protected $table      = 'event_tenants';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_id', 'name', 'category', 'participating_promo',
        'baseline_sales', 'event_relevance',
    ];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('name')->findAll();
    }
}
