<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventBudgetModel;
use App\Models\EventExhibitorModel;
use App\Models\EventSponsorModel;
use App\Models\EventLoyaltyModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Models\EventVMModel;
use App\Models\EventVMRealisasiModel;
use App\Models\EventContentItemModel;
use App\Models\EventContentRealisasiModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCompletionModel;
use App\Models\DailyTrafficModel;

class EventCompare extends BaseController
{
    public function index()
    {
        $ids = array_map('intval', array_filter((array)$this->request->getGet('ids')));
        $ids = array_unique(array_slice($ids, 0, 3));

        $eventModel = new EventModel();
        $allEvents  = $eventModel->orderBy('created_at', 'DESC')->findAll();

        $compared = [];
        if (! empty($ids)) {
            foreach ($ids as $id) {
                $kpi = $this->getKPIs($id);
                if ($kpi) $compared[] = $kpi;
            }
        }

        return view('events/compare', [
            'user'      => $this->currentUser(),
            'allEvents' => $allEvents,
            'compared'  => $compared,
            'selectedIds' => $ids,
        ]);
    }

    private function getKPIs(int $eventId): ?array
    {
        $event = (new EventModel())->find($eventId);
        if (! $event) return null;

        $startDate = $event['start_date'];
        $endDate   = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));

        // Budget
        $budgetByDept  = (new EventBudgetModel())->getTotalByDept($eventId);
        $deptBudget    = array_sum(array_column($budgetByDept, 'total'));
        $loyaltyBudget = array_sum(array_column((new EventLoyaltyModel())->getByEvent($eventId), 'budget'));
        $vmBudget      = (new EventVMModel())->getTotalBudget($eventId);
        $contentBudget = (new EventContentItemModel())->getTotalBudget($eventId);
        $creativeBudget = (new EventCreativeItemModel())->getTotalBudget($eventId);
        $totalBudget   = $deptBudget + $loyaltyBudget + $vmBudget + $contentBudget + $creativeBudget;

        // Revenue
        $exhibitorModel   = new EventExhibitorModel();
        $sponsorModel     = new EventSponsorModel();
        $totalDealing     = $exhibitorModel->getTotalDealing($eventId);
        $totalSponsorCash = $sponsorModel->getTotalCash($eventId);
        $totalRevenue     = $totalDealing + $totalSponsorCash;
        $exhibitorCount   = count($exhibitorModel->getByEvent($eventId));
        $sponsors         = $sponsorModel->getByEvent($eventId);
        $sponsorCount     = count($sponsors);

        // Loyalty realisasi
        $programs    = (new EventLoyaltyModel())->getByEvent($eventId);
        $programIds  = array_column($programs, 'id');
        $voucherItems  = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems   = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);
        $allVIds = [];
        foreach ($voucherItems as $items) { foreach ($items as $v) { $allVIds[] = $v['id']; } }
        $allHIds = [];
        foreach ($hadiahItems as $items) { foreach ($items as $h) { $allHIds[] = $h['id']; } }
        $vReal = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($allVIds);
        $hReal = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($allHIds);
        $loyaltyReal = 0;
        foreach ($programs as $prog) {
            $pid = $prog['id'];
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $loyaltyReal += ($vReal[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
            }
            foreach ($hadiahItems[$pid] ?? [] as $hi) {
                $loyaltyReal += ($hReal[$hi['id']]['total'] ?? 0) * (int)$hi['nilai_satuan'];
            }
        }

        // Other realisasi
        $vmRealTotal     = array_sum(array_map(fn($r) => $r['total'] ?? 0,
            (new EventVMRealisasiModel())->getGroupedByEvent($eventId)));
        $contentReal     = (new EventContentRealisasiModel())->getTotalByEvent($eventId);
        $creativeReal    = (new EventCreativeRealisasiModel())->getTotalByEvent($eventId);
        $totalBudgetReal = $loyaltyReal + $vmRealTotal + $contentReal + $creativeReal;

        // Traffic
        $trafficModel = new DailyTrafficModel();
        $ewalk  = $trafficModel->getDailyTotals($startDate, $endDate, 'ewalk');
        $penta  = $trafficModel->getDailyTotals($startDate, $endDate, 'pentacity');
        $totalTrafficEwalk = array_sum(array_column($ewalk, 'total'));
        $totalTrafficPenta = array_sum(array_column($penta, 'total'));
        $totalTraffic      = $totalTrafficEwalk + $totalTrafficPenta;

        // Completions
        $completions = (new EventCompletionModel())->getByEvent($eventId);
        $required    = count(EventCompletionModel::REQUIRED_MODULES);
        $done        = count($completions);

        $profit     = $totalRevenue - $totalBudget;
        $marginPct  = $totalRevenue > 0 ? round($profit / $totalRevenue * 100, 1) : null;
        $budgetUsePct = $totalBudget > 0 ? round($totalBudgetReal / $totalBudget * 100, 1) : null;

        return [
            'event'          => $event,
            'endDate'        => $endDate,
            'totalBudget'    => $totalBudget,
            'totalBudgetReal'=> $totalBudgetReal,
            'budgetUsePct'   => $budgetUsePct,
            'totalRevenue'   => $totalRevenue,
            'totalDealing'   => $totalDealing,
            'totalSponsorCash' => $totalSponsorCash,
            'profit'         => $profit,
            'marginPct'      => $marginPct,
            'exhibitorCount' => $exhibitorCount,
            'sponsorCount'   => $sponsorCount,
            'loyaltyBudget'  => $loyaltyBudget,
            'loyaltyReal'    => $loyaltyReal,
            'vmBudget'       => $vmBudget,
            'vmReal'         => $vmRealTotal,
            'contentBudget'  => $contentBudget,
            'contentReal'    => $contentReal,
            'creativeBudget' => $creativeBudget,
            'creativeReal'   => $creativeReal,
            'totalTraffic'   => $totalTraffic,
            'trafficEwalk'   => $totalTrafficEwalk,
            'trafficPenta'   => $totalTrafficPenta,
            'completions'    => $done,
            'required'       => $required,
        ];
    }
}
