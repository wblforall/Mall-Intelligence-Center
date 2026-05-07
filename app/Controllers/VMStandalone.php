<?php

namespace App\Controllers;

use App\Models\EventVMModel;
use App\Models\EventVMRealisasiModel;
use App\Libraries\ActivityLog;

class VMStandalone extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('vm_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $vmModel = new EventVMModel();

        $standaloneItems = $vmModel->getStandalone();
        foreach ($standaloneItems as &$it) { $it['source'] = 'standalone'; $it['event_name'] = null; $it['event_mall'] = null; }
        unset($it);

        $eventItems = $vmModel->getAllWithEvent();
        foreach ($eventItems as &$it) { $it['source'] = 'event'; }
        unset($it);

        $items     = array_merge($standaloneItems, $eventItems);
        $allIds    = array_column($items, 'id');
        $realisasi = (new EventVMRealisasiModel())->getGroupedByItems($allIds);

        $totalBudget    = array_sum(array_column($items, 'budget'));
        $totalRealisasi = array_sum(array_column($realisasi, 'total'));
        $totalPct       = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : 0;

        return view('vm/standalone', [
            'user'           => $this->currentUser(),
            'items'          => $items,
            'realisasi'      => $realisasi,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'totalPct'       => $totalPct,
            'canEdit'        => $this->canEditMenu('vm_main'),
        ]);
    }

    public function monthly()
    {
        if (! $this->canViewMenu('vm_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        [$year, $month] = explode('-', $bulan);
        $year  = (int)$year;
        $month = (int)$month;

        $prevBulan = date('Y-m', mktime(0, 0, 0, $month - 1, 1, $year));
        $nextBulan = date('Y-m', mktime(0, 0, 0, $month + 1, 1, $year));

        $vmModel = new EventVMModel();

        $standaloneItems = $vmModel->getStandalone();
        foreach ($standaloneItems as &$it) { $it['source'] = 'standalone'; $it['event_name'] = null; $it['event_mall'] = null; }
        unset($it);

        $eventItems = $vmModel->getAllWithEvent();
        foreach ($eventItems as &$it) { $it['source'] = 'event'; }
        unset($it);

        $allItems = array_merge($standaloneItems, $eventItems);
        $allIds   = array_column($allItems, 'id');

        $realModel      = new EventVMRealisasiModel();
        $realMonth      = empty($allIds) ? [] : $realModel->getMonthlyGrouped($bulan, $allIds);

        $totalBudget    = array_sum(array_column($allItems, 'budget'));
        $totalRealMonth = array_sum(array_map(fn($g) => $g['total'] ?? 0, $realMonth));
        $activeCount    = count($realMonth);

        $rows = [];
        foreach ($allItems as $item) {
            $iid  = (int)$item['id'];
            $rMon = $realMonth[$iid] ?? null;
            $rows[] = [
                'item'        => $item,
                'realMonth'   => $rMon,
                'hasActivity' => $rMon !== null,
            ];
        }

        usort($rows, fn($a, $b) => $b['hasActivity'] <=> $a['hasActivity']);

        $chartItems      = [];
        $chartBudget     = [];
        $chartRealMonth  = [];
        foreach ($rows as $r) {
            $budget  = (int)$r['item']['budget'];
            $realTot = (int)($r['realMonth']['total'] ?? 0);
            if ($budget > 0 || $realTot > 0) {
                $chartItems[]     = mb_strimwidth($r['item']['nama_item'], 0, 30, '…');
                $chartBudget[]    = $budget;
                $chartRealMonth[] = $realTot;
            }
        }

        return view('vm/monthly', [
            'user'           => $this->currentUser(),
            'bulan'          => $bulan,
            'year'           => $year,
            'month'          => $month,
            'prevBulan'      => $prevBulan,
            'nextBulan'      => $nextBulan,
            'rows'           => $rows,
            'activeCount'    => $activeCount,
            'totalBudget'    => $totalBudget,
            'totalRealMonth' => $totalRealMonth,
            'chartItems'     => $chartItems,
            'chartBudget'    => $chartBudget,
            'chartRealMonth' => $chartRealMonth,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('vm_main')) return redirect()->to('/vm')->with('error', 'Akses ditolak.');

        $post  = $this->request->getPost();
        $newId = (new EventVMModel())->insert([
            'event_id'            => null,
            'nama_item'           => $post['nama_item'],
            'deskripsi_referensi' => $post['deskripsi_referensi'] ?? null,
            'budget'              => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'             => $post['catatan'] ?? null,
            'tanggal_deadline'    => $post['tanggal_deadline'] ?: null,
            'created_by'          => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'vm_standalone', (string)$newId, $post['nama_item'], ['budget' => $post['budget'] ?? 0]);

        return redirect()->to('/vm')->with('success', 'Item dekorasi berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('vm_main')) return redirect()->to('/vm')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new EventVMModel())->update($id, [
            'nama_item'           => $post['nama_item'],
            'deskripsi_referensi' => $post['deskripsi_referensi'] ?? null,
            'budget'              => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'             => $post['catatan'] ?? null,
            'tanggal_deadline'    => $post['tanggal_deadline'] ?: null,
        ]);
        ActivityLog::write('update', 'vm_standalone', (string)$id, $post['nama_item'], ['budget' => $post['budget'] ?? 0]);

        return redirect()->to('/vm')->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('vm_main')) return redirect()->to('/vm')->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $item = (new EventVMModel())->find($id);

        $db->transStart();
        (new EventVMRealisasiModel())->where('vm_item_id', $id)->delete();
        (new EventVMModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('/vm')->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        ActivityLog::write('delete', 'vm_standalone', (string)$id, $item['nama_item'] ?? '');
        return redirect()->to('/vm')->with('success', 'Item berhasil dihapus.');
    }

    public function storeRealisasi(int $itemId)
    {
        if (! $this->canEditMenu('vm_main')) return redirect()->to('/vm')->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $fotoName = null;
        $fotoOrig = null;
        $file     = $this->request->getFile('foto');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $uploadPath = FCPATH . 'uploads/vm/standalone/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fotoName = 'real_' . time() . '_' . $file->getRandomName();
            $fotoOrig = $file->getClientName();
            $file->move($uploadPath, $fotoName);
        }

        $jumlah = (int)str_replace([',', '.', ' '], '', $post['jumlah'] ?? 0);
        (new EventVMRealisasiModel())->insert([
            'vm_item_id'         => $itemId,
            'event_id'           => null,
            'tanggal'            => $post['tanggal'],
            'jumlah'             => $jumlah,
            'catatan'            => $post['catatan'] ?? null,
            'foto_file_name'     => $fotoName,
            'foto_original_name' => $fotoOrig,
            'created_by'         => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'vm_standalone_realisasi', (string)$itemId, 'Realisasi VM Standalone', ['tanggal' => $post['tanggal'], 'jumlah' => $jumlah]);

        return redirect()->to('/vm#item-' . $itemId)->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $itemId, int $rid)
    {
        if (! $this->canEditMenu('vm_main')) return redirect()->to('/vm')->with('error', 'Akses ditolak.');

        $entry = (new EventVMRealisasiModel())->find($rid);
        if ($entry && $entry['foto_file_name']) {
            $path = FCPATH . 'uploads/vm/standalone/' . $entry['foto_file_name'];
            if (file_exists($path)) unlink($path);
        }
        (new EventVMRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'vm_standalone_realisasi', (string)$rid, 'Realisasi VM Standalone', ['item_id' => $itemId]);

        return redirect()->to('/vm#item-' . $itemId)->with('success', 'Realisasi berhasil dihapus.');
    }
}
