<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventBudgetModel;
use App\Models\EventExhibitorModel;
use App\Models\EventSponsorModel;
use App\Models\EventSponsorItemModel;
use App\Models\EventLoyaltyModel;
use App\Models\EventVMModel;
use App\Models\DailyTrafficModel;
use App\Models\DailyVehicleModel;
use App\Models\EventLoyaltyRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventCompletionModel;
use App\Models\EventExhibitorProgramModel;
use App\Models\EventExhibitorTargetModel;
use App\Models\EventContentItemModel;
use App\Models\EventContentRealisasiModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Models\EventCreativeFileModel;
use App\Models\EventVMRealisasiModel;
use App\Models\EventRundownModel;
use App\Models\EventLocationModel;
use App\Services\EventFinanceService;

class EventSummary extends BaseController
{
    public function monthly()
    {
        if (! $this->canViewMenu('summary')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }

        [$year, $month] = explode('-', $bulan);
        $year  = (int)$year;
        $month = (int)$month;

        $prevBulan = date('Y-m', mktime(0, 0, 0, $month - 1, 1, $year));
        $nextBulan = date('Y-m', mktime(0, 0, 0, $month + 1, 1, $year));

        $events = (new EventModel())
            ->where('YEAR(start_date)', $year)
            ->where('MONTH(start_date)', $month)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        // Bulk queries — 9 queries total regardless of event count
        $eventIds      = array_column($events, 'id');
        $budgetTotals  = EventFinanceService::getBulkBudgetTotals($eventIds);
        $revenueTotals = EventFinanceService::getBulkRevenueTotals($eventIds);
        $trafficTotals = EventFinanceService::getBulkTrafficTotals($events);
        $vehicleTotals = EventFinanceService::getBulkVehicleTotals($events);

        $rows = [];
        $totalBudget  = 0;
        $totalRevenue = 0;
        $totalTraffic = 0;

        foreach ($events as $ev) {
            $eid       = (int)$ev['id'];
            $startDate = $ev['start_date'];
            $endDate   = date('Y-m-d', strtotime($startDate . ' +' . ($ev['event_days'] - 1) . ' days'));

            $budget    = $budgetTotals[$eid]  ?? 0;
            $revenue   = $revenueTotals[$eid] ?? 0;
            $traffic   = $trafficTotals[$eid] ?? 0;
            $kendaraan = ($vehicleTotals[$eid]['mobil'] ?? 0) + ($vehicleTotals[$eid]['motor'] ?? 0);

            $rows[] = [
                'event'     => $ev,
                'endDate'   => $endDate,
                'budget'    => $budget,
                'revenue'   => $revenue,
                'profit'    => $revenue - $budget,
                'traffic'   => $traffic,
                'kendaraan' => $kendaraan,
                'mobil'     => $vehicleTotals[$eid]['mobil'] ?? 0,
                'motor'     => $vehicleTotals[$eid]['motor'] ?? 0,
            ];

            $totalBudget  += $budget;
            $totalRevenue += $revenue;
            $totalTraffic += $traffic;
        }

        $trafficModel      = new DailyTrafficModel();
        $vehicleModel      = new DailyVehicleModel();
        $monthStart        = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd          = date('Y-m-t', strtotime($monthStart));
        $monthTrafficEwalk = $trafficModel->getPeriodTotal($monthStart, $monthEnd, 'ewalk');
        $monthTrafficPenta = $trafficModel->getPeriodTotal($monthStart, $monthEnd, 'pentacity');
        $monthVehicle      = $vehicleModel->getPeriodTotals($monthStart, $monthEnd);

        return view('summary/monthly', [
            'user'              => $this->currentUser(),
            'bulan'             => $bulan,
            'year'              => $year,
            'month'             => $month,
            'prevBulan'         => $prevBulan,
            'nextBulan'         => $nextBulan,
            'rows'              => $rows,
            'totalBudget'       => $totalBudget,
            'totalRevenue'      => $totalRevenue,
            'totalProfit'       => $totalRevenue - $totalBudget,
            'totalTraffic'      => $totalTraffic,
            'monthTrafficEwalk' => $monthTrafficEwalk,
            'monthTrafficPenta' => $monthTrafficPenta,
            'monthVehicle'      => $monthVehicle,
        ]);
    }

