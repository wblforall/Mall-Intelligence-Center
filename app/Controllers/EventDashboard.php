<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventConfigModel;
use App\Models\EventBaselineModel;
use App\Models\EventDailyTrackingModel;

class EventDashboard extends BaseController
{
    public function index(int $eventId)
    {
        if (! $this->canViewMenu('dashboard')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);
        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $config    = (new EventConfigModel())->getByEvent($eventId) ?? [];
        $baselines = (new EventBaselineModel())->getByEvent($eventId);
        $rows      = (new EventDailyTrackingModel())->getByEvent($eventId);

        // Build baseline map by day number
        $baselineMap = [];
        foreach ($baselines as $b) {
            $num = (int)str_replace('DAY-', '', $b['day_label']);
            $baselineMap[$num] = $b;
        }

        // Compute aggregated actuals
        $totals = [
            'actual_traffic'      => 0, 'baseline_traffic'    => 0,
            'event_area_visitors' => 0, 'engaged_visitors'    => 0,
            'new_pam_members'     => 0, 'voucher_claims'      => 0,
            'voucher_redemptions' => 0, 'receipt_uploads'     => 0,
            'actual_tenant_sales' => 0, 'baseline_tenant_sales' => 0,
            'sponsor_revenue'     => 0, 'booth_cl_revenue'    => 0,
            'media_revenue'       => 0, 'parking_uplift'      => 0,
        ];

        foreach ($rows as $r) {
            $bl = $baselineMap[$r['day_number']] ?? null;
            $totals['actual_traffic']        += (int)$r['actual_traffic'];
            $totals['baseline_traffic']      += $bl ? (int)$bl['baseline_traffic'] : 0;
            $totals['event_area_visitors']   += (int)$r['event_area_visitors'];
            $totals['engaged_visitors']      += (int)$r['mg_registration'] + (int)$r['photo_game_participants'] + (int)$r['qr_scans'];
            $totals['new_pam_members']       += (int)$r['new_pam_members'];
            $totals['voucher_claims']        += (int)$r['voucher_claims'];
            $totals['voucher_redemptions']   += (int)$r['voucher_redemptions'];
            $totals['receipt_uploads']       += (int)$r['receipt_uploads'];
            $totals['actual_tenant_sales']   += (int)$r['actual_tenant_sales'];
            $totals['baseline_tenant_sales'] += $bl ? (int)$bl['baseline_tenant_sales'] : 0;
            $totals['sponsor_revenue']       += (int)$r['sponsor_revenue'];
            $totals['booth_cl_revenue']      += (int)$r['booth_cl_revenue'];
            $totals['media_revenue']         += (int)$r['media_revenue'];
            $basePark = $bl ? (int)$bl['baseline_parking_revenue'] : 0;
            $totals['parking_uplift']        += max(0, (int)$r['parking_actual'] - $basePark);
        }

        $totalCost    = (int)array_sum([
            $config['royalty_character'] ?? 0, $config['operational_mg'] ?? 0,
            $config['production_decor'] ?? 0, $config['promotion_media'] ?? 0,
            $config['security_cost'] ?? 0, $config['other_cost'] ?? 0,
        ]);
        $totalRevenue = $totals['sponsor_revenue'] + $totals['booth_cl_revenue'] + $totals['media_revenue'] + $totals['parking_uplift'];
        $tenantUplift = $totals['actual_tenant_sales'] - $totals['baseline_tenant_sales'];

        // KPI calculations
        $kpis = [];

        $kpis['traffic_uplift'] = [
            'label'  => 'Traffic Uplift',
            'actual' => $totals['baseline_traffic'] > 0
                ? ($totals['actual_traffic'] - $totals['baseline_traffic']) / $totals['baseline_traffic']
                : 0,
            'target' => (float)($config['target_traffic_uplift'] ?? 0.3),
            'format' => 'pct',
        ];

        $kpis['engagement_rate'] = [
            'label'  => 'Engagement Rate',
            'actual' => $totals['event_area_visitors'] > 0
                ? $totals['engaged_visitors'] / $totals['event_area_visitors']
                : 0,
            'target' => (float)($config['target_engagement_rate'] ?? 0.15),
            'format' => 'pct',
        ];

        $kpis['member_conversion'] = [
            'label'  => 'Member Conversion',
            'actual' => $totals['engaged_visitors'] > 0
                ? $totals['new_pam_members'] / $totals['engaged_visitors']
                : 0,
            'target' => (float)($config['target_member_conversion'] ?? 0.2),
            'format' => 'pct',
        ];

        $kpis['voucher_redemption'] = [
            'label'  => 'Voucher Redemption Rate',
            'actual' => $totals['voucher_claims'] > 0
                ? $totals['voucher_redemptions'] / $totals['voucher_claims']
                : 0,
            'target' => (float)($config['target_voucher_redemption'] ?? 0.35),
            'format' => 'pct',
        ];

        $kpis['receipt_conversion'] = [
            'label'  => 'Receipt Conversion',
            'actual' => $totals['engaged_visitors'] > 0
                ? $totals['receipt_uploads'] / $totals['engaged_visitors']
                : 0,
            'target' => (float)($config['target_transaction_conv'] ?? 0.1),
            'format' => 'pct',
        ];

        $kpis['sales_uplift'] = [
            'label'  => 'Tenant Sales Uplift',
            'actual' => $totals['baseline_tenant_sales'] > 0
                ? ($totals['actual_tenant_sales'] - $totals['baseline_tenant_sales']) / $totals['baseline_tenant_sales']
                : 0,
            'target' => (float)($config['target_sales_uplift'] ?? 0.25),
            'format' => 'pct',
        ];

        $kpis['direct_roi'] = [
            'label'  => 'Direct ROI',
            'actual' => $totalCost > 0 ? $totalRevenue / $totalCost : 0,
            'target' => (float)($config['target_roi_direct'] ?? 1.0),
            'format' => 'multiplier',
        ];

        $kpis['event_impact_ratio'] = [
            'label'  => 'Event Impact Ratio',
            'actual' => $totalCost > 0 ? ($totalRevenue + max(0, $tenantUplift)) / $totalCost : 0,
            'target' => 1.0,
            'format' => 'multiplier',
        ];

        // Daily chart data
        $chartLabels  = array_column($rows, 'tracking_date');
        $chartTraffic = array_map(fn($r) => (int)$r['actual_traffic'], $rows);
        $chartRevenue = array_map(fn($r) => (int)$r['sponsor_revenue'] + (int)$r['booth_cl_revenue'] + (int)$r['media_revenue'], $rows);

        return view('dashboard/event', [
            'user'         => $user,
            'event'        => $event,
            'config'       => $config,
            'totals'       => $totals,
            'kpis'         => $kpis,
            'totalCost'    => $totalCost,
            'totalRevenue' => $totalRevenue,
            'tenantUplift' => $tenantUplift,
            'chartLabels'  => $chartLabels,
            'chartTraffic' => $chartTraffic,
            'chartRevenue' => $chartRevenue,
            'dayCount'     => count($rows),
        ]);
    }
}
