<?php

namespace App\Controllers;

use App\Models\DailyTrafficModel;
use App\Models\DailyVehicleModel;
use App\Models\TrafficDoorModel;
use App\Libraries\ActivityLog;
use App\Libraries\TrafficExcelParser;

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
        $vehicleRows  = $vehicleModel->getDailyTotals($from, $to);
        $chartVMobil  = [];
        $chartVMotor  = [];
        $vehicleMap   = array_column($vehicleRows, null, 'tanggal');
        foreach ($chartDates as $i => $label) {
            $date = date('Y-m-d', strtotime($from . " +{$i} days"));
            $chartVMobil[] = (int)($vehicleMap[$date]['total_mobil'] ?? 0);
            $chartVMotor[] = (int)($vehicleMap[$date]['total_motor'] ?? 0);
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

        return view('traffic/summary', [
            'user'          => $this->currentUser(),
            'from'          => $from,
            'to'            => $to,
            'totalEwalk'    => $totalEwalk,
            'totalPenta'    => $totalPenta,
            'totalVisitor'  => $totalEwalk + $totalPenta,
            'totalMobil'    => $vehicles['mobil'],
            'totalMotor'    => $vehicles['motor'],
            'chartDates'    => $chartDates,
            'chartEwalk'    => $chartEwalk,
            'chartPenta'    => $chartPenta,
            'chartVMobil'   => $chartVMobil,
            'chartVMotor'   => $chartVMotor,
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
        ]);
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
        $maxDays   = max($days1, $days2);
        $dayLabels = array_map(fn($i) => 'H' . ($i + 1), range(0, $maxDays - 1));

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

        return view('traffic/compare', [
            'user'       => $this->currentUser(),
            'from1'      => $from1, 'to1' => $to1,
            'from2'      => $from2, 'to2' => $to2,
            'p1Total'    => $p1Total,    'p2Total'    => $p2Total,
            'p1Ewalk'    => $p1Ewalk,    'p2Ewalk'    => $p2Ewalk,
            'p1Penta'    => $p1Penta,    'p2Penta'    => $p2Penta,
            'p1Vehicles' => $p1Vehicles, 'p2Vehicles' => $p2Vehicles,
            'dayLabels'  => $dayLabels,
            'p1Daily'    => $p1Daily,    'p2Daily'    => $p2Daily,
            'chartHours' => $chartHours,
            'p1HourData' => $p1HourData, 'p2HourData' => $p2HourData,
            'door1Ewalk' => $door1Ewalk, 'door1Penta' => $door1Penta,
            'door2Ewalk' => $door2Ewalk, 'door2Penta' => $door2Penta,
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
            'tanggal'     => $tanggal,
            'mall'        => $mall,
            'total_mobil' => (int)($post['total_mobil'] ?? 0),
            'total_motor' => (int)($post['total_motor'] ?? 0),
            'created_by'  => $userId,
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
            'mall'         => $mall,
            'tanggal'      => $tanggal,
            'total_pengunjung' => $totalSaved,
            'total_mobil'  => (int)($post['total_mobil'] ?? 0),
            'total_motor'  => (int)($post['total_motor'] ?? 0),
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
