<?php

namespace App\Controllers;

use App\Models\DailyTrafficModel;
use App\Models\DailyVehicleModel;
use App\Models\EventModel;
use App\Models\TrafficDoorModel;
use App\Libraries\ActivityLog;
use App\Libraries\TrafficExcelParser;
use App\Libraries\TrafficXlsxExporter;

class Traffic extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $month = $this->request->getGet('bulan') ?: date('Y-m');

        $trafficModel = new DailyTrafficModel();

        $ewalkDates  = $trafficModel->getInputtedDates('ewalk',     $month);
        $pentaDates  = $trafficModel->getInputtedDates('pentacity', $month);

        return view('traffic/index', [
            'user'       => $this->currentUser(),
            'ewalkDates' => $ewalkDates,
            'pentaDates' => $pentaDates,
            'canEdit'    => $this->canEditMenu('traffic'),
            'month'      => $month,
        ]);
    }

    public function summary()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        // KPI totals
        $totalEwalk  = $trafficModel->getPeriodTotal($from, $to, 'ewalk');
        $totalPenta  = $trafficModel->getPeriodTotal($from, $to, 'pentacity');
        $vehicles    = $vehicleModel->getPeriodTotals($from, $to);

        // Daily chart data
        $dailyMap   = $trafficModel->getDailyByBothMalls($from, $to);
        $chartDates = [];
        $chartEwalk = [];
        $chartPenta = [];

        $cursor = $from;
        while ($cursor <= $to) {
            $chartDates[] = date('d/m', strtotime($cursor));
            $chartEwalk[] = $dailyMap[$cursor]['ewalk']     ?? 0;
            $chartPenta[] = $dailyMap[$cursor]['pentacity'] ?? 0;
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        // Vehicle daily chart
        $vehicleRows    = $vehicleModel->getDailyTotals($from, $to);
        $chartVMobil    = [];
        $chartVMotor    = [];
        $chartVMobilBox = [];
        $chartVBus      = [];
        $chartVTruck    = [];
        $chartVTaxi     = [];
        $vehicleMap     = array_column($vehicleRows, null, 'tanggal');
        foreach ($chartDates as $i => $label) {
            $date = date('Y-m-d', strtotime($from . " +{$i} days"));
            $chartVMobil[]    = (int)($vehicleMap[$date]['total_mobil']     ?? 0);
            $chartVMotor[]    = (int)($vehicleMap[$date]['total_motor']     ?? 0);
            $chartVMobilBox[] = (int)($vehicleMap[$date]['total_mobil_box'] ?? 0);
            $chartVBus[]      = (int)($vehicleMap[$date]['total_bus']       ?? 0);
            $chartVTruck[]    = (int)($vehicleMap[$date]['total_truck']     ?? 0);
            $chartVTaxi[]     = (int)($vehicleMap[$date]['total_taxi']      ?? 0);
        }

        // By hour per mall
        $hourEwalkRows = $trafficModel->getByHour($from, $to, 'ewalk');
        $hourPentaRows = $trafficModel->getByHour($from, $to, 'pentacity');
        $hourEwalkMap  = array_column($hourEwalkRows, 'total', 'jam');
        $hourPentaMap  = array_column($hourPentaRows, 'total', 'jam');
        $chartHours        = [];
        $chartHourEwalk    = [];
        $chartHourPenta    = [];
        for ($h = 10; $h <= 23; $h++) {
            $chartHours[]     = $h . '-' . ($h + 1);
            $chartHourEwalk[] = (int)($hourEwalkMap[$h] ?? 0);
            $chartHourPenta[] = (int)($hourPentaMap[$h] ?? 0);
        }

        // By door
        $doorEwalk = $trafficModel->getByDoor($from, $to, 'ewalk');
        $doorPenta = $trafficModel->getByDoor($from, $to, 'pentacity');

        // Insights
        $days     = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days'));
        $prevTotal = $trafficModel->getPeriodTotal($prevFrom, $prevTo, 'ewalk')
                   + $trafficModel->getPeriodTotal($prevFrom, $prevTo, 'pentacity');

        $hourCombined = [];
        foreach ($chartHours as $i => $_) {
            $hourCombined[$i] = $chartHourEwalk[$i] + $chartHourPenta[$i];
        }
        $peakHourIdx = ! empty($hourCombined) && max($hourCombined) > 0
            ? array_search(max($hourCombined), $hourCombined) : null;

        $dailyCombined = [];
        foreach ($chartDates as $i => $_) {
            $dailyCombined[$i] = $chartEwalk[$i] + $chartPenta[$i];
        }
        $activeDays  = count(array_filter($dailyCombined, fn($v) => $v > 0));
        $avgDaily    = $activeDays > 0 ? (int) round(($totalEwalk + $totalPenta) / $activeDays) : 0;
        $bestDayIdx  = ! empty($dailyCombined) && max($dailyCombined) > 0
            ? array_search(max($dailyCombined), $dailyCombined) : null;

        $totalVisitor = $totalEwalk + $totalPenta;
        $changePct = null;
        if ($prevTotal > 0) {
            $changePct = round(($totalVisitor - $prevTotal) / $prevTotal * 100, 1);
        }

        // Weekday (Mon–Thu) vs Weekend (Fri–Sun)
        $wdEwalk = $wdPenta = $wdDays = 0;
        $weEwalk = $wePenta = $weDays = 0;
        foreach ($chartDates as $i => $_) {
            $date = date('Y-m-d', strtotime($from . " +{$i} days"));
            $dow  = (int) date('N', strtotime($date));
            $ew   = $chartEwalk[$i];
            $pt   = $chartPenta[$i];
            if ($dow <= 4) {
                $wdEwalk += $ew; $wdPenta += $pt;
                if ($ew + $pt > 0) $wdDays++;
            } else {
                $weEwalk += $ew; $wePenta += $pt;
                if ($ew + $pt > 0) $weDays++;
            }
        }
        $wdTotal = $wdEwalk + $wdPenta;
        $weTotal = $weEwalk + $wePenta;
        $wdAvg   = $wdDays > 0 ? (int) round($wdTotal / $wdDays) : 0;
        $weAvg   = $weDays > 0 ? (int) round($weTotal / $weDays) : 0;

        return view('traffic/summary', [
            'user'          => $this->currentUser(),
            'from'          => $from,
            'to'            => $to,
            'totalEwalk'    => $totalEwalk,
            'totalPenta'    => $totalPenta,
            'totalVisitor'  => $totalEwalk + $totalPenta,
            'totalMobil'    => $vehicles['mobil'],
            'totalMotor'    => $vehicles['motor'],
            'totalMobilBox' => $vehicles['mobil_box'],
            'totalBus'      => $vehicles['bus'],
            'totalTruck'    => $vehicles['truck'],
            'totalTaxi'     => $vehicles['taxi'],
            'chartDates'    => $chartDates,
            'chartEwalk'    => $chartEwalk,
            'chartPenta'    => $chartPenta,
            'chartVMobil'    => $chartVMobil,
            'chartVMotor'    => $chartVMotor,
            'chartVMobilBox' => $chartVMobilBox,
            'chartVBus'      => $chartVBus,
            'chartVTruck'    => $chartVTruck,
            'chartVTaxi'     => $chartVTaxi,
            'chartHours'     => $chartHours,
            'chartHourEwalk' => $chartHourEwalk,
            'chartHourPenta' => $chartHourPenta,
            'doorEwalk'     => $doorEwalk,
            'doorPenta'     => $doorPenta,
            'insightAvgDaily'  => $avgDaily,
            'insightPeakHour'  => $peakHourIdx !== null ? $chartHours[$peakHourIdx] : null,
            'insightPeakVal'   => $peakHourIdx !== null ? $hourCombined[$peakHourIdx] : 0,
            'insightBestDay'   => $bestDayIdx !== null ? $chartDates[$bestDayIdx] : null,
            'insightBestVal'   => $bestDayIdx !== null ? $dailyCombined[$bestDayIdx] : 0,
            'insightChangePct' => $changePct,
            'insightPrevTotal' => $prevTotal,
            'insightPrevFrom'  => $prevFrom,
            'insightPrevTo'    => $prevTo,
            'periodEvents'     => (new EventModel())->getByPeriod($from, $to),
            'wdTotal'          => $wdTotal, 'wdEwalk' => $wdEwalk, 'wdPenta' => $wdPenta, 'wdAvg' => $wdAvg, 'wdDays' => $wdDays,
            'weTotal'          => $weTotal, 'weEwalk' => $weEwalk, 'wePenta' => $wePenta, 'weAvg' => $weAvg, 'weDays' => $weDays,
        ]);
    }

    public function exportSummary()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        $totalEwalk   = $trafficModel->getPeriodTotal($from, $to, 'ewalk');
        $totalPenta   = $trafficModel->getPeriodTotal($from, $to, 'pentacity');
        $vehicles     = $vehicleModel->getPeriodTotals($from, $to);
        $totalVisitor = $totalEwalk + $totalPenta;

        // Daily — iterate tanggal agar baris kosong tetap muncul
        $dailyMap    = $trafficModel->getDailyByBothMalls($from, $to);
        $vehicleRows = $vehicleModel->getDailyTotals($from, $to);
        $vehicleMap  = array_column($vehicleRows, null, 'tanggal');

        $days   = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $ew = (int) ($dailyMap[$cursor]['ewalk']     ?? 0);
            $pt = (int) ($dailyMap[$cursor]['pentacity'] ?? 0);
            $days[] = [
                'date_fmt'  => date('d/m/Y', strtotime($cursor)),
                'ewalk'     => $ew,
                'pentacity' => $pt,
                'total'     => $ew + $pt,
                'mobil'     => (int) ($vehicleMap[$cursor]['total_mobil']     ?? 0),
                'motor'     => (int) ($vehicleMap[$cursor]['total_motor']     ?? 0),
                'mobil_box' => (int) ($vehicleMap[$cursor]['total_mobil_box'] ?? 0),
                'bus'       => (int) ($vehicleMap[$cursor]['total_bus']       ?? 0),
                'truck'     => (int) ($vehicleMap[$cursor]['total_truck']     ?? 0),
                'taxi'      => (int) ($vehicleMap[$cursor]['total_taxi']      ?? 0),
            ];
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        // Hourly
        $hourEwalkMap = array_column($trafficModel->getByHour($from, $to, 'ewalk'),     'total', 'jam');
        $hourPentaMap = array_column($trafficModel->getByHour($from, $to, 'pentacity'), 'total', 'jam');
        $hours = [];
        for ($h = 10; $h <= 23; $h++) {
            $ew = (int) ($hourEwalkMap[$h] ?? 0);
            $pt = (int) ($hourPentaMap[$h] ?? 0);
            $hours[] = ['jam' => sprintf('%02d:00–%02d:00', $h, $h + 1), 'ewalk' => $ew, 'pentacity' => $pt, 'total' => $ew + $pt];
        }

        // Door
        $doorEwalk = $trafficModel->getByDoor($from, $to, 'ewalk');
        $doorPenta = $trafficModel->getByDoor($from, $to, 'pentacity');

        // Insights
        $activeDays  = count(array_filter(array_column($days, 'total'), fn($v) => $v > 0));
        $avgDaily    = $activeDays > 0 ? (int) round($totalVisitor / $activeDays) : 0;
        $peakHourVal = max(array_column($hours, 'total') ?: [0]);
        $peakHour    = $peakHourVal > 0 ? $hours[array_search($peakHourVal, array_column($hours, 'total'))]['jam'] : null;
        $bestDayVal  = max(array_column($days, 'total') ?: [0]);
        $bestDay     = $bestDayVal > 0 ? $days[array_search($bestDayVal, array_column($days, 'total'))]['date_fmt'] : null;

        $filename = 'traffic-summary-' . $from . '-sd-' . $to . '.xls';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($this->buildTrafficExcelHtml([
                'from'         => $from,
                'to'           => $to,
                'totalVisitor' => $totalVisitor,
                'totalEwalk'   => $totalEwalk,
                'totalPenta'   => $totalPenta,
                'vehicles'     => $vehicles,
                'days'         => $days,
                'hours'        => $hours,
                'doorEwalk'    => $doorEwalk,
                'doorPenta'    => $doorPenta,
                'avgDaily'     => $avgDaily,
                'peakHour'     => $peakHour,
                'peakHourVal'  => $peakHourVal,
                'bestDay'      => $bestDay,
                'bestDayVal'   => $bestDayVal,
            ]));
    }

    public function printSummary()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        $totalEwalk   = $trafficModel->getPeriodTotal($from, $to, 'ewalk');
        $totalPenta   = $trafficModel->getPeriodTotal($from, $to, 'pentacity');
        $vehicles     = $vehicleModel->getPeriodTotals($from, $to);
        $totalVisitor = $totalEwalk + $totalPenta;

        $dailyMap    = $trafficModel->getDailyByBothMalls($from, $to);
        $vehicleRows = $vehicleModel->getDailyTotals($from, $to);
        $vehicleMap  = array_column($vehicleRows, null, 'tanggal');

        $days   = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $ew = (int) ($dailyMap[$cursor]['ewalk']     ?? 0);
            $pt = (int) ($dailyMap[$cursor]['pentacity'] ?? 0);
            $days[] = [
                'date_fmt'  => date('d/m/Y', strtotime($cursor)),
                'ewalk'     => $ew,
                'pentacity' => $pt,
                'total'     => $ew + $pt,
                'mobil'     => (int) ($vehicleMap[$cursor]['total_mobil']     ?? 0),
                'motor'     => (int) ($vehicleMap[$cursor]['total_motor']     ?? 0),
                'mobil_box' => (int) ($vehicleMap[$cursor]['total_mobil_box'] ?? 0),
                'bus'       => (int) ($vehicleMap[$cursor]['total_bus']       ?? 0),
                'truck'     => (int) ($vehicleMap[$cursor]['total_truck']     ?? 0),
                'taxi'      => (int) ($vehicleMap[$cursor]['total_taxi']      ?? 0),
            ];
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        $hourEwalkMap = array_column($trafficModel->getByHour($from, $to, 'ewalk'),     'total', 'jam');
        $hourPentaMap = array_column($trafficModel->getByHour($from, $to, 'pentacity'), 'total', 'jam');
        $hours = [];
        for ($h = 10; $h <= 23; $h++) {
            $ew = (int) ($hourEwalkMap[$h] ?? 0);
            $pt = (int) ($hourPentaMap[$h] ?? 0);
            $hours[] = ['jam' => sprintf('%02d:00–%02d:00', $h, $h + 1), 'ewalk' => $ew, 'pentacity' => $pt, 'total' => $ew + $pt];
        }

        $doorEwalk = $trafficModel->getByDoor($from, $to, 'ewalk');
        $doorPenta = $trafficModel->getByDoor($from, $to, 'pentacity');

        $activeDays  = count(array_filter(array_column($days, 'total'), fn($v) => $v > 0));
        $avgDaily    = $activeDays > 0 ? (int) round($totalVisitor / $activeDays) : 0;
        $peakHourVal = max(array_column($hours, 'total') ?: [0]);
        $peakHourIdx = $peakHourVal > 0 ? array_search($peakHourVal, array_column($hours, 'total')) : null;
        $bestDayVal  = max(array_column($days,  'total') ?: [0]);
        $bestDayIdx  = $bestDayVal > 0 ? array_search($bestDayVal, array_column($days, 'total')) : null;

        $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevDays = max(1, count($days));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($prevDays - 1) . ' days'));
        $prevTotal = $trafficModel->getPeriodTotal($prevFrom, $prevTo, 'ewalk')
                   + $trafficModel->getPeriodTotal($prevFrom, $prevTo, 'pentacity');
        $changePct = $prevTotal > 0 ? round(($totalVisitor - $prevTotal) / $prevTotal * 100, 1) : null;

        // Weekday (Mon–Thu) vs Weekend (Fri–Sun)
        $wdEwalk = $wdPenta = $wdDays = 0;
        $weEwalk = $wePenta = $weDays = 0;
        $cursor  = $from;
        foreach ($days as $row) {
            $dow = (int) date('N', strtotime($cursor));
            if ($dow <= 4) {
                $wdEwalk += $row['ewalk']; $wdPenta += $row['pentacity'];
                if ($row['total'] > 0) $wdDays++;
            } else {
                $weEwalk += $row['ewalk']; $wePenta += $row['pentacity'];
                if ($row['total'] > 0) $weDays++;
            }
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        $wdTotal = $wdEwalk + $wdPenta;
        $weTotal = $weEwalk + $wePenta;
        $wdAvg   = $wdDays > 0 ? (int) round($wdTotal / $wdDays) : 0;
        $weAvg   = $weDays > 0 ? (int) round($weTotal / $weDays) : 0;

        return view('traffic/print_summary', [
            'from'         => $from,
            'to'           => $to,
            'totalVisitor' => $totalVisitor,
            'totalEwalk'   => $totalEwalk,
            'totalPenta'   => $totalPenta,
            'vehicles'     => $vehicles,
            'days'         => $days,
            'hours'        => $hours,
            'doorEwalk'    => $doorEwalk,
            'doorPenta'    => $doorPenta,
            'avgDaily'     => $avgDaily,
            'peakHour'     => $peakHourIdx !== null ? $hours[$peakHourIdx]['jam'] : null,
            'peakHourVal'  => $peakHourVal,
            'bestDay'      => $bestDayIdx !== null ? $days[$bestDayIdx]['date_fmt'] : null,
            'bestDayVal'   => $bestDayVal,
            'prevFrom'     => $prevFrom,
            'prevTo'       => $prevTo,
            'prevTotal'    => $prevTotal,
            'changePct'    => $changePct,
            'wdTotal'      => $wdTotal, 'wdEwalk' => $wdEwalk, 'wdPenta' => $wdPenta, 'wdAvg' => $wdAvg, 'wdDays' => $wdDays,
            'weTotal'      => $weTotal, 'weEwalk' => $weEwalk, 'wePenta' => $wePenta, 'weAvg' => $weAvg, 'weDays' => $weDays,
            'periodEvents' => (new EventModel())->getByPeriod($from, $to),
        ]);
    }

    public function printCompare()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from1 = $this->request->getGet('from1') ?: date('Y-m-01', strtotime('first day of last month'));
        $to1   = $this->request->getGet('to1')   ?: date('Y-m-t',  strtotime('last day of last month'));
        $from2 = $this->request->getGet('from2') ?: date('Y-m-01');
        $to2   = $this->request->getGet('to2')   ?: date('Y-m-t');
        $from3 = $this->request->getGet('from3') ?: null;
        $to3   = $this->request->getGet('to3')   ?: null;
        $hasP3 = $from3 !== null && $to3 !== null;

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        $p1Ewalk    = $trafficModel->getPeriodTotal($from1, $to1, 'ewalk');
        $p1Penta    = $trafficModel->getPeriodTotal($from1, $to1, 'pentacity');
        $p1Vehicles = $vehicleModel->getPeriodTotals($from1, $to1);
        $p1Total    = $p1Ewalk + $p1Penta;

        $p2Ewalk    = $trafficModel->getPeriodTotal($from2, $to2, 'ewalk');
        $p2Penta    = $trafficModel->getPeriodTotal($from2, $to2, 'pentacity');
        $p2Vehicles = $vehicleModel->getPeriodTotals($from2, $to2);
        $p2Total    = $p2Ewalk + $p2Penta;

        $map1  = $trafficModel->getDailyByBothMalls($from1, $to1);
        $map2  = $trafficModel->getDailyByBothMalls($from2, $to2);
        $days1 = (int) ((strtotime($to1) - strtotime($from1)) / 86400) + 1;
        $days2 = (int) ((strtotime($to2) - strtotime($from2)) / 86400) + 1;

        $p1Daily = [];
        $cursor  = $from1;
        for ($i = 0; $i < $days1; $i++) {
            $p1Daily[] = ($map1[$cursor]['ewalk'] ?? 0) + ($map1[$cursor]['pentacity'] ?? 0);
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        $p2Daily = [];
        $cursor  = $from2;
        for ($i = 0; $i < $days2; $i++) {
            $p2Daily[] = ($map2[$cursor]['ewalk'] ?? 0) + ($map2[$cursor]['pentacity'] ?? 0);
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        $h1EwalkMap = array_column($trafficModel->getByHour($from1, $to1, 'ewalk'),     'total', 'jam');
        $h1PentaMap = array_column($trafficModel->getByHour($from1, $to1, 'pentacity'), 'total', 'jam');
        $h2EwalkMap = array_column($trafficModel->getByHour($from2, $to2, 'ewalk'),     'total', 'jam');
        $h2PentaMap = array_column($trafficModel->getByHour($from2, $to2, 'pentacity'), 'total', 'jam');

        $chartHours = [];
        $p1HourData = [];
        $p2HourData = [];
        for ($h = 10; $h <= 23; $h++) {
            $chartHours[] = sprintf('%02d:00', $h);
            $p1HourData[] = (int) (($h1EwalkMap[$h] ?? 0) + ($h1PentaMap[$h] ?? 0));
            $p2HourData[] = (int) (($h2EwalkMap[$h] ?? 0) + ($h2PentaMap[$h] ?? 0));
        }

        $door1Ewalk = $trafficModel->getByDoor($from1, $to1, 'ewalk');
        $door1Penta = $trafficModel->getByDoor($from1, $to1, 'pentacity');
        $door2Ewalk = $trafficModel->getByDoor($from2, $to2, 'ewalk');
        $door2Penta = $trafficModel->getByDoor($from2, $to2, 'pentacity');

        $p3Total    = $p3Ewalk = $p3Penta = 0;
        $p3Vehicles = ['mobil' => 0, 'motor' => 0, 'mobil_box' => 0, 'bus' => 0, 'truck' => 0, 'taxi' => 0];
        $p3Daily    = [];
        $p3HourData = [];
        $door3Ewalk = $door3Penta = [];
        $days3      = 0;
        $map3       = [];

        if ($hasP3) {
            $p3Ewalk    = $trafficModel->getPeriodTotal($from3, $to3, 'ewalk');
            $p3Penta    = $trafficModel->getPeriodTotal($from3, $to3, 'pentacity');
            $p3Vehicles = $vehicleModel->getPeriodTotals($from3, $to3);
            $p3Total    = $p3Ewalk + $p3Penta;

            $map3   = $trafficModel->getDailyByBothMalls($from3, $to3);
            $days3  = (int) ((strtotime($to3) - strtotime($from3)) / 86400) + 1;
            $cursor = $from3;
            for ($i = 0; $i < $days3; $i++) {
                $p3Daily[] = ($map3[$cursor]['ewalk'] ?? 0) + ($map3[$cursor]['pentacity'] ?? 0);
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }

            $h3EwalkMap = array_column($trafficModel->getByHour($from3, $to3, 'ewalk'),     'total', 'jam');
            $h3PentaMap = array_column($trafficModel->getByHour($from3, $to3, 'pentacity'), 'total', 'jam');
            for ($h = 10; $h <= 23; $h++) {
                $p3HourData[] = (int) (($h3EwalkMap[$h] ?? 0) + ($h3PentaMap[$h] ?? 0));
            }

            $door3Ewalk = $trafficModel->getByDoor($from3, $to3, 'ewalk');
            $door3Penta = $trafficModel->getByDoor($from3, $to3, 'pentacity');
        }

        $maxDays   = max($days1, $days2, max(1, $days3));
        $dayLabels = array_map(fn($i) => 'H' . ($i + 1), range(0, $maxDays - 1));

        $calcWdWe = function (array $map, string $from, int $numDays): array {
            $wdT = $wdE = $wdP = $wdD = 0;
            $weT = $weE = $weP = $weD = 0;
            $cursor = $from;
            for ($i = 0; $i < $numDays; $i++) {
                $dow = (int) date('N', strtotime($cursor));
                $ew  = (int) ($map[$cursor]['ewalk']     ?? 0);
                $pt  = (int) ($map[$cursor]['pentacity'] ?? 0);
                if ($dow <= 4) { $wdE += $ew; $wdP += $pt; if ($ew + $pt > 0) $wdD++; }
                else           { $weE += $ew; $weP += $pt; if ($ew + $pt > 0) $weD++; }
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
            $wdT = $wdE + $wdP; $weT = $weE + $weP;
            return [
                'wd' => ['total' => $wdT, 'ewalk' => $wdE, 'penta' => $wdP, 'avg' => $wdD > 0 ? (int) round($wdT / $wdD) : 0, 'days' => $wdD],
                'we' => ['total' => $weT, 'ewalk' => $weE, 'penta' => $weP, 'avg' => $weD > 0 ? (int) round($weT / $weD) : 0, 'days' => $weD],
            ];
        };

        $p1WdWe = $calcWdWe($map1, $from1, $days1);
        $p2WdWe = $calcWdWe($map2, $from2, $days2);
        $p3WdWe = $hasP3 ? $calcWdWe($map3, $from3, $days3)
                         : ['wd' => ['total'=>0,'ewalk'=>0,'penta'=>0,'avg'=>0,'days'=>0],
                            'we' => ['total'=>0,'ewalk'=>0,'penta'=>0,'avg'=>0,'days'=>0]];

        $eventModel = new EventModel();

        return view('traffic/print_compare', [
            'from1'      => $from1, 'to1' => $to1,
            'from2'      => $from2, 'to2' => $to2,
            'from3'      => $from3, 'to3' => $to3,
            'hasP3'      => $hasP3,
            'p1Total'    => $p1Total,    'p2Total'    => $p2Total,    'p3Total'    => $p3Total,
            'p1Ewalk'    => $p1Ewalk,    'p2Ewalk'    => $p2Ewalk,    'p3Ewalk'    => $p3Ewalk,
            'p1Penta'    => $p1Penta,    'p2Penta'    => $p2Penta,    'p3Penta'    => $p3Penta,
            'p1Vehicles' => $p1Vehicles, 'p2Vehicles' => $p2Vehicles, 'p3Vehicles' => $p3Vehicles,
            'dayLabels'  => $dayLabels,
            'p1Daily'    => $p1Daily,    'p2Daily'    => $p2Daily,    'p3Daily'    => $p3Daily,
            'chartHours' => $chartHours,
            'p1HourData' => $p1HourData, 'p2HourData' => $p2HourData, 'p3HourData' => $p3HourData,
            'door1Ewalk' => $door1Ewalk, 'door1Penta' => $door1Penta,
            'door2Ewalk' => $door2Ewalk, 'door2Penta' => $door2Penta,
            'door3Ewalk' => $door3Ewalk, 'door3Penta' => $door3Penta,
            'p1Events'   => $eventModel->getByPeriod($from1, $to1),
            'p2Events'   => $eventModel->getByPeriod($from2, $to2),
            'p3Events'   => $hasP3 ? $eventModel->getByPeriod($from3, $to3) : [],
            'p1WdWe'     => $p1WdWe,     'p2WdWe'     => $p2WdWe,     'p3WdWe'     => $p3WdWe,
        ]);
    }

    private function buildTrafficExcelHtml(array $d): string
    {
        $n   = fn(int $v) => number_format($v, 0, ',', '.');
        $fmt = fn(int $v) => $v > 0 ? $n($v) : '—';

        $hBg  = '#1e3a8a';
        $hFg  = '#ffffff';
        $th   = 'background:#dbeafe;font-weight:bold;padding:5px 10px;border:1px solid #93c5fd;font-size:9pt;';
        $thR  = $th . 'text-align:right;';
        $td   = 'padding:5px 10px;border:1px solid #e2e8f0;font-size:9pt;';
        $tdR  = $td . 'text-align:right;';
        $sec  = 'background:#1e3a8a;color:#fff;font-weight:bold;padding:6px 12px;font-size:10pt;';
        $totR = 'background:#dbeafe;font-weight:bold;padding:5px 10px;border:1px solid #93c5fd;text-align:right;font-size:9pt;';

        $fromFmt = date('d M Y', strtotime($d['from']));
        $toFmt   = date('d M Y', strtotime($d['to']));
        $maxHour = max(array_column($d['hours'], 'total') ?: [0]);

        $out  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $out .= '<style>body{font-family:Calibri,Arial,sans-serif;font-size:10pt}';
        $out .= 'table{border-collapse:collapse;margin-bottom:18px}td,th{white-space:nowrap}</style></head><body>';

        // ── Header ────────────────────────────────────────────────────────────
        $out .= '<table>';
        $out .= '<tr><td colspan="7" style="background:' . $hBg . ';color:' . $hFg . ';font-size:15pt;font-weight:bold;padding:10px 14px">Traffic Summary — eWalk &amp; Pentacity</td></tr>';
        $out .= '<tr><td colspan="7" style="background:#1e40af;color:#bfdbfe;font-size:10pt;padding:6px 14px">Periode: ' . $fromFmt . ' — ' . $toFmt . '</td></tr>';
        $out .= '<tr><td colspan="7" style="background:#1e40af;color:#93c5fd;font-size:8.5pt;padding:4px 14px">Digenerate: ' . date('d M Y H:i') . ' &nbsp;·&nbsp; Mall Intelligence Center v1.4</td></tr>';
        $out .= '</table>';

        // ── KPI ───────────────────────────────────────────────────────────────
        $out .= '<table>';
        $out .= '<tr><td colspan="4" style="' . $sec . '">RINGKASAN KPI</td></tr>';
        $out .= '<tr><th style="' . $th . '">Metrik</th><th style="' . $thR . '">eWalk</th><th style="' . $thR . '">Pentacity</th><th style="' . $thR . '">Total</th></tr>';

        $rows = [
            ['Total Pengunjung',          $n($d['totalEwalk']),  $n($d['totalPenta']),  $n($d['totalVisitor']), true],
            ['Rata-rata / Hari',          $fmt((int)round($d['totalEwalk'] / max(1, count($d['days'])))),
                                          $fmt((int)round($d['totalPenta'] / max(1, count($d['days'])))),
                                          $n($d['avgDaily']),                                                   false],
            ['Kendaraan — Mobil',         '—', '—', $n($d['vehicles']['mobil']),     false],
            ['Kendaraan — Motor',         '—', '—', $n($d['vehicles']['motor']),     false],
            ['Kendaraan — Mobil Box',     '—', '—', $n($d['vehicles']['mobil_box']), false],
            ['Kendaraan — Bus',           '—', '—', $n($d['vehicles']['bus']),       false],
            ['Kendaraan — Truck',         '—', '—', $n($d['vehicles']['truck']),     false],
            ['Kendaraan — Taxi',          '—', '—', $n($d['vehicles']['taxi']),      false],
        ];
        if ($d['peakHour']) {
            $rows[] = ['Jam Tersibuk', $d['peakHour'], '', $n($d['peakHourVal']) . ' orang', false];
        }
        if ($d['bestDay']) {
            $rows[] = ['Hari Terbaik', $d['bestDay'],  '', $n($d['bestDayVal'])  . ' orang', false];
        }

        foreach ($rows as [$label, $ew, $pt, $tot, $bold]) {
            $b = $bold ? 'font-weight:bold;' : '';
            $out .= '<tr>';
            $out .= '<td style="' . $td . $b . '">' . $label . '</td>';
            $out .= '<td style="' . $tdR . $b . '">' . $ew  . '</td>';
            $out .= '<td style="' . $tdR . $b . '">' . $pt  . '</td>';
            $out .= '<td style="' . $tdR . $b . '">' . $tot . '</td>';
            $out .= '</tr>';
        }
        $out .= '</table>';

        // ── Daily ─────────────────────────────────────────────────────────────
        $hasExtra = ($d['vehicles']['mobil_box'] + $d['vehicles']['bus'] + $d['vehicles']['truck'] + $d['vehicles']['taxi']) > 0;
        $totalVeh = $d['vehicles']['mobil'] + $d['vehicles']['motor'] + $d['vehicles']['mobil_box'] + $d['vehicles']['bus'] + $d['vehicles']['truck'] + $d['vehicles']['taxi'];
        $colspan  = $hasExtra ? 10 : 6;
        $out .= '<table>';
        $out .= '<tr><td colspan="' . $colspan . '" style="' . $sec . '">TRAFFIC HARIAN</td></tr>';
        $out .= '<tr><th style="' . $th . '">Tanggal</th><th style="' . $thR . '">eWalk</th><th style="' . $thR . '">Pentacity</th><th style="' . $thR . '">Total</th><th style="' . $thR . '">Mobil</th><th style="' . $thR . '">Motor</th>';
        if ($hasExtra) $out .= '<th style="' . $thR . '">Box</th><th style="' . $thR . '">Bus</th><th style="' . $thR . '">Truck</th><th style="' . $thR . '">Taxi</th>';
        $out .= '</tr>';

        foreach ($d['days'] as $row) {
            $rowVeh = $row['mobil'] + $row['motor'] + $row['mobil_box'] + $row['bus'] + $row['truck'] + $row['taxi'];
            $out .= '<tr>';
            $out .= '<td style="' . $td . '">'  . $row['date_fmt'] . '</td>';
            $out .= '<td style="' . $tdR . '">' . $fmt($row['ewalk'])     . '</td>';
            $out .= '<td style="' . $tdR . '">' . $fmt($row['pentacity']) . '</td>';
            $out .= '<td style="' . $tdR . ($row['total'] > 0 ? 'font-weight:bold;' : '') . '">' . $fmt($row['total']) . '</td>';
            $out .= '<td style="' . $tdR . '">' . $fmt($row['mobil'])  . '</td>';
            $out .= '<td style="' . $tdR . '">' . $fmt($row['motor'])  . '</td>';
            if ($hasExtra) {
                $out .= '<td style="' . $tdR . '">' . $fmt($row['mobil_box']) . '</td>';
                $out .= '<td style="' . $tdR . '">' . $fmt($row['bus'])       . '</td>';
                $out .= '<td style="' . $tdR . '">' . $fmt($row['truck'])     . '</td>';
                $out .= '<td style="' . $tdR . '">' . $fmt($row['taxi'])      . '</td>';
            }
            $out .= '</tr>';
        }
        $out .= '<tr>';
        $out .= '<td style="' . $totR . 'text-align:left;">TOTAL</td>';
        $out .= '<td style="' . $totR . '">' . $n($d['totalEwalk'])        . '</td>';
        $out .= '<td style="' . $totR . '">' . $n($d['totalPenta'])        . '</td>';
        $out .= '<td style="' . $totR . '">' . $n($d['totalVisitor'])      . '</td>';
        $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['mobil']) . '</td>';
        $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['motor']) . '</td>';
        if ($hasExtra) {
            $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['mobil_box']) . '</td>';
            $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['bus'])       . '</td>';
            $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['truck'])     . '</td>';
            $out .= '<td style="' . $totR . '">' . $n($d['vehicles']['taxi'])      . '</td>';
        }
        $out .= '</tr></table>';

        // ── Hourly ────────────────────────────────────────────────────────────
        $out .= '<table>';
        $out .= '<tr><td colspan="4" style="' . $sec . '">TRAFFIC PER JAM</td></tr>';
        $out .= '<tr><th style="' . $th . '">Jam</th><th style="' . $thR . '">eWalk</th><th style="' . $thR . '">Pentacity</th><th style="' . $thR . '">Total</th></tr>';
        foreach ($d['hours'] as $row) {
            $peak = $row['total'] === $maxHour && $maxHour > 0;
            $bg   = $peak ? 'background:#fef3c7;' : '';
            $out .= '<tr>';
            $out .= '<td style="' . $td . $bg . '">' . $row['jam'] . ($peak ? ' ★' : '') . '</td>';
            $out .= '<td style="' . $tdR . $bg . '">' . $n($row['ewalk'])     . '</td>';
            $out .= '<td style="' . $tdR . $bg . '">' . $n($row['pentacity']) . '</td>';
            $out .= '<td style="' . $tdR . $bg . 'font-weight:' . ($peak ? 'bold' : 'normal') . ';">' . $n($row['total']) . '</td>';
            $out .= '</tr>';
        }
        $out .= '</table>';

        // ── Per Pintu eWalk ───────────────────────────────────────────────────
        if (! empty($d['doorEwalk'])) {
            $out .= '<table>';
            $out .= '<tr><td colspan="2" style="' . $sec . 'background:#1d4ed8;">TRAFFIC PER PINTU — eWalk</td></tr>';
            $out .= '<tr><th style="' . $th . '">Pintu</th><th style="' . $thR . '">Total Pengunjung</th></tr>';
            foreach ($d['doorEwalk'] as $row) {
                $out .= '<tr><td style="' . $td . '">' . htmlspecialchars($row['pintu']) . '</td><td style="' . $tdR . '">' . $n((int)$row['total']) . '</td></tr>';
            }
            $out .= '</table>';
        }

        // ── Per Pintu Pentacity ───────────────────────────────────────────────
        if (! empty($d['doorPenta'])) {
            $out .= '<table>';
            $out .= '<tr><td colspan="2" style="' . $sec . 'background:#065f46;">TRAFFIC PER PINTU — Pentacity</td></tr>';
            $out .= '<tr><th style="' . $th . 'background:#d1fae5;">Pintu</th><th style="' . $thR . 'background:#d1fae5;">Total Pengunjung</th></tr>';
            foreach ($d['doorPenta'] as $row) {
                $out .= '<tr><td style="' . $td . '">' . htmlspecialchars($row['pintu']) . '</td><td style="' . $tdR . '">' . $n((int)$row['total']) . '</td></tr>';
            }
            $out .= '</table>';
        }

        $out .= '</body></html>';
        return $out;
    }

    public function compare()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from1 = $this->request->getGet('from1') ?: date('Y-m-01', strtotime('first day of last month'));
        $to1   = $this->request->getGet('to1')   ?: date('Y-m-t',  strtotime('last day of last month'));
        $from2 = $this->request->getGet('from2') ?: date('Y-m-01');
        $to2   = $this->request->getGet('to2')   ?: date('Y-m-t');
        $from3 = $this->request->getGet('from3') ?: null;
        $to3   = $this->request->getGet('to3')   ?: null;
        $hasP3 = $from3 !== null && $to3 !== null;

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        // KPI per periode
        $p1Ewalk    = $trafficModel->getPeriodTotal($from1, $to1, 'ewalk');
        $p1Penta    = $trafficModel->getPeriodTotal($from1, $to1, 'pentacity');
        $p1Vehicles = $vehicleModel->getPeriodTotals($from1, $to1);
        $p1Total    = $p1Ewalk + $p1Penta;

        $p2Ewalk    = $trafficModel->getPeriodTotal($from2, $to2, 'ewalk');
        $p2Penta    = $trafficModel->getPeriodTotal($from2, $to2, 'pentacity');
        $p2Vehicles = $vehicleModel->getPeriodTotals($from2, $to2);
        $p2Total    = $p2Ewalk + $p2Penta;

        // Daily chart — indexed (Hari ke-1, 2, …)
        $map1  = $trafficModel->getDailyByBothMalls($from1, $to1);
        $map2  = $trafficModel->getDailyByBothMalls($from2, $to2);
        $days1 = (int) ((strtotime($to1) - strtotime($from1)) / 86400) + 1;
        $days2 = (int) ((strtotime($to2) - strtotime($from2)) / 86400) + 1;

        $p1Daily = [];
        $cursor  = $from1;
        for ($i = 0; $i < $days1; $i++) {
            $p1Daily[] = ($map1[$cursor]['ewalk'] ?? 0) + ($map1[$cursor]['pentacity'] ?? 0);
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        $p2Daily = [];
        $cursor  = $from2;
        for ($i = 0; $i < $days2; $i++) {
            $p2Daily[] = ($map2[$cursor]['ewalk'] ?? 0) + ($map2[$cursor]['pentacity'] ?? 0);
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        // Hourly
        $h1EwalkMap = array_column($trafficModel->getByHour($from1, $to1, 'ewalk'),     'total', 'jam');
        $h1PentaMap = array_column($trafficModel->getByHour($from1, $to1, 'pentacity'), 'total', 'jam');
        $h2EwalkMap = array_column($trafficModel->getByHour($from2, $to2, 'ewalk'),     'total', 'jam');
        $h2PentaMap = array_column($trafficModel->getByHour($from2, $to2, 'pentacity'), 'total', 'jam');

        $chartHours = [];
        $p1HourData = [];
        $p2HourData = [];
        for ($h = 10; $h <= 23; $h++) {
            $chartHours[] = sprintf('%02d:00', $h);
            $p1HourData[] = (int) (($h1EwalkMap[$h] ?? 0) + ($h1PentaMap[$h] ?? 0));
            $p2HourData[] = (int) (($h2EwalkMap[$h] ?? 0) + ($h2PentaMap[$h] ?? 0));
        }

        // Door
        $door1Ewalk = $trafficModel->getByDoor($from1, $to1, 'ewalk');
        $door1Penta = $trafficModel->getByDoor($from1, $to1, 'pentacity');
        $door2Ewalk = $trafficModel->getByDoor($from2, $to2, 'ewalk');
        $door2Penta = $trafficModel->getByDoor($from2, $to2, 'pentacity');

        // Periode 3 — opsional
        $p3Total    = $p3Ewalk = $p3Penta = 0;
        $p3Vehicles = ['mobil' => 0, 'motor' => 0, 'mobil_box' => 0, 'bus' => 0, 'truck' => 0, 'taxi' => 0];
        $p3Daily    = [];
        $p3HourData = [];
        $door3Ewalk = $door3Penta = [];
        $days3      = 0;

        if ($hasP3) {
            $p3Ewalk    = $trafficModel->getPeriodTotal($from3, $to3, 'ewalk');
            $p3Penta    = $trafficModel->getPeriodTotal($from3, $to3, 'pentacity');
            $p3Vehicles = $vehicleModel->getPeriodTotals($from3, $to3);
            $p3Total    = $p3Ewalk + $p3Penta;

            $map3   = $trafficModel->getDailyByBothMalls($from3, $to3);
            $days3  = (int) ((strtotime($to3) - strtotime($from3)) / 86400) + 1;
            $cursor = $from3;
            for ($i = 0; $i < $days3; $i++) {
                $p3Daily[] = ($map3[$cursor]['ewalk'] ?? 0) + ($map3[$cursor]['pentacity'] ?? 0);
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }

            $h3EwalkMap = array_column($trafficModel->getByHour($from3, $to3, 'ewalk'),     'total', 'jam');
            $h3PentaMap = array_column($trafficModel->getByHour($from3, $to3, 'pentacity'), 'total', 'jam');
            for ($h = 10; $h <= 23; $h++) {
                $p3HourData[] = (int) (($h3EwalkMap[$h] ?? 0) + ($h3PentaMap[$h] ?? 0));
            }

            $door3Ewalk = $trafficModel->getByDoor($from3, $to3, 'ewalk');
            $door3Penta = $trafficModel->getByDoor($from3, $to3, 'pentacity');
        }

        $maxDays   = max($days1, $days2, $days3);
        $dayLabels = array_map(fn($i) => 'H' . ($i + 1), range(0, $maxDays - 1));

        // Weekday/weekend breakdown per periode
        $calcWdWe = function (array $map, string $from, int $numDays): array {
            $wdT = $wdE = $wdP = $wdD = 0;
            $weT = $weE = $weP = $weD = 0;
            $cursor = $from;
            for ($i = 0; $i < $numDays; $i++) {
                $dow = (int) date('N', strtotime($cursor));
                $ew  = (int) ($map[$cursor]['ewalk']     ?? 0);
                $pt  = (int) ($map[$cursor]['pentacity'] ?? 0);
                if ($dow <= 4) { $wdE += $ew; $wdP += $pt; if ($ew + $pt > 0) $wdD++; }
                else           { $weE += $ew; $weP += $pt; if ($ew + $pt > 0) $weD++; }
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
            $wdT = $wdE + $wdP; $weT = $weE + $weP;
            return [
                'wd' => ['total' => $wdT, 'ewalk' => $wdE, 'penta' => $wdP, 'avg' => $wdD > 0 ? (int) round($wdT / $wdD) : 0, 'days' => $wdD],
                'we' => ['total' => $weT, 'ewalk' => $weE, 'penta' => $weP, 'avg' => $weD > 0 ? (int) round($weT / $weD) : 0, 'days' => $weD],
            ];
        };

        $p1WdWe = $calcWdWe($map1, $from1, $days1);
        $p2WdWe = $calcWdWe($map2, $from2, $days2);
        $p3WdWe = $hasP3 ? $calcWdWe($map3, $from3, $days3) : ['wd' => ['total'=>0,'ewalk'=>0,'penta'=>0,'avg'=>0,'days'=>0], 'we' => ['total'=>0,'ewalk'=>0,'penta'=>0,'avg'=>0,'days'=>0]];

        $eventModel = new EventModel();
        $p1Events   = $eventModel->getByPeriod($from1, $to1);
        $p2Events   = $eventModel->getByPeriod($from2, $to2);
        $p3Events   = $hasP3 ? $eventModel->getByPeriod($from3, $to3) : [];

        return view('traffic/compare', [
            'user'       => $this->currentUser(),
            'from1'      => $from1, 'to1' => $to1,
            'from2'      => $from2, 'to2' => $to2,
            'from3'      => $from3, 'to3' => $to3,
            'hasP3'      => $hasP3,
            'p1Total'    => $p1Total,    'p2Total'    => $p2Total,    'p3Total'    => $p3Total,
            'p1Ewalk'    => $p1Ewalk,    'p2Ewalk'    => $p2Ewalk,    'p3Ewalk'    => $p3Ewalk,
            'p1Penta'    => $p1Penta,    'p2Penta'    => $p2Penta,    'p3Penta'    => $p3Penta,
            'p1Vehicles' => $p1Vehicles, 'p2Vehicles' => $p2Vehicles, 'p3Vehicles' => $p3Vehicles,
            'dayLabels'  => $dayLabels,
            'p1Daily'    => $p1Daily,    'p2Daily'    => $p2Daily,    'p3Daily'    => $p3Daily,
            'chartHours' => $chartHours,
            'p1HourData' => $p1HourData, 'p2HourData' => $p2HourData, 'p3HourData' => $p3HourData,
            'door1Ewalk' => $door1Ewalk, 'door1Penta' => $door1Penta,
            'door2Ewalk' => $door2Ewalk, 'door2Penta' => $door2Penta,
            'door3Ewalk' => $door3Ewalk, 'door3Penta' => $door3Penta,
            'p1Events'   => $p1Events,   'p2Events'   => $p2Events,   'p3Events'   => $p3Events,
            'p1WdWe'     => $p1WdWe,     'p2WdWe'     => $p2WdWe,     'p3WdWe'     => $p3WdWe,
        ]);
    }

    public function form(string $mall = 'ewalk', string $tanggal = '')
    {
        if (! $this->canEditMenu('traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }

        if (! in_array($mall, ['ewalk', 'pentacity'])) {
            return redirect()->to('/traffic')->with('error', 'Mall tidak valid.');
        }

        $tanggal = $tanggal ?: date('Y-m-d');

        $trafficModel  = new DailyTrafficModel();
        $vehicleModel  = new DailyVehicleModel();
        $trafficRows   = $trafficModel->getByDateMall($tanggal, $mall);
        $vehicleRow    = $vehicleModel->getByDateMall($tanggal, $mall);
        $doors         = (new TrafficDoorModel())->getByMall($mall);

        return view('traffic/form', [
            'user'        => $this->currentUser(),
            'mall'        => $mall,
            'tanggal'     => $tanggal,
            'trafficRows' => $trafficRows,
            'vehicleRow'  => $vehicleRow,
            'doors'       => $doors,
        ]);
    }

    public function save()
    {
        if (! $this->canEditMenu('traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }

        $post    = $this->request->getPost();
        $tanggal = $post['tanggal'];
        $mall    = $post['mall'];
        $userId  = $this->currentUser()['id'];

        if (! in_array($mall, ['ewalk', 'pentacity'])) {
            return redirect()->to('/traffic')->with('error', 'Mall tidak valid.');
        }

        $trafficModel = new DailyTrafficModel();
        $vehicleModel = new DailyVehicleModel();

        // Replace traffic rows for this date+mall
        $trafficModel->deleteByDateMall($tanggal, $mall);

        // jumlah[jam][door_id] = count (grid format from form)
        $doors   = (new TrafficDoorModel())->getByMall($mall, false);
        $doorMap = array_column($doors, 'nama_pintu', 'id');

        foreach (($post['jumlah'] ?? []) as $jam => $doorCounts) {
            foreach ($doorCounts as $doorId => $jumlah) {
                $jumlah = (int)$jumlah;
                if ($jumlah <= 0) continue;
                $pintu = $doorMap[$doorId] ?? null;
                if (! $pintu) continue;
                $trafficModel->insert([
                    'tanggal'           => $tanggal,
                    'mall'              => $mall,
                    'jam'               => (int)$jam,
                    'pintu'             => $pintu,
                    'jumlah_pengunjung' => $jumlah,
                    'created_by'        => $userId,
                ]);
            }
        }

        // Save vehicles (upsert)
        $existing = $vehicleModel->getByDateMall($tanggal, $mall);
        $vehicleData = [
            'tanggal'         => $tanggal,
            'mall'            => $mall,
            'total_mobil'     => (int)($post['total_mobil']     ?? 0),
            'total_motor'     => (int)($post['total_motor']     ?? 0),
            'total_mobil_box' => (int)($post['total_mobil_box'] ?? 0),
            'total_bus'       => (int)($post['total_bus']       ?? 0),
            'total_truck'     => (int)($post['total_truck']     ?? 0),
            'total_taxi'      => (int)($post['total_taxi']      ?? 0),
            'created_by'      => $userId,
        ];

        if ($existing) {
            $vehicleModel->update($existing['id'], $vehicleData);
        } else {
            $vehicleModel->insert($vehicleData);
        }

        $totalSaved = array_sum(array_column(
            $trafficModel->where('tanggal', $tanggal)->where('mall', $mall)->findAll(),
            'jumlah_pengunjung'
        ));
        ActivityLog::write('update', 'traffic', "{$mall}/{$tanggal}", "Traffic {$mall} — {$tanggal}", [
            'mall'             => $mall,
            'tanggal'          => $tanggal,
            'total_pengunjung' => $totalSaved,
            'total_mobil'      => (int)($post['total_mobil']     ?? 0),
            'total_motor'      => (int)($post['total_motor']     ?? 0),
            'total_mobil_box'  => (int)($post['total_mobil_box'] ?? 0),
            'total_bus'        => (int)($post['total_bus']       ?? 0),
            'total_truck'      => (int)($post['total_truck']     ?? 0),
            'total_taxi'       => (int)($post['total_taxi']      ?? 0),
        ]);
        return redirect()->to('/traffic')->with('success', "Data traffic {$mall} tanggal {$tanggal} berhasil disimpan.");
    }

    public function importForm()
    {
        if (! $this->can('can_import_traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }
        return view('traffic/import', [
            'user'        => $this->currentUser(),
            'preview'     => session()->getFlashdata('import_preview'),
            'tmpFile'     => session()->getFlashdata('import_tmp'),
            'bulkItems'   => session()->getFlashdata('import_bulk'),
            'bulkErrors'  => session()->getFlashdata('import_bulk_errors'),
            'mall'        => session()->getFlashdata('import_mall') ?? 'ewalk',
        ]);
    }

    public function importPreview()
    {
        if (! $this->can('can_import_traffic')) {
            return redirect()->to('/traffic/import')->with('error', 'Akses ditolak.');
        }

        $files = $this->request->getFileMultiple('excel_file');
        $mall  = $this->request->getPost('mall') ?: 'ewalk';

        $validFiles = array_filter((array)$files, function ($f) {
            return $f && $f->isValid() && in_array($f->getClientExtension(), ['xlsx', 'xls']);
        });

        if (empty($validFiles)) {
            return redirect()->to('/traffic/import')->with('error', 'Tidak ada file .xlsx yang valid.');
        }

        // Single file → detailed preview
        if (count($validFiles) === 1) {
            $file    = array_values($validFiles)[0];
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $tmpPath = WRITEPATH . 'uploads/' . $newName;

            try {
                $preview = TrafficExcelParser::parse($tmpPath, $mall);
            } catch (\Exception $e) {
                @unlink($tmpPath);
                return redirect()->to('/traffic/import')->with('error', 'Gagal membaca file: ' . $e->getMessage());
            }

            session()->setFlashdata('import_preview', $preview);
            session()->setFlashdata('import_tmp',     $tmpPath);
            session()->setFlashdata('import_mall',    $mall);
            return redirect()->to('/traffic/import');
        }

        // Multiple files → bulk summary preview
        $bulkItems  = [];
        $bulkErrors = [];

        foreach ($validFiles as $file) {
            $origName = $file->getClientName();
            $newName  = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $tmpPath  = WRITEPATH . 'uploads/' . $newName;

            try {
                $parsed = TrafficExcelParser::parse($tmpPath, $mall);
                $bulkItems[] = [
                    'origName'     => $origName,
                    'tmpPath'      => $tmpPath,
                    'mall'         => $mall,
                    'tanggal'      => $parsed['tanggal'],
                    'totalVisitor' => $parsed['totalVisitor'],
                    'totalEwalk'   => $parsed['totalEwalk'] ?? 0,
                    'jamCount'     => count($parsed['rows']),
                    'doorCount'    => count($parsed['colToDoor']),
                    'warnings'     => $parsed['warnings'],
                ];
            } catch (\Exception $e) {
                @unlink($tmpPath);
                $bulkErrors[] = $origName . ': ' . $e->getMessage();
            }
        }

        if (empty($bulkItems)) {
            return redirect()->to('/traffic/import')->with('error', 'Semua file gagal dibaca. ' . implode(' | ', $bulkErrors));
        }

        // Sort by detected date
        usort($bulkItems, fn($a, $b) => ($a['tanggal'] ?? '') <=> ($b['tanggal'] ?? ''));

        session()->setFlashdata('import_bulk',       $bulkItems);
        session()->setFlashdata('import_bulk_errors', $bulkErrors ?: null);
        session()->setFlashdata('import_mall',        $mall);
        return redirect()->to('/traffic/import');
    }

    public function importBulkSave()
    {
        if (! $this->can('can_import_traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }

        $mall     = $this->request->getPost('mall');
        $tmpFiles = $this->request->getPost('tmp_files') ?: [];
        $tanggals = $this->request->getPost('tanggals')  ?: [];
        $userId   = $this->currentUser()['id'];

        $trafficModel = new DailyTrafficModel();
        $saved  = 0;
        $errors = [];

        foreach ($tmpFiles as $i => $tmpFile) {
            if (! $tmpFile || ! file_exists($tmpFile)) {
                $errors[] = 'File #' . ($i + 1) . ' tidak ditemukan (session mungkin habis).';
                continue;
            }

            $tanggal = trim($tanggals[$i] ?? '');

            try {
                $data = TrafficExcelParser::parse($tmpFile, $mall);
            } catch (\Exception $e) {
                @unlink($tmpFile);
                $errors[] = basename($tmpFile) . ': ' . $e->getMessage();
                continue;
            }

            $tanggal = $tanggal ?: $data['tanggal'];
            if (! $tanggal) {
                @unlink($tmpFile);
                $errors[] = 'File #' . ($i + 1) . ': tanggal tidak terdeteksi, lewati.';
                continue;
            }

            $trafficModel->deleteByDateMall($tanggal, $mall);

            foreach ($data['rows'] as $row) {
                foreach ($row['doors'] as $pintu => $jumlah) {
                    if ($jumlah <= 0) continue;
                    $trafficModel->insert([
                        'tanggal'           => $tanggal,
                        'mall'              => $mall,
                        'jam'               => $row['jam'],
                        'pintu'             => $pintu,
                        'jumlah_pengunjung' => $jumlah,
                        'created_by'        => $userId,
                    ]);
                }
            }

            // PSV: also save eWalk UG/FF override rows
            if (! empty($data['ewalkRows'])) {
                $this->saveEwalkOverride($trafficModel, $data['ewalkRows'], $tanggal, $userId);
            }

            @unlink($tmpFile);

            ActivityLog::write('create', 'traffic', "{$mall}/{$tanggal}", "Import traffic {$mall} — {$tanggal}", [
                'source'           => 'excel_import_bulk',
                'total_pengunjung' => $data['totalVisitor'],
                'total_ewalk'      => $data['totalEwalk'] ?? 0,
                'jam_count'        => count($data['rows']),
            ]);

            $saved++;
        }

        if ($errors) {
            $errMsg = implode(' | ', $errors);
            $flash  = $saved > 0 ? 'success' : 'error';
            $msg    = $saved > 0
                ? "Berhasil import {$saved} file untuk {$mall}. Gagal: {$errMsg}"
                : "Semua file gagal disimpan: {$errMsg}";
            return redirect()->to('/traffic')->with($flash, $msg);
        }

        return redirect()->to('/traffic')->with('success', "Berhasil import {$saved} file traffic {$mall}.");
    }

    public function importSave()
    {
        if (! $this->can('can_import_traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }

        $tmpFile = $this->request->getPost('tmp_file');
        $mall    = $this->request->getPost('mall');
        $tanggal = $this->request->getPost('tanggal');
        $userId  = $this->currentUser()['id'];

        if (! $tmpFile || ! file_exists($tmpFile)) {
            return redirect()->to('/traffic/import')->with('error', 'Session habis atau file hilang. Upload ulang.');
        }

        try {
            $data = TrafficExcelParser::parse($tmpFile, $mall);
        } catch (\Exception $e) {
            return redirect()->to('/traffic/import')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        $tanggal = $tanggal ?: $data['tanggal'];
        if (! $tanggal) {
            return redirect()->to('/traffic/import')->with('error', 'Tanggal tidak terdeteksi. Isi manual dan upload ulang.');
        }

        $trafficModel = new DailyTrafficModel();
        $trafficModel->deleteByDateMall($tanggal, $mall);

        foreach ($data['rows'] as $row) {
            foreach ($row['doors'] as $pintu => $jumlah) {
                if ($jumlah <= 0) continue;
                $trafficModel->insert([
                    'tanggal'           => $tanggal,
                    'mall'              => $mall,
                    'jam'               => $row['jam'],
                    'pintu'             => $pintu,
                    'jumlah_pengunjung' => $jumlah,
                    'created_by'        => $userId,
                ]);
            }
        }

        // PSV: also save eWalk UG/FF override rows
        if (! empty($data['ewalkRows'])) {
            $this->saveEwalkOverride($trafficModel, $data['ewalkRows'], $tanggal, $userId);
        }

        @unlink($tmpFile);

        $successMsg = "Import berhasil — traffic {$mall} tanggal {$tanggal} ({$data['totalVisitor']} pengunjung).";
        if (! empty($data['ewalkRows'])) {
            $successMsg .= " eWalk UG/FF juga diperbarui ({$data['totalEwalk']} pengunjung).";
        }

        ActivityLog::write('create', 'traffic', "{$mall}/{$tanggal}", "Import traffic {$mall} — {$tanggal}", [
            'source'           => 'excel_import',
            'total_pengunjung' => $data['totalVisitor'],
            'total_ewalk'      => $data['totalEwalk'] ?? 0,
            'jam_count'        => count($data['rows']),
        ]);

        return redirect()->to('/traffic')->with('success', $successMsg);
    }

    private function saveEwalkOverride(DailyTrafficModel $model, array $ewalkRows, string $tanggal, int $userId): void
    {
        $doors = array_unique(array_merge(
            array_merge(...array_map(fn($r) => array_keys($r['doors']), $ewalkRows)),
            ['UG Funstation', 'FF XXI']  // legacy names from old eWalk Excel imports
        ));
        $model->deleteByDateMallDoors($tanggal, 'ewalk', $doors);
        foreach ($ewalkRows as $row) {
            foreach ($row['doors'] as $pintu => $jumlah) {
                if ($jumlah <= 0) continue;
                $model->insert([
                    'tanggal'           => $tanggal,
                    'mall'              => 'ewalk',
                    'jam'               => $row['jam'],
                    'pintu'             => $pintu,
                    'jumlah_pengunjung' => $jumlah,
                    'created_by'        => $userId,
                ]);
            }
        }
    }

    public function export()
    {
        if (! $this->canViewMenu('traffic')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');
        $mall = $this->request->getGet('mall')  ?: '';

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
        if ($from > $to) [$from, $to] = [$to, $from];

        $db = db_connect();

        // Semua baris dalam periode, sum per tanggal+mall+pintu
        $rows = $db->table('daily_traffic')
            ->select('tanggal, mall, pintu, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal >=', $from)
            ->where('tanggal <=', $to)
            ->groupBy(['tanggal', 'mall', 'pintu'])
            ->orderBy('tanggal')
            ->orderBy('mall')
            ->orderBy('pintu');
        if ($mall) $rows->where('mall', $mall);
        $rows = $rows->get()->getResultArray();

        // Build date list
        $dates  = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $dates[] = $cursor;
            $cursor  = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        // Build per-mall pivot: [mall][tanggal][pintu] = total, and collect distinct doors per mall
        $pivot = [];
        $doors = [];
        foreach ($rows as $row) {
            $m = $row['mall'];
            $t = $row['tanggal'];
            $p = $row['pintu'];
            $pivot[$m][$t][$p] = (int)$row['total'];
            if (! in_array($p, $doors[$m] ?? [])) {
                $doors[$m][] = $p;
            }
        }
        foreach ($doors as &$dList) sort($dList);

        // Build sheet definitions
        $mallsToExport = $mall ? [$mall] : ['ewalk', 'pentacity'];
        $mallLabels    = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];
        $sheets = [];
        foreach ($mallsToExport as $m) {
            if (empty($doors[$m])) continue;
            $sheets[] = [
                'name'  => $mallLabels[$m] ?? ucfirst($m),
                'doors' => $doors[$m],
                'dates' => $dates,
                'data'  => $pivot[$m] ?? [],
            ];
        }

        $mallLabel = $mall ? '_' . $mall : '';
        $filename  = 'traffic' . $mallLabel . '_' . $from . '_sd_' . $to . '.xlsx';

        ActivityLog::write('export', 'traffic', '', $filename, ['from' => $from, 'to' => $to, 'mall' => $mall ?: 'all']);

        TrafficXlsxExporter::download($filename, $sheets);
    }

    public function delete(string $mall, string $tanggal)
    {
        if (! $this->can('can_delete_traffic')) {
            return redirect()->to('/traffic')->with('error', 'Akses ditolak.');
        }

        (new DailyTrafficModel())->deleteByDateMall($tanggal, $mall);
        $existing = (new DailyVehicleModel())->getByDateMall($tanggal, $mall);
        if ($existing) (new DailyVehicleModel())->delete($existing['id']);

        ActivityLog::write('delete', 'traffic', "{$mall}/{$tanggal}", "Traffic {$mall} — {$tanggal}", [
            'mall' => $mall, 'tanggal' => $tanggal,
        ]);
        return redirect()->to('/traffic')->with('success', 'Data berhasil dihapus.');
    }
}
