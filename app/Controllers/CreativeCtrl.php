<?php

namespace App\Controllers;

use App\Models\CreativeItemModel;
use App\Models\CreativeRealisasiModel;
use App\Models\CreativeFileModel;
use App\Models\CreativeInsightModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Libraries\ActivityLog;

class CreativeCtrl extends BaseController
{
    private function realisasiDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private function fileDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private function insightDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    public function index()
    {
        if (!$this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $user = $this->currentUser();

        $standaloneItems = (new CreativeItemModel())->getAll();
        foreach ($standaloneItems as &$item) { $item['_source'] = 's'; }
        unset($item);

        $eventItems = (new EventCreativeItemModel())->getAllWithEvents();
        foreach ($eventItems as &$item) { $item['_source'] = 'e'; }
        unset($item);

        $merged = array_merge($standaloneItems, $eventItems);

        $byTipe = [];
        foreach ($merged as $item) {
            $byTipe[$item['tipe']][] = $item;
        }

        $standaloneIds = array_column($standaloneItems, 'id');
        $eventItemIds  = array_column($eventItems, 'id');

        $files          = (new CreativeFileModel())->getGroupedByItems($standaloneIds);
        $realisasi      = (new CreativeRealisasiModel())->getGroupedByItems($standaloneIds);
        $eventRealisasi = empty($eventItemIds) ? [] : (new EventCreativeRealisasiModel())->getGroupedByItems($eventItemIds);
        $insights       = (new CreativeInsightModel())->getGroupedByItems($standaloneIds);

        // Assign _tab: 'realisasi' if has any realisasi, else 'review'
        foreach ($byTipe as $tipe => &$tipeItems) {
            foreach ($tipeItems as &$item) {
                if ($item['_source'] === 's') {
                    $hasReal = ($realisasi[$item['id']]['total'] ?? 0) > 0;
                } else {
                    $hasReal = ($eventRealisasi[$item['id']]['total'] ?? 0) > 0;
                }
                $item['_tab'] = $hasReal ? 'realisasi' : 'review';
            }
            unset($item);
        }
        unset($tipeItems);

        $totalBudgetStandalone = (new CreativeItemModel())->getTotalBudget();
        $totalBudgetEvent      = array_sum(array_column($eventItems, 'budget'));
        $totalBudget           = $totalBudgetStandalone + $totalBudgetEvent;
        $totalRealisasi        = empty($standaloneIds) ? 0 : (new CreativeRealisasiModel())->getTotalRealisasi($standaloneIds);

        $tabCounts = ['review' => 0, 'realisasi' => 0];
        foreach ($byTipe as $tipeItems) {
            foreach ($tipeItems as $item) {
                $tabCounts[$item['_tab']] = ($tabCounts[$item['_tab']] ?? 0) + 1;
            }
        }

        $canApprove = in_array($user['role'] ?? '', ['admin', 'manager']);

        return view('creative_main/index', [
            'user'           => $user,
            'byTipe'         => $byTipe,
            'standaloneItems'=> $standaloneItems,
            'eventItems'     => $eventItems,
            'files'          => $files,
            'realisasi'      => $realisasi,
            'insights'       => $insights,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'canEdit'        => $this->canEditMenu('creative_main'),
            'canApprove'     => $canApprove,
            'tabCounts'      => $tabCounts,
        ]);
    }

    public function monthly()
    {
        if (!$this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        [$year, $month] = explode('-', $bulan);
        $year  = (int)$year;
        $month = (int)$month;

        $prevBulan = date('Y-m', mktime(0, 0, 0, $month - 1, 1, $year));
        $nextBulan = date('Y-m', mktime(0, 0, 0, $month + 1, 1, $year));

        $standaloneItems = (new CreativeItemModel())->getAll();
        foreach ($standaloneItems as &$si) { $si['_source'] = 's'; }
        unset($si);

        $eventItems = (new EventCreativeItemModel())->getAllWithEvents();
        foreach ($eventItems as &$ei) { $ei['_source'] = 'e'; }
        unset($ei);

        $standaloneIds = array_column($standaloneItems, 'id');
        $eventItemIds  = array_column($eventItems, 'id');

        $sReal = empty($standaloneIds) ? [] : (new CreativeRealisasiModel())->getMonthlyGrouped($bulan, $standaloneIds);
        $eReal = empty($eventItemIds)  ? [] : (new EventCreativeRealisasiModel())->getMonthlyGrouped($bulan, $eventItemIds);
        $sIns  = empty($standaloneIds) ? [] : (new CreativeInsightModel())->getMonthlyGrouped($bulan, $standaloneIds);
        $eIns  = empty($eventItemIds)  ? [] : (new EventCreativeInsightModel())->getMonthlyGrouped($bulan, $eventItemIds);

        $allItems = array_merge($standaloneItems, $eventItems);

        $totalBudget    = array_sum(array_column($allItems, 'budget'));
        $totalRealisasi = array_sum(array_map(fn($g) => $g['total'] ?? 0, array_merge($sReal, $eReal)));
        $totalReach     = array_sum(array_map(fn($g) => $g['max_reach'] ?? 0, array_merge($sIns, $eIns)));
        $totalImpr      = array_sum(array_map(fn($g) => $g['max_impressions'] ?? 0, array_merge($sIns, $eIns)));
        $totalFollowers = array_sum(array_map(fn($g) => $g['total_followers_gained'] ?? 0, array_merge($sIns, $eIns)));

        $statusCounts = [];
        foreach ($allItems as $item) {
            $s = $item['status'] ?? 'draft';
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }

        $activeStSet = array_flip(array_unique(array_merge(array_keys($sReal), array_keys($sIns))));
        $activeEvSet = array_flip(array_unique(array_merge(array_keys($eReal), array_keys($eIns))));
        $activeCount = count($activeStSet) + count($activeEvSet);

        $rows = [];
        foreach ($allItems as $item) {
            $iid  = (int)$item['id'];
            $isSt = $item['_source'] === 's';

            $realMonth   = $isSt ? ($sReal[$iid] ?? null) : ($eReal[$iid] ?? null);
            $insMonth    = $isSt ? ($sIns[$iid]  ?? null) : ($eIns[$iid]  ?? null);
            $hasActivity = $isSt ? isset($activeStSet[$iid]) : isset($activeEvSet[$iid]);

            $rows[] = compact('item', 'realMonth', 'insMonth', 'hasActivity');
        }

        usort($rows, fn($a, $b) =>
            $b['hasActivity'] <=> $a['hasActivity'] ?: strcmp($a['item']['tipe'], $b['item']['tipe'])
        );

        $chartItems      = [];
        $chartBudget     = [];
        $chartRealMonth  = [];
        foreach ($rows as $r) {
            $budget  = (int)$r['item']['budget'];
            $realTot = (int)($r['realMonth']['total'] ?? 0);
            if ($budget > 0 || $realTot > 0) {
                $chartItems[]     = mb_strimwidth($r['item']['nama'], 0, 30, '…');
                $chartBudget[]    = $budget;
                $chartRealMonth[] = $realTot;
            }
        }

        $insightLabels = [];
        $insightReach  = [];
        $insightImpr   = [];
        foreach ($rows as $r) {
            if ($r['insMonth'] !== null) {
                $insightLabels[] = mb_strimwidth($r['item']['nama'], 0, 30, '…');
                $insightReach[]  = (int)($r['insMonth']['max_reach']       ?? 0);
                $insightImpr[]   = (int)($r['insMonth']['max_impressions'] ?? 0);
            }
        }

        return view('creative_main/monthly', [
            'user'            => $this->currentUser(),
            'bulan'           => $bulan,
            'year'            => $year,
            'month'           => $month,
            'prevBulan'       => $prevBulan,
            'nextBulan'       => $nextBulan,
            'rows'            => $rows,
            'activeCount'     => $activeCount,
            'totalBudget'     => $totalBudget,
            'totalRealisasi'  => $totalRealisasi,
            'totalReach'      => $totalReach,
            'totalImpressions'=> $totalImpr,
            'totalFollowers'  => $totalFollowers,
            'statusCounts'    => $statusCounts,
            'chartItems'      => $chartItems,
            'chartBudget'     => $chartBudget,
            'chartRealMonth'  => $chartRealMonth,
            'insightLabels'   => $insightLabels,
            'insightReach'    => $insightReach,
            'insightImpr'     => $insightImpr,
        ]);
    }

    public function store()
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $post      = $this->request->getPost();
        $isDigital = ($post['tipe'] === 'digital');

        $newId = (new CreativeItemModel())->insert([
            'tipe'         => $post['tipe'],
            'nama'         => $post['nama'],
            'platform'     => $isDigital ? ($post['platform'] ?? null) : null,
            'tanggal_take' => $isDigital ? ($post['tanggal_take'] ?: null) : null,
            'jam_take'     => $isDigital ? ($post['jam_take'] ?: null) : null,
            'pic'          => $isDigital ? ($post['pic'] ?: null) : null,
            'deskripsi'    => $post['deskripsi'] ?? null,
            'budget'       => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'urutan'       => (int)($post['urutan'] ?? 0),
            'created_by'   => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'creative_standalone', (string)$newId, $post['nama'], []);

        return redirect()->to('/creative')->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $post      = $this->request->getPost();
        $isDigital = ($post['tipe'] === 'digital');

        (new CreativeItemModel())->update($id, [
            'tipe'         => $post['tipe'],
            'nama'         => $post['nama'],
            'platform'     => $isDigital ? ($post['platform'] ?? null) : null,
            'tanggal_take' => $isDigital ? ($post['tanggal_take'] ?: null) : null,
            'jam_take'     => $isDigital ? ($post['jam_take'] ?: null) : null,
            'pic'          => $isDigital ? ($post['pic'] ?: null) : null,
            'deskripsi'    => $post['deskripsi'] ?? null,
            'budget'       => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'urutan'       => (int)($post['urutan'] ?? 0),
        ]);

        ActivityLog::write('update', 'creative_standalone', (string)$id, $post['nama'], []);

        return redirect()->to('/creative')->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $item         = (new CreativeItemModel())->find($id);
        $realisasiRows= (new CreativeRealisasiModel())->where('creative_item_id', $id)->findAll();
        $fileRows     = (new CreativeFileModel())->where('creative_item_id', $id)->findAll();
        $insightRows  = (new CreativeInsightModel())->where('creative_item_id', $id)->findAll();

        $db = db_connect();
        $db->transStart();
        (new CreativeInsightModel())->where('creative_item_id', $id)->delete();
        (new CreativeFileModel())->where('creative_item_id', $id)->delete();
        (new CreativeRealisasiModel())->where('creative_item_id', $id)->delete();
        (new CreativeItemModel())->delete($id);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->to('/creative')->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        // Unlink physical files after successful transaction
        foreach ($realisasiRows as $r) {
            $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($r[$col] && file_exists($dir . $r[$col])) unlink($dir . $r[$col]);
            }
        }
        foreach ($fileRows as $f) {
            $path = FCPATH . 'uploads/creative-standalone/' . $id . '/' . $f['file_name'];
            if (file_exists($path)) unlink($path);
        }
        foreach ($insightRows as $ins) {
            if ($ins['file_name']) {
                $path = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/' . $ins['file_name'];
                if (file_exists($path)) unlink($path);
            }
        }

        ActivityLog::write('delete', 'creative_standalone', (string)$id, $item['nama'] ?? '', []);

        return redirect()->to('/creative')->with('success', 'Item berhasil dihapus.');
    }

    public function uploadFile(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'File tidak valid.');
        }

