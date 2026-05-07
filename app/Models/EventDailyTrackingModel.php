<?php

namespace App\Models;

use CodeIgniter\Model;

class EventDailyTrackingModel extends Model
{
    protected $table      = 'event_daily_tracking';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_id', 'tracking_date', 'day_number', 'day_type',
        'actual_traffic', 'event_area_visitors',
        'mg_registration', 'photo_game_participants', 'qr_scans',
        'new_pam_members', 'voucher_claims', 'voucher_redemptions',
        'receipt_uploads', 'actual_tenant_sales',
        'sponsor_revenue', 'booth_cl_revenue', 'media_revenue', 'parking_actual',
        'notes', 'created_by',
    ];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('tracking_date')->findAll();
    }

    public function getTotals(int $eventId): array
    {
        $rows = $this->getByEvent($eventId);
        $t = [
            'actual_traffic' => 0, 'event_area_visitors' => 0,
            'mg_registration' => 0, 'photo_game_participants' => 0, 'qr_scans' => 0,
            'engaged_visitors' => 0, 'new_pam_members' => 0,
            'voucher_claims' => 0, 'voucher_redemptions' => 0,
            'receipt_uploads' => 0, 'actual_tenant_sales' => 0,
            'sponsor_revenue' => 0, 'booth_cl_revenue' => 0,
            'media_revenue' => 0, 'parking_uplift' => 0,
            'total_direct_revenue' => 0,
            'baseline_traffic' => 0, 'baseline_tenant_sales' => 0, 'baseline_parking' => 0,
        ];

        foreach ($rows as $r) {
            $t['actual_traffic']          += (int)$r['actual_traffic'];
            $t['event_area_visitors']     += (int)$r['event_area_visitors'];
            $t['mg_registration']         += (int)$r['mg_registration'];
            $t['photo_game_participants'] += (int)$r['photo_game_participants'];
            $t['qr_scans']                += (int)$r['qr_scans'];
            $engaged = (int)$r['mg_registration'] + (int)$r['photo_game_participants'] + (int)$r['qr_scans'];
            $t['engaged_visitors']        += $engaged;
            $t['new_pam_members']         += (int)$r['new_pam_members'];
            $t['voucher_claims']          += (int)$r['voucher_claims'];
            $t['voucher_redemptions']     += (int)$r['voucher_redemptions'];
            $t['receipt_uploads']         += (int)$r['receipt_uploads'];
            $t['actual_tenant_sales']     += (int)$r['actual_tenant_sales'];
            $t['sponsor_revenue']         += (int)$r['sponsor_revenue'];
            $t['booth_cl_revenue']        += (int)$r['booth_cl_revenue'];
            $t['media_revenue']           += (int)$r['media_revenue'];
            $t['parking_uplift']          += max(0, (int)$r['parking_actual'] - (int)$r['_baseline_parking'] ?? 0);
        }

        $t['total_direct_revenue'] = $t['sponsor_revenue'] + $t['booth_cl_revenue'] + $t['media_revenue'];

        return $t;
    }
}