    public function index(int $eventId)
    {
        if (! $this->canViewMenu('summary')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $event      = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $canApprove = $this->canApproveEvents();
        if (($event['approval_status'] ?? 'approved') !== 'approved' && ! $canApprove) {
            return redirect()->to('/events')->with('error', 'Event belum disetujui.');
        }

        // Date range for traffic/vehicle charts
        $startDate = $event['start_date'];
        $endDate   = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));

        // Budget summary
        $budgetModel    = new EventBudgetModel();
        $budgetByDept   = $budgetModel->getTotalByDept($eventId);
        $allBudgets     = $budgetModel->getByEvent($eventId);
        $deptBudget     = array_sum(array_column($budgetByDept, 'total'));
        $loyaltyBudget  = array_sum(array_column((new EventLoyaltyModel())->getByEvent($eventId), 'budget'));

        // Revenue
        $exhibitorModel = new EventExhibitorModel();
        $sponsorModel   = new EventSponsorModel();
        $exhibitors     = $exhibitorModel->getByEvent($eventId);
        $sponsors       = $sponsorModel->getByEvent($eventId);
        $sponsorIds     = array_column($sponsors, 'id');
        $allSponsorItems = (new EventSponsorItemModel())->getBySponsorIds($sponsorIds);
        $itemsBySponsors = [];
        foreach ($allSponsorItems as $itm) { $itemsBySponsors[$itm['sponsor_id']][] = $itm; }
        $totalDealing         = $exhibitorModel->getTotalDealing($eventId);
        $totalSponsorCash     = $sponsorModel->getTotalCash($eventId);
        $totalSponsorInKind   = $sponsorModel->getTotalInKind($eventId);
        $totalSponsorInKindQty = $sponsorModel->getTotalInKindQty($eventId);
        $totalRevenue      = $totalDealing + $totalSponsorCash;

        // Exhibitor programs
        $exhibitorPrograms   = (new EventExhibitorProgramModel())->getForSummary($eventId);
        $programsByExhibitor = [];
        foreach ($exhibitorPrograms as $p) { $programsByExhibitor[$p['exhibitor_id']][] = $p; }
        $exhibitorsByKat = [];
        foreach ($exhibitors as $ex) { $exhibitorsByKat[$ex['kategori']][] = $ex; }
        ksort($exhibitorsByKat);

        // Exhibition target
        $exhibitorTarget = (new EventExhibitorTargetModel())->getByEvent($eventId) ?? [];
        $tgtExJumlah     = (int)($exhibitorTarget['target_jumlah']        ?? 0);
        $tgtExNilai      = (int)($exhibitorTarget['target_nilai_dealing']  ?? 0);
        $pctExJumlah     = $tgtExJumlah > 0 ? min(100, round(count($exhibitors) / $tgtExJumlah * 100)) : null;
        $pctExNilai      = $tgtExNilai  > 0 ? min(100, round($totalDealing      / $tgtExNilai  * 100)) : null;
        $colorExJumlah   = $pctExJumlah === null ? 'secondary' : ($pctExJumlah >= 100 ? 'success' : ($pctExJumlah >= 60 ? 'primary' : ($pctExJumlah >= 30 ? 'warning' : 'danger')));
        $colorExNilai    = $pctExNilai  === null ? 'secondary' : ($pctExNilai  >= 100 ? 'success' : ($pctExNilai  >= 60 ? 'primary' : ($pctExNilai  >= 30 ? 'warning' : 'danger')));

        // Loyalty & VM summary
        $vmModel   = new EventVMModel();
        $programs  = (new EventLoyaltyModel())->getByEvent($eventId);
        $realisasi = (new EventLoyaltyRealisasiModel())->getGroupedByEvent($eventId);
        $vmItems   = $vmModel->getByEvent($eventId);
        $vmBudget      = $vmModel->getTotalBudget($eventId);
        $contentBudget = (new EventContentItemModel())->getTotalBudget($eventId);
        // totalBudget computed after creative budget is loaded below