        $ext  = $file->getClientExtension();
        $name = 'creative_s_' . $id . '_' . time() . '.' . $ext;
        $dir  = $this->fileDir($id);
        $file->move($dir, $name);

        (new CreativeFileModel())->insert([
            'creative_item_id' => $id,
            'file_name'        => $name,
            'original_name'    => $file->getClientName(),
            'uploaded_by'      => $this->currentUser()['id'],
        ]);

        ActivityLog::write('upload', 'creative_standalone', (string)$id, $name, []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'File berhasil diupload.');
    }

    public function deleteFile(int $id, int $fileId)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $fileModel = new CreativeFileModel();
        $row       = $fileModel->find($fileId);
        if ($row) {
            $path = FCPATH . 'uploads/creative-standalone/' . $id . '/' . $row['file_name'];
            if (file_exists($path)) unlink($path);
            $fileModel->delete($fileId);
        }

        ActivityLog::write('delete_file', 'creative_standalone', (string)$fileId, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'File berhasil dihapus.');
    }

    public function updateStatus(int $id)
    {
        $user   = $this->currentUser();
        $canApprove = in_array($user['role'] ?? '', ['admin', 'manager']);

        if (!$this->canEditMenu('creative_main') && !$canApprove) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $status = $this->request->getPost('status');
        $validStatuses = ['draft', 'review', 'approved', 'revision'];
        if (!in_array($status, $validStatuses)) {
            return redirect()->to('/creative')->with('error', 'Status tidak valid.');
        }

        (new CreativeItemModel())->updateStatus($id, $status);

        ActivityLog::write('update_status', 'creative_standalone', (string)$id, $status, []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Status berhasil diperbarui.');
    }

    public function storeRealisasi(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $post = $this->request->getPost();
        $dir  = $this->realisasiDir($id);

        $fileName = null; $origName = null;
        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext      = $file->getClientExtension();
            $fileName = 'real_s_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            $origName = $file->getClientName();
            $file->move($dir, $fileName);
        }

        $stFileName = null; $stOrigName = null;
        $stFile = $this->request->getFile('serah_terima');
        if ($stFile && $stFile->isValid() && !$stFile->hasMoved()) {
            $ext        = $stFile->getClientExtension();
            $stFileName = 'st_s_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            $stOrigName = $stFile->getClientName();
            $stFile->move($dir, $stFileName);
        }

        $btFileName = null; $btOrigName = null;
        $btFile = $this->request->getFile('bukti_terpasang');
        if ($btFile && $btFile->isValid() && !$btFile->hasMoved()) {
            $ext        = $btFile->getClientExtension();
            $btFileName = 'bt_s_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            $btOrigName = $btFile->getClientName();
            $btFile->move($dir, $btFileName);
        }

        (new CreativeRealisasiModel())->insert([
            'creative_item_id'              => $id,
            'tanggal'                       => $post['tanggal'],
            'nilai'                         => (int)str_replace([',', '.', ' '], '', $post['nilai'] ?? 0),
            'nama_influencer'               => $post['nama_influencer'] ?? null,
            'file_name'                     => $fileName,
            'original_name'                 => $origName,
            'serah_terima_file_name'        => $stFileName,
            'serah_terima_original_name'    => $stOrigName,
            'bukti_terpasang_file_name'     => $btFileName,
            'bukti_terpasang_original_name' => $btOrigName,
            'catatan'                       => $post['catatan'] ?? null,
            'created_by'                    => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'creative_standalone_realisasi', (string)$id, $post['tanggal'], []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $id, int $rid)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $model = new CreativeRealisasiModel();
        $row   = $model->find($rid);

        $db = db_connect();
        $db->transStart();
        $model->delete($rid);
        $db->transComplete();

        if ($db->transStatus() && $row) {
            $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($row[$col] && file_exists($dir . $row[$col])) unlink($dir . $row[$col]);
            }
        }

        ActivityLog::write('delete', 'creative_standalone_realisasi', (string)$rid, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Realisasi berhasil dihapus.');
    }

    public function storeInsight(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $post     = $this->request->getPost();
        $dir      = $this->insightDir($id);
        $item     = (new CreativeItemModel())->find($id);
        $fileName = null;
        $origName = null;

        $file = $this->request->getFile('screenshot');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext      = $file->getClientExtension();
            $fileName = 'insight_s_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            $origName = $file->getClientName();
            $file->move($dir, $fileName);
        }

        (new CreativeInsightModel())->insert([
            'creative_item_id' => $id,
            'tanggal'          => $post['tanggal'],
            'platform'         => $post['platform'] ?? ($item['platform'] ?? null),
            'reach'            => (int)($post['reach']            ?? 0),
            'impressions'      => (int)($post['impressions']      ?? 0),
            'views'            => (int)($post['views']            ?? 0),
            'likes'            => (int)($post['likes']            ?? 0),
            'comments'         => (int)($post['comments']         ?? 0),
            'shares'           => (int)($post['shares']           ?? 0),
            'saves'            => (int)($post['saves']            ?? 0),
            'followers_gained' => (int)($post['followers_gained'] ?? 0),
            'file_name'        => $fileName,
            'original_name'    => $origName,
            'catatan'          => $post['catatan'] ?? null,
            'created_by'       => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'creative_standalone_insight', (string)$id, $post['tanggal'], []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Insight berhasil disimpan.');
    }

    public function deleteInsight(int $id, int $iid)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $model = new CreativeInsightModel();
        $row   = $model->find($iid);
        $model->delete($iid);

        if ($row && $row['file_name']) {
            $path = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/' . $row['file_name'];
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'creative_standalone_insight', (string)$iid, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Insight berhasil dihapus.');
    }
}
