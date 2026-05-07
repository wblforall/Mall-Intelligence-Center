<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventConfigModel;
use App\Models\EventBaselineModel;
use App\Models\EventDailyTrackingModel;

class EventFunnel extends BaseController
{
    public function index(int $eventId)
    {
        if (! $this->canViewMenu('funnel')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);
        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $config = (new EventConfigModel())->getByEvent($eventId) ?? [];
        $rows   = (new EventDailyTrackingModel())->getByEvent($eventId);

        $t = array_fill_keys([
            'actual_traffic', 'event_area_visitors', 'engaged_visitors',
            'new_pam_members', 'voucher_claims', 'voucher_redemptions',
            'receipt_uploads', 'actual_tenant_sales', 'sponsor_revenue',
            'booth_cl_revenue', 'media_revenue', 'parking_uplift',
        ], 0);

        $baselineTraffic = 0;
        $baselines = (new EventBaselineModel())->getByEvent($eventId);
        $baselineMap = [];
        foreach ($baselines as $b) {
            $num = (int)str_replace('DAY-', '', $b['day_label']);
            $baselineMap[$num] = $b;
        }

        foreach ($rows as $r) {
            $bl = $baselineMap[$r['day_number']] ?? null;
            $t['actual_traffic']        += (int)$r['actual_traffic'];
            $t['event_area_visitors']   += (int)$r['event_area_visitors'];
            $t['engaged_visitors']      += (int)$r['mg_registration'] + (int)$r['photo_game_participants'] + (int)$r['qr_scans'];
            $t['new_pam_members']       += (int)$r['new_pam_members'];
            $t['voucher_claims']        += (int)$r['voucher_claims'];
            $t['voucher_redemptions']   += (int)$r['voucher_redemptions'];
            $t['receipt_uploads']       += (int)$r['receipt_uploads'];
            $t['actual_tenant_sales']   += (int)$r['actual_tenant_sales'];
            $t['sponsor_revenue']       += (int)$r['sponsor_revenue'];
            $t['booth_cl_revenue']      += (int)$r['booth_cl_revenue'];
            $t['media_revenue']         += (int)$r['media_revenue'];
            $baselineTraffic            += $bl ? (int)$bl['baseline_traffic'] : 0;
            $basePark = $bl ? (int)$bl['baseline_parking_revenue'] : 0;
            $t['parking_uplift']        += max(0, (int)$r['parking_actual'] - $basePark);
        }

        $totalDirectRevenue = $t['sponsor_revenue'] + $t['booth_cl_revenue'] + $t['media_revenue'] + $t['parking_uplift'];
        $totalCost = (int)array_sum([
            $config['royalty_character'] ?? 0, $config['operational_mg'] ?? 0,
            $config['production_decor'] ?? 0, $config['promotion_media'] ?? 0,
            $config['security_cost'] ?? 0, $config['other_cost'] ?? 0,
        ]);

        $funnel = [
            ['stage' => 'Visit',            'metric' => 'Actual Traffic',         'actual' => $t['actual_traffic'],        'target' => (int)($baselineTraffic * (1 + ($config['target_traffic_uplift'] ?? 0.3)))],
            ['stage' => 'Event Exposure',   'metric' => 'Event Area Visitors',    'actual' => $t['event_area_visitors'],   'target' => 0],
            ['stage' => 'Engagement',       'metric' => 'Engaged Visitors',       'actual' => $t['engaged_visitors'],      'target' => (int)($t['event_area_visitors'] * ($config['target_engagement_rate'] ?? 0.15))],
            ['stage' => 'Loyalty',          'metric' => 'New PAM Plus Members',   'actual' => $t['new_pam_members'],       'target' => (int)($t['engaged_visitors'] * ($config['target_member_conversion'] ?? 0.2))],
            ['stage' => 'Voucher Interest', 'metric' => 'Voucher Claims',         'actual' => $t['voucher_claims'],        'target' => 0],
            ['stage' => 'Voucher Usage',    'metric' => 'Voucher Redemptions',    'actual' => $t['voucher_redemptions'],   'target' => (int)($t['voucher_claims'] * ($config['target_voucher_redemption'] ?? 0.35))],
            ['stage' => 'Transaction',      'metric' => 'Receipt Uploads',        'actual' => $t['receipt_uploads'],       'target' => (int)($t['engaged_visitors'] * ($config['target_transaction_conv'] ?? 0.1))],
            ['stage' => 'Direct Revenue',   'metric' => 'Sponsor+Booth+Media',    'actual' => $totalDirectRevenue,         'target' => (int)($totalCost * ($config['target_sponsor_coverage'] ?? 0.4))],
        ];

        return view('funnel/index', [
            'user'   => $user,
            'event'  => $event,
            'funnel' => $funnel,
            'totals' => $t,
            'config' => $config,
        ]);
    }
}
