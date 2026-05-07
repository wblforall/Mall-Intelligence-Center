<?php

namespace App\Models;

use CodeIgniter\Model;

class EventConfigModel extends Model
{
    protected $table      = 'event_configs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_id', 'royalty_character', 'operational_mg', 'production_decor',
        'promotion_media', 'security_cost', 'other_cost',
        'target_traffic_uplift', 'target_engagement_rate', 'target_member_conversion',
        'target_transaction_conv', 'target_voucher_redemption', 'target_sales_uplift',
        'target_sponsor_coverage', 'target_roi_direct', 'target_repeat_visit',
    ];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): ?array
    {
        return $this->where('event_id', $eventId)->first();
    }

    public function getTotalCost(int $eventId): int
    {
        $cfg = $this->getByEvent($eventId);
        if (! $cfg) return 0;
        return (int)($cfg['royalty_character'] + $cfg['operational_mg'] + $cfg['production_decor']
            + $cfg['promotion_media'] + $cfg['security_cost'] + $cfg['other_cost']);
    }
}