        // Budget realisasi — from voucher items (terpakai × nilai) + hadiah items (dibagikan × nilai_satuan)
        $programIds      = array_column($programs, 'id');
        $voucherItems    = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems     = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);
        $allVoucherIds   = [];
        foreach ($voucherItems as $items) { foreach ($items as $item) { $allVoucherIds[] = $item['id']; } }
        $allHadiahIds    = [];
        foreach ($hadiahItems as $items) { foreach ($items as $item) { $allHadiahIds[] = $item['id']; } }
        $voucherRealisasi = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);
        $hadiahRealisasi  = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        $loyaltyBudgetReal = 0;
        foreach ($programs as $prog) {
            $pid = $prog['id'];
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $loyaltyBudgetReal += ($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
            }
            foreach ($hadiahItems[$pid] ?? [] as $hi) {
                $loyaltyBudgetReal += ($hadiahRealisasi[$hi['id']]['total'] ?? 0) * (int)$hi['nilai_satuan'];
            }
        }
        $contentItems           = (new EventContentItemModel())->getByEvent($eventId);
        $contentRealisasiByItem = (new EventContentRealisasiModel())->getGroupedByEvent($eventId);
        $contentRealisasi       = (new EventContentRealisasiModel())->getTotalByEvent($eventId);

        $creativeItemModel    = new EventCreativeItemModel();
        $creativeItems        = $creativeItemModel->getByEvent($eventId);
        $creativeItemIds      = array_column($creativeItems, 'id');
        $creativeRealisasi      = (new EventCreativeRealisasiModel())->getGroupedByItems($creativeItemIds);
        $creativeInsights       = (new EventCreativeInsightModel())->getGroupedByItems($creativeItemIds);
        $creativeBudget         = $creativeItemModel->getTotalBudget($eventId);
        $creativeRealisasiTotal = (new EventCreativeRealisasiModel())->getTotalByEvent($eventId);

        $vmRealisasi     = (new EventVMRealisasiModel())->getGroupedByEvent($eventId);
        $vmRealTotal     = array_sum(array_map(fn($r) => $r['total'] ?? 0, $vmRealisasi));

        $totalBudget     = EventFinanceService::getBudgetTotal($eventId);
        $totalBudgetReal = $loyaltyBudgetReal + $contentRealisasi + $creativeRealisasiTotal + $vmRealTotal;

        // KPI derived values
        $profit          = $totalRevenue - $totalBudget;
        $marginPct       = $totalRevenue > 0 ? round($profit / $totalRevenue * 100, 1) : 0;
        $profitPositive  = $profit >= 0;
        $budgetRealPct   = $totalBudget > 0 ? min(100, round($totalBudgetReal / $totalBudget * 100, 1)) : 0;
        $budgetRealColor = $totalBudgetReal > $totalBudget ? 'danger' : ($budgetRealPct >= 80 ? 'warning' : 'success');

        // Traffic chart data (both malls combined)
        $trafficModel   = new DailyTrafficModel();
        $vehicleModel   = new DailyVehicleModel();
        $trafficEwalk   = $trafficModel->getDailyTotals($startDate, $endDate, 'ewalk');
        $trafficPenta   = $trafficModel->getDailyTotals($startDate, $endDate, 'pentacity');
        $vehicleData    = $vehicleModel->getDailyTotals($startDate, $endDate);

        // Combine traffic by date
        $trafficMap = [];
        foreach ($trafficEwalk as $t) {
            $trafficMap[$t['tanggal']]['ewalk'] = (int)$t['total'];
        }
        foreach ($trafficPenta as $t) {
            $trafficMap[$t['tanggal']]['pentacity'] = (int)$t['total'];
        }

        // Build chart arrays
        $chartDates  = [];
        $chartEwalk  = [];
        $chartPenta  = [];
        $chartMobil  = [];
        $chartMotor  = [];

        // Fill every day of event
        $vehicleMap = [];
        foreach ($vehicleData as $v) {
            $vehicleMap[$v['tanggal']]['mobil'] = (int)$v['total_mobil'];
            $vehicleMap[$v['tanggal']]['motor'] = (int)$v['total_motor'];
        }

        for ($i = 0; $i < $event['event_days']; $i++) {
            $date          = date('Y-m-d', strtotime($startDate . " +{$i} days"));
            $chartDates[]  = date('d/m', strtotime($date));
            $chartEwalk[]  = $trafficMap[$date]['ewalk'] ?? 0;
            $chartPenta[]  = $trafficMap[$date]['pentacity'] ?? 0;
            $chartMobil[]  = $vehicleMap[$date]['mobil'] ?? 0;
            $chartMotor[]  = $vehicleMap[$date]['motor'] ?? 0;
        }

        // Chart totals
        $totalEwalk     = array_sum($chartEwalk);
        $totalPenta     = array_sum($chartPenta);
        $totalTraffic   = $totalEwalk + $totalPenta;
        $totalMobil     = array_sum($chartMobil);
        $totalMotor     = array_sum($chartMotor);
        $totalKendaraan = $totalMobil + $totalMotor;

        // Completion
        $completions     = (new EventCompletionModel())->getByEvent($eventId);
        $requiredModules = EventCompletionModel::REQUIRED_MODULES;
        $allDone         = count($completions) === count($requiredModules);

        return view('summary/index', [
            'user'                  => $this->currentUser(),
            'event'                 => $event,
            'startDate'             => $startDate,
            'endDate'               => $endDate,
            'isAfterEvent'          => date('Y-m-d') > $endDate,
            'requiredModules'       => $requiredModules,
            'allDone'               => $allDone,
            'budgetByDept'          => $budgetByDept,
            'allBudgets'            => $allBudgets,
            'loyaltyBudget'         => $loyaltyBudget,
            'loyaltyBudgetReal'     => $loyaltyBudgetReal,
            'contentRealisasi'      => $contentRealisasi,
            'totalBudgetReal'       => $totalBudgetReal,
            'vmBudget'              => $vmBudget,
            'contentBudget'         => $contentBudget,
            'totalBudget'           => $totalBudget,
            'profit'                => $profit,
            'marginPct'             => $marginPct,
            'profitPositive'        => $profitPositive,
            'budgetRealPct'         => $budgetRealPct,
            'budgetRealColor'       => $budgetRealColor,
            'exhibitors'            => $exhibitors,
            'exhibitorsByKat'       => $exhibitorsByKat,
            'tgtExJumlah'           => $tgtExJumlah,
            'tgtExNilai'            => $tgtExNilai,
            'pctExJumlah'           => $pctExJumlah,
            'pctExNilai'            => $pctExNilai,
            'colorExJumlah'         => $colorExJumlah,
            'colorExNilai'          => $colorExNilai,
            'sponsors'              => $sponsors,
            'itemsBySponsors'       => $itemsBySponsors,
            'totalDealing'          => $totalDealing,
            'totalSponsorCash'      => $totalSponsorCash,
            'totalSponsorInKind'    => $totalSponsorInKind,
            'totalSponsorInKindQty' => $totalSponsorInKindQty,
            'totalRevenue'          => $totalRevenue,
            'exhibitorPrograms'     => $exhibitorPrograms,
            'programsByExhibitor'   => $programsByExhibitor,
            'programs'              => $programs,
            'realisasi'             => $realisasi,
            'voucherItems'          => $voucherItems,
            'voucherRealisasi'      => $voucherRealisasi,
            'hadiahItems'           => $hadiahItems,
            'hadiahRealisasi'       => $hadiahRealisasi,
            'vmItems'               => $vmItems,
            'vmRealisasi'           => $vmRealisasi,
            'vmRealTotal'           => $vmRealTotal,
            'contentItems'          => $contentItems,
            'contentRealisasiByItem'=> $contentRealisasiByItem,
            'creativeItems'         => $creativeItems,
            'creativeRealisasi'     => $creativeRealisasi,
            'creativeInsights'      => $creativeInsights,
            'creativeBudget'        => $creativeBudget,
            'creativeRealisasiTotal'=> $creativeRealisasiTotal,
            'completions'           => $completions,
            'totalEwalk'            => $totalEwalk,
            'totalPenta'            => $totalPenta,
            'totalTraffic'          => $totalTraffic,
            'totalMobil'            => $totalMobil,
            'totalMotor'            => $totalMotor,
            'totalKendaraan'        => $totalKendaraan,
            'chartDates'            => $chartDates,
            'chartEwalk'            => $chartEwalk,
            'chartPenta'            => $chartPenta,
            'chartMobil'            => $chartMobil,
            'chartMotor'            => $chartMotor,
            'eventLocations'        => (new EventLocationModel())->getEventLocations($eventId),
            'canApprove'            => $canApprove,
        ]);
    }

    public function technicalMeeting(int $eventId)
    {
        if (! $this->canViewMenu('summary')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        // Rundown
        $rundownRaw = (new EventRundownModel())->getByEvent($eventId);
        $rundown    = [];
        foreach ($rundownRaw as $r) { $rundown[$r['hari_ke']][] = $r; }

        // VM / Dekorasi
        $vmItems = (new EventVMModel())->getByEvent($eventId);

        // Exhibition
        $exhibitors        = (new EventExhibitorModel())->getByEvent($eventId);
        $exhibitorPrograms = (new EventExhibitorProgramModel())->getForSummary($eventId);
        $progsByExhibitor  = [];
        foreach ($exhibitorPrograms as $p) { $progsByExhibitor[$p['exhibitor_id']][] = $p; }
        $exhibitorsByKat   = [];
        foreach ($exhibitors as $ex) { $exhibitorsByKat[$ex['kategori']][] = $ex; }
        ksort($exhibitorsByKat);

        // Loyalty
        $programs    = (new EventLoyaltyModel())->getByEvent($eventId);
        $programIds  = array_column($programs, 'id');
        $voucherItems = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems  = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);

        // Sponsors
        $sponsors       = (new EventSponsorModel())->getByEvent($eventId);
        $sponsorIds     = array_column($sponsors, 'id');
        $allSponsorItems = (new EventSponsorItemModel())->getBySponsorIds($sponsorIds);
        $itemsBySponsors = [];
        foreach ($allSponsorItems as $itm) { $itemsBySponsors[$itm['sponsor_id']][] = $itm; }

        // Creative & Design
        $creativeItems = (new EventCreativeItemModel())->getByEvent($eventId);
        $byTipe        = [];
        foreach ($creativeItems as $ci) { $byTipe[$ci['tipe']][] = $ci; }

        // Sponsor totals for tfoot
        $totalCash   = array_sum(array_column(array_filter($sponsors, fn($s) => $s['jenis'] === 'cash'), 'nilai'));
        $totalInKind = array_sum(array_map(fn($s) => array_sum(array_column($itemsBySponsors[$s['id']] ?? [], 'qty')), array_filter($sponsors, fn($s) => $s['jenis'] !== 'cash')));

        // Locations
        $eventLocations = (new EventLocationModel())->getEventLocations($eventId);

        return view('summary/technical_meeting', [
            'event'           => $event,
            'rundown'         => $rundown,
            'vmItems'         => $vmItems,
            'exhibitorsByKat' => $exhibitorsByKat,
            'progsByExhibitor'=> $progsByExhibitor,
            'programs'        => $programs,
            'voucherItems'    => $voucherItems,
            'hadiahItems'     => $hadiahItems,
            'sponsors'        => $sponsors,
            'itemsBySponsors' => $itemsBySponsors,
            'creativeItems'   => $creativeItems,
            'byTipe'          => $byTipe,
            'totalCash'       => $totalCash,
            'totalInKind'     => $totalInKind,
            'eventLocations'  => $eventLocations,
        ]);
    }

    public function postEvent(int $eventId)
    {
        if (! $this->canViewMenu('summary')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        // Rundown
        $rundownRaw = (new EventRundownModel())->getByEvent($eventId);
        $rundown    = [];
        foreach ($rundownRaw as $r) { $rundown[$r['hari_ke']][] = $r; }

        // VM
        $vmModel    = new EventVMModel();
        $vmItems    = $vmModel->getByEvent($eventId);
        $vmRealisasi = (new EventVMRealisasiModel())->getGroupedByEvent($eventId);

        // Exhibition
        $exhibitors        = (new EventExhibitorModel())->getByEvent($eventId);
        $exhibitorPrograms = (new EventExhibitorProgramModel())->getForSummary($eventId);
        $progsByExhibitor  = [];
        foreach ($exhibitorPrograms as $p) { $progsByExhibitor[$p['exhibitor_id']][] = $p; }
        $exhibitorsByKat   = [];
        foreach ($exhibitors as $ex) { $exhibitorsByKat[$ex['kategori']][] = $ex; }
        ksort($exhibitorsByKat);

        // Loyalty
        $programs       = (new EventLoyaltyModel())->getByEvent($eventId);
        $programIds     = array_column($programs, 'id');
        $memberRealisasi = (new EventLoyaltyRealisasiModel())->getGroupedByEvent($eventId);
        $voucherItems    = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems     = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);
        $allVoucherIds   = [];
        foreach ($voucherItems as $items) { foreach ($items as $item) { $allVoucherIds[] = $item['id']; } }
        $allHadiahIds    = [];
        foreach ($hadiahItems as $items) { foreach ($items as $item) { $allHadiahIds[] = $item['id']; } }
        $voucherRealisasi = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);
        $hadiahRealisasi  = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        // Sponsors
        $sponsorModel    = new EventSponsorModel();
        $sponsors        = $sponsorModel->getByEvent($eventId);
        $sponsorIds      = array_column($sponsors, 'id');
        $allSponsorItems = (new EventSponsorItemModel())->getBySponsorIds($sponsorIds);
        $itemsBySponsors = [];
        foreach ($allSponsorItems as $itm) { $itemsBySponsors[$itm['sponsor_id']][] = $itm; }

        // Creative
        $creativeItemModel = new EventCreativeItemModel();
        $creativeItems     = $creativeItemModel->getByEvent($eventId);
        $creativeItemIds   = array_column($creativeItems, 'id');
        $creativeFiles     = (new EventCreativeFileModel())->getGroupedByItems($creativeItemIds);
        $creativeRealisasi = (new EventCreativeRealisasiModel())->getGroupedByItems($creativeItemIds);
        $creativeInsights  = (new EventCreativeInsightModel())->getGroupedByItems($creativeItemIds);
        $byTipe            = [];
        foreach ($creativeItems as $ci) { $byTipe[$ci['tipe']][] = $ci; }

        // Content Event
        $contentItems           = (new EventContentItemModel())->getByEvent($eventId);
        $contentRealisasiByItem = (new EventContentRealisasiModel())->getGroupedByEvent($eventId);
        $contentPrograms        = array_filter($contentItems, fn($i) => ($i['tipe'] ?? 'program') === 'program');
        $contentBiaya           = array_filter($contentItems, fn($i) => ($i['tipe'] ?? 'program') === 'biaya');
        $contentRealTotal       = array_sum(array_map(fn($g) => array_sum(array_column($g, 'nilai')), $contentRealisasiByItem));

        // KPI — Budget
        $totalBudget = EventFinanceService::getBudgetTotal($eventId);

        // KPI — Realisasi
        $loyaltyBudgetReal = 0;
        foreach ($programs as $prog) {
            $pid = $prog['id'];
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $loyaltyBudgetReal += ($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
            }
            foreach ($hadiahItems[$pid] ?? [] as $hi) {
                $loyaltyBudgetReal += ($hadiahRealisasi[$hi['id']]['total'] ?? 0) * (int)$hi['nilai_satuan'];
            }
        }
        $contentRealisasiTotal  = array_sum(array_map(fn($g) => array_sum(array_column($g, 'nilai')), $contentRealisasiByItem));
        $creativeRealisasiTotal = (new EventCreativeRealisasiModel())->getTotalByEvent($eventId);
        $totalBudgetReal        = $loyaltyBudgetReal + $contentRealisasiTotal + $creativeRealisasiTotal;

        // KPI — Revenue
        $exhibitorModel   = new EventExhibitorModel();
        $totalDealing     = $exhibitorModel->getTotalDealing($eventId);
        $totalSponsorCash = $sponsorModel->getTotalCash($eventId);
        $totalRevenue     = $totalDealing + $totalSponsorCash;

        // Exhibition target
        $exhibitorTarget = (new EventExhibitorTargetModel())->getByEvent($eventId) ?? [];
        $tgtExJumlah     = (int)($exhibitorTarget['target_jumlah']        ?? 0);
        $tgtExNilai      = (int)($exhibitorTarget['target_nilai_dealing']  ?? 0);
        $pctExJumlah     = $tgtExJumlah > 0 ? min(100, round(count($exhibitors) / $tgtExJumlah * 100)) : null;
        $pctExNilai      = $tgtExNilai  > 0 ? min(100, round($totalDealing      / $tgtExNilai  * 100)) : null;

        // Locations
        $eventLocations = (new EventLocationModel())->getEventLocations($eventId);

        return view('summary/post_event', [
            'event'                  => $event,
            'eventLocations'         => $eventLocations,
            'rundown'                => $rundown,
            'vmItems'                => $vmItems,
            'vmRealisasi'            => $vmRealisasi,
            'exhibitors'             => $exhibitors,
            'exhibitorsByKat'        => $exhibitorsByKat,
            'progsByExhibitor'       => $progsByExhibitor,
            'programs'               => $programs,
            'memberRealisasi'        => $memberRealisasi,
            'voucherItems'           => $voucherItems,
            'voucherRealisasi'       => $voucherRealisasi,
            'hadiahItems'            => $hadiahItems,
            'hadiahRealisasi'        => $hadiahRealisasi,
            'sponsors'               => $sponsors,
            'itemsBySponsors'        => $itemsBySponsors,
            'contentItems'           => $contentItems,
            'contentRealisasiByItem' => $contentRealisasiByItem,
            'contentPrograms'        => $contentPrograms,
            'contentBiaya'           => $contentBiaya,
            'contentRealTotal'       => $contentRealTotal,
            'creativeItems'          => $creativeItems,
            'byTipe'                 => $byTipe,
            'creativeFiles'          => $creativeFiles,
            'creativeRealisasi'      => $creativeRealisasi,
            'creativeInsights'       => $creativeInsights,
            'totalBudget'            => $totalBudget,
            'totalBudgetReal'        => $totalBudgetReal,
            'totalRevenue'           => $totalRevenue,
            'totalDealing'           => $totalDealing,
            'totalSponsorCash'       => $totalSponsorCash,
            'tgtExJumlah'            => $tgtExJumlah,
            'tgtExNilai'             => $tgtExNilai,
            'pctExJumlah'            => $pctExJumlah,
            'pctExNilai'             => $pctExNilai,
        ]);
    }

    public function budget(int $eventId)
    {
        if (! $this->canViewMenu('budget')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $budgetModel    = new EventBudgetModel();
        $budgetByDept   = $budgetModel->getTotalByDept($eventId);
        $allBudgets     = $budgetModel->getByEvent($eventId);
        $loyaltyPrograms = (new EventLoyaltyModel())->getByEvent($eventId);
        $loyaltyBudget  = array_sum(array_column($loyaltyPrograms, 'budget'));
        $vmModel        = new EventVMModel();
        $vmItems        = $vmModel->getByEvent($eventId);
        $vmBudget       = $vmModel->getTotalBudget($eventId);
        $contentModel   = new EventContentItemModel();
        $contentItems   = $contentModel->getByEvent($eventId);
        $contentBudget  = $contentModel->getTotalBudget($eventId);
        $creativeModel  = new EventCreativeItemModel();
        $creativeItems  = $creativeModel->getByEvent($eventId);
        $creativeBudget = $creativeModel->getTotalBudget($eventId);
        $deptBudget     = array_sum(array_column($budgetByDept, 'total'));
        $totalBudget    = $deptBudget + $loyaltyBudget + $vmBudget + $contentBudget + $creativeBudget;

        $revenue        = EventFinanceService::getRevenueTotal($eventId);

        return view('budget/index', [
            'user'            => $this->currentUser(),
            'event'           => $event,
            'budgetByDept'    => $budgetByDept,
            'allBudgets'      => $allBudgets,
            'loyaltyPrograms' => $loyaltyPrograms,
            'loyaltyBudget'   => $loyaltyBudget,
            'vmItems'         => $vmItems,
            'vmBudget'        => $vmBudget,
            'contentItems'    => $contentItems,
            'contentBudget'   => $contentBudget,
            'creativeItems'   => $creativeItems,
            'creativeBudget'  => $creativeBudget,
            'totalBudget'     => $totalBudget,
            'revenue'         => $revenue,
        ]);
    }

}
