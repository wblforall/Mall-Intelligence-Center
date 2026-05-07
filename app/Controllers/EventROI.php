<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventConfigModel;
use App\Models\EventBaselineModel;
use App\Models\EventDailyTrackingModel;
use App\Models\EventTenantImpactModel;

class EventROI extends BaseController
{
    public function index(int $eventId)
    {
        if (! $this->canViewMenu('roi')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);
        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $config  = (new EventConfigModel())->getByEvent($eventId) ?? [];
        $rows    = (new EventDailyTrackingModel())->getByEvent($eventId);
        $baselines = (new EventBaselineModel())->getByEvent($eventId);
        $baselineMap = [];
        foreach ($baselines as $b) {
            $num = (int)str_replace('DAY-', '', $b['day_label']);
            $baselineMap[$num] = $b;
        }

        $sponsor = $booth = $media = $parking = $tenantActual = $tenantBaseline = 0;

        foreach ($rows as $r) {
            $bl = $baselineMap[$r['day_number']] ?? null;
            $sponsor       += (int)$r['sponsor_revenue'];
            $booth         += (int)$r['booth_cl_revenue'];
            $media         += (int)$r['media_revenue'];
            $tenantActual  += (int)$r['actual_tenant_sales'];
            $tenantBaseline += $bl ? (int)$bl['baseline_tenant_sales'] : 0;
            $basePark = $bl ? (int)$bl['baseline_parking_revenue'] : 0;
            $parking += max(0, (int)$r['parking_actual'] - $basePark);
        }

        $ipOperationalCost  = (int)($config['royalty_character'] ?? 0) + (int)($config['operational_mg'] ?? 0);
        $additionalCost     = (int)($config['production_decor'] ?? 0) + (int)($config['promotion_media'] ?? 0) + (int)($config['security_cost'] ?? 0) + (int)($config['other_cost'] ?? 0);
        $totalCost          = $ipOperationalCost + $additionalCost;
        $totalDirectRevenue = $sponsor + $booth + $media + $parking;
        $directROI          = $totalCost > 0 ? $totalDirectRevenue / $totalCost : 0;
        $netDirectImpact    = $totalDirectRevenue - $totalCost;
        $tenantUplift       = $tenantActual - $tenantBaseline;
        $eventImpactRatio   = $totalCost > 0 ? ($totalDirectRevenue + max(0, $tenantUplift)) / $totalCost : 0;
        $breakEvenDaily     = $event['event_days'] > 0 ? $totalCost / $event['event_days'] : 0;
        $targetRevenue      = $totalCost * (float)($config['target_sponsor_coverage'] ?? 0.4);

        $roi = [
            'ip_operational_cost'   => $ipOperationalCost,
            'additional_cost'       => $additionalCost,
            'total_cost'            => $totalCost,
            'sponsor_revenue'       => $sponsor,
            'booth_revenue'         => $booth,
            'media_revenue'         => $media,
            'parking_uplift'        => $parking,
            'total_direct_revenue'  => $totalDirectRevenue,
            'direct_roi'            => $directROI,
            'target_roi'            => (float)($config['target_roi_direct'] ?? 1.0),
            'net_direct_impact'     => $netDirectImpact,
            'tenant_uplift'         => $tenantUplift,
            'event_impact_ratio'    => $eventImpactRatio,
            'break_even_daily'      => $breakEvenDaily,
            'target_revenue'        => $targetRevenue,
        ];

        $costBreakdown = [
            ['label' => 'Royalty Character',    'amount' => (int)($config['royalty_character'] ?? 0)],
            ['label' => 'Operational M&G',      'amount' => (int)($config['operational_mg'] ?? 0)],
            ['label' => 'Production & Decor',   'amount' => (int)($config['production_decor'] ?? 0)],
            ['label' => 'Promotion & Media',    'amount' => (int)($config['promotion_media'] ?? 0)],
            ['label' => 'Security',             'amount' => (int)($config['security_cost'] ?? 0)],
            ['label' => 'Other Cost',           'amount' => (int)($config['other_cost'] ?? 0)],
        ];

        return view('roi/index', [
            'user'          => $user,
            'event'         => $event,
            'roi'           => $roi,
            'costBreakdown' => $costBreakdown,
            'config'        => $config,
        ]);
    }
}
