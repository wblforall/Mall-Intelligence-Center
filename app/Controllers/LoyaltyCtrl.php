<?php

namespace App\Controllers;

use App\Models\LoyaltyProgramModel;
use App\Models\LoyaltyRealisasiModel;
use App\Models\LoyaltyHadiahItemModel;
use App\Models\LoyaltyHadiahRealisasiModel;
use App\Models\LoyaltyVoucherItemModel;
use App\Models\LoyaltyVoucherRealisasiModel;
use App\Models\EventLoyaltyModel;
use App\Models\EventLoyaltyRealisasiModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Libraries\ActivityLog;

class LoyaltyCtrl extends BaseController
{
    private function mergePrograms(): array
    {
        $standalone = (new LoyaltyProgramModel())->getAll();
        foreach ($standalone as &$p) { $p['source'] = 'standalone'; $p['event_name'] = null; }
        unset($p);

        $eventPrograms = (new EventLoyaltyModel())->getAllWithEvent();
        foreach ($eventPrograms as &$p) {
            $p['source'] = 'event';
            $p['status'] = $p['completion_id'] ? 'inactive' : 'active';
        }
        unset($p);

        return array_merge($standalone, $eventPrograms);
    }

    private function mergeRealisasi(array $programs): array
    {
        $standaloneIds = [];
        $eventIds      = [];
        foreach ($programs as $p) {
            if ($p['source'] === 'standalone') $standaloneIds[] = $p['id'];
            else                               $eventIds[]      = $p['id'];
        }

        $sRealisasi = (new LoyaltyRealisasiModel())->getGroupedByPrograms($standaloneIds);
        $eRealisasi = (new EventLoyaltyRealisasiModel())->getGroupedByPrograms($eventIds);

        $merged = [];
        foreach ($sRealisasi as $id => $data) { $merged['s_' . $id] = $data; }
        foreach ($eRealisasi as $id => $data) { $merged['e_' . $id] = $data; }
        return $merged;
    }

    private function syncBudget(int $programId): void
    {
        $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $programId)->findAll();
        $hadiahItems  = (new LoyaltyHadiahItemModel())->where('program_id', $programId)->findAll();

        $budget = 0;
        foreach ($voucherItems as $vi) { $budget += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher']; }
        foreach ($hadiahItems  as $hi) { $budget += (int)$hi['stok']              * (int)$hi['nilai_satuan'];  }

        (new LoyaltyProgramModel())->update($programId, ['budget' => $budget]);
    }

    public function index()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $programs  = $this->mergePrograms();
        $realisasi = $this->mergeRealisasi($programs);

        $standaloneProgIds = array_column(
            array_filter($programs, fn($p) => $p['source'] === 'standalone'),
            'id'
        );

        $hadiahItems     = (new LoyaltyHadiahItemModel())->getByPrograms($standaloneProgIds);
        $allHadiahIds    = array_merge(...array_map(fn($items) => array_column($items, 'id'), $hadiahItems ?: [[]]));
        $hadiahRealisasi = (new LoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        $voucherItems     = (new LoyaltyVoucherItemModel())->getByPrograms($standaloneProgIds);
        $allVoucherIds    = array_merge(...array_map(fn($items) => array_column($items, 'id'), $voucherItems ?: [[]]));
        $voucherRealisasi = (new LoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);

        // KPI: member from realisasi.jumlah, voucher terpakai from per-item data
        $totalMemberKpi   = 0;
        $targetMemberKpi  = 0;
        $totalTerpakaiKpi = 0;
        foreach ($programs as $p) {
            if ($p['status'] !== 'active') continue;
            $key = ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
            $totalMemberKpi  += (int)($realisasi[$key]['total'] ?? 0);
            $targetMemberKpi += (int)($p['target_peserta'] ?? 0);
        }
        foreach ($standaloneProgIds as $pid) {
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $totalTerpakaiKpi += (int)($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0);
            }
        }

        $totalBudget = array_sum(array_column($programs, 'budget'));
        $activeCount = count(array_filter($programs, fn($p) => $p['status'] === 'active'));

        return view('loyalty_program/index', [
            'user'             => $this->currentUser(),
            'programs'         => $programs,
            'realisasi'        => $realisasi,
            'hadiahItems'      => $hadiahItems,
            'hadiahRealisasi'  => $hadiahRealisasi,
            'voucherItems'     => $voucherItems,
            'voucherRealisasi' => $voucherRealisasi,
            'totalBudget'      => $totalBudget,
            'activeCount'      => $activeCount,
            'totalMemberKpi'   => $totalMemberKpi,
            'targetMemberKpi'  => $targetMemberKpi,
            'totalTerpakaiKpi' => $totalTerpakaiKpi,
            'canEdit'          => $this->canEditMenu('loyalty_main'),
        ]);
    }

    // ── Hadiah items ─────────────────────────────────────────────────────────

    private function assertNotLocked(int $programId): bool
    {
        return ! (new LoyaltyProgramModel())->isLocked($programId);
    }

    public function storeHadiahItem(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $hadiahModel = new LoyaltyHadiahItemModel();
        $hadiahModel->insert([
            'program_id'   => $programId,
            'nama_hadiah'  => $post['nama_hadiah'],
            'stok'         => (int)($post['stok'] ?? 0),
            'nilai_satuan' => $clean($post['nilai_satuan']),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        $this->syncBudget($programId);
        ActivityLog::write('create', 'loyalty_hadiah_item', (string)$hadiahModel->getInsertID(), $post['nama_hadiah'], ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Item hadiah berhasil ditambahkan.');
    }

    public function deleteHadiahItem(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $hadiahModel = new LoyaltyHadiahItemModel();
        $item = $hadiahModel->find($itemId);
        (new LoyaltyHadiahRealisasiModel())->where('item_id', $itemId)->delete();
        $hadiahModel->delete($itemId);
        $this->syncBudget($programId);
        ActivityLog::write('delete', 'loyalty_hadiah_item', (string)$itemId, $item['nama_hadiah'] ?? '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Item hadiah dihapus.');
    }

    public function storeHadiahRealisasi(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to('/loyalty')->with('error', 'Tanggal wajib diisi.');
        $hrModel = new LoyaltyHadiahRealisasiModel();
        $hrModel->insert([
            'program_id'       => $programId,
            'item_id'          => $itemId,
            'tanggal'          => $post['tanggal'],
            'jumlah_dibagikan' => (int)($post['jumlah_dibagikan'] ?? 0),
            'catatan'          => $post['catatan'] ?? null,
            'created_by'       => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_hadiah_realisasi', (string)$hrModel->getInsertID(), $post['tanggal'], ['program_id' => $programId, 'item_id' => $itemId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi hadiah disimpan.');
    }

    public function deleteHadiahRealisasi(int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        (new LoyaltyHadiahRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'loyalty_hadiah_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Voucher items ─────────────────────────────────────────────────────────

    public function storeVoucherItem(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post       = $this->request->getPost();
        $clean      = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $penyerapan = ($post['target_penyerapan'] ?? '') !== '' ? (float)$post['target_penyerapan'] : null;
        $voucherModel = new LoyaltyVoucherItemModel();
        $voucherModel->insert([
            'program_id'        => $programId,
            'nama_voucher'      => $post['nama_voucher'],
            'nilai_voucher'     => $clean($post['nilai_voucher']),
            'total_diterbitkan' => (int)($post['total_diterbitkan'] ?? 0),
            'target_penyerapan' => $penyerapan,
            'catatan'           => $post['catatan'] ?? null,
            'created_by'        => $this->currentUser()['id'],
        ]);
        $this->syncBudget($programId);
        ActivityLog::write('create', 'loyalty_voucher_item', (string)$voucherModel->getInsertID(), $post['nama_voucher'], ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function deleteVoucherItem(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $voucherModel = new LoyaltyVoucherItemModel();
        $item = $voucherModel->find($itemId);
        (new LoyaltyVoucherRealisasiModel())->where('item_id', $itemId)->delete();
        $voucherModel->delete($itemId);
        $this->syncBudget($programId);
        ActivityLog::write('delete', 'loyalty_voucher_item', (string)$itemId, $item['nama_voucher'] ?? '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Voucher dihapus.');
    }

    public function storeVoucherRealisasi(int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to('/loyalty')->with('error', 'Tanggal wajib diisi.');
        $vrModel = new LoyaltyVoucherRealisasiModel();
        $vrModel->insert([
            'program_id' => $programId,
            'item_id'    => $itemId,
            'tanggal'    => $post['tanggal'],
            'tersebar'   => (int)($post['tersebar'] ?? 0),
            'terpakai'   => (int)($post['terpakai'] ?? 0),
            'catatan'    => $post['catatan'] ?? null,
            'created_by' => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_voucher_realisasi', (string)$vrModel->getInsertID(), $post['tanggal'], ['program_id' => $programId, 'item_id' => $itemId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi voucher disimpan.');
    }

    public function deleteVoucherRealisasi(int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        (new LoyaltyVoucherRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'loyalty_voucher_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Program CRUD ──────────────────────────────────────────────────────────

    public function storeProgram()
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        $post        = $this->request->getPost();
        $progModel   = new LoyaltyProgramModel();
        $progModel->insert([
            'nama_program'    => $post['nama_program'],
            'tanggal_mulai'   => $post['tanggal_mulai']  ?? null ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?? null ?: null,
            'jam_mulai'       => $post['jam_mulai']       ?? null ?: null,
            'jam_selesai'     => $post['jam_selesai']     ?? null ?: null,
            'mekanisme'       => $post['mekanisme']       ?? null,
            'target_peserta'  => ($post['target_peserta'] ?? '') !== '' ? (int)$post['target_peserta'] : null,
            'budget'          => 0,
            'status'          => 'active',
            'catatan'         => $post['catatan'] ?? null,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_program', (string)$progModel->getInsertID(), $post['nama_program']);
        return redirect()->to('/loyalty')->with('success', 'Program loyalty berhasil ditambahkan.');
    }

    public function updateProgram(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        (new LoyaltyProgramModel())->update($id, [
            'nama_program'    => $post['nama_program'],
            'tanggal_mulai'   => $post['tanggal_mulai']  ?? null ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?? null ?: null,
            'jam_mulai'       => $post['jam_mulai']       ?? null ?: null,
            'jam_selesai'     => $post['jam_selesai']     ?? null ?: null,
            'mekanisme'       => $post['mekanisme']       ?? null,
            'target_peserta'      => ($post['target_peserta'] ?? '') !== '' ? (int)$post['target_peserta'] : null,
            'target_member_aktif' => ($post['target_member_aktif'] ?? '') !== '' ? (int)$post['target_member_aktif'] : null,
            'catatan'             => $post['catatan'] ?? null,
        ]);
        ActivityLog::write('update', 'loyalty_program', (string)$id, $post['nama_program']);
        return redirect()->to('/loyalty')->with('success', 'Program berhasil diperbarui.');
    }

    public function deleteProgram(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci.');
        $prog = (new LoyaltyProgramModel())->find($id);
        (new LoyaltyRealisasiModel())->where('program_id', $id)->delete();
        $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $id)->findAll();
        foreach ($voucherItems as $vi) { (new LoyaltyVoucherRealisasiModel())->where('item_id', $vi['id'])->delete(); }
        (new LoyaltyVoucherItemModel())->where('program_id', $id)->delete();
        $hadiahItems = (new LoyaltyHadiahItemModel())->where('program_id', $id)->findAll();
        foreach ($hadiahItems as $hi) { (new LoyaltyHadiahRealisasiModel())->where('item_id', $hi['id'])->delete(); }
        (new LoyaltyHadiahItemModel())->where('program_id', $id)->delete();
        (new LoyaltyProgramModel())->delete($id);
        ActivityLog::write('delete', 'loyalty_program', (string)$id, $prog['nama_program'] ?? '');
        return redirect()->to('/loyalty')->with('success', 'Program berhasil dihapus.');
    }

    public function toggleStatus(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if ((new LoyaltyProgramModel())->isLocked($id)) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Program terkunci, tidak bisa diubah.');
        (new LoyaltyProgramModel())->toggleStatus($id);
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'toggle_status']);
        return redirect()->to('/loyalty')->with('success', 'Status program diperbarui.');
    }

    public function lock(int $id)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        (new LoyaltyProgramModel())->lock($id, $this->currentUser()['id']);
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'lock']);
        return redirect()->to('/loyalty#program-s-'.$id)->with('success', 'Program berhasil dikunci.');
    }

    public function unlock(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/loyalty#program-s-'.$id)->with('error', 'Hanya admin yang bisa membuka kunci.');
        (new LoyaltyProgramModel())->unlock($id);
        ActivityLog::write('update', 'loyalty_program', (string)$id, '', ['action' => 'unlock']);
        return redirect()->to('/loyalty#program-s-'.$id)->with('success', 'Kunci program berhasil dibuka.');
    }

    public function storeRealisasi(int $programId)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        $post = $this->request->getPost();
        if (! (new LoyaltyProgramModel())->find($programId) || empty($post['tanggal'])) {
            return redirect()->to('/loyalty')->with('error', 'Data tidak valid.');
        }
        $realModel = new LoyaltyRealisasiModel();
        $realModel->insert([
            'program_id' => $programId,
            'tanggal'    => $post['tanggal'],
            'jumlah'       => (int)($post['jumlah'] ?? 0),
            'member_aktif' => (int)($post['member_aktif'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_realisasi', (string)$realModel->getInsertID(), $post['tanggal'], ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Realisasi berhasil disimpan.');
    }

    public function deleteRealisasi(int $programId, int $rid)
    {
        if (! $this->canEditMenu('loyalty_main')) return redirect()->to('/loyalty')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/loyalty#program-s-'.$programId)->with('error', 'Program terkunci.');
        (new LoyaltyRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'loyalty_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/loyalty#program-s-' . $programId)->with('success', 'Entri realisasi dihapus.');
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function summary()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $programs  = $this->mergePrograms();
        $programMap = [];
        foreach ($programs as $p) {
            $key = ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
            $programMap[$key] = $p;
        }

        $standaloneIds = array_column(array_filter($programs, fn($p) => $p['source'] === 'standalone'), 'id');
        $eventIds      = array_column(array_filter($programs, fn($p) => $p['source'] === 'event'), 'id');

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $sModel  = new LoyaltyRealisasiModel();
        $eModel  = new EventLoyaltyRealisasiModel();
        $vrModel = new LoyaltyVoucherRealisasiModel();
        $evrModel = new EventLoyaltyVoucherRealisasiModel();
        $ehrModel = new EventLoyaltyHadiahRealisasiModel();

        // Voucher items — standalone
        $voucherItemsGrouped = (new LoyaltyVoucherItemModel())->getByPrograms($standaloneIds);
        $allVoucherIds = [];
        foreach ($voucherItemsGrouped as $items) {
            foreach ($items as $vi) { $allVoucherIds[] = $vi['id']; }
        }

        // Voucher items — event programs
        $evoucherItemsGrouped = (new EventLoyaltyVoucherItemModel())->getByPrograms($eventIds);
        $allEvoucherIds = [];
        foreach ($evoucherItemsGrouped as $items) {
            foreach ($items as $vi) { $allEvoucherIds[] = $vi['id']; }
        }

        // Hadiah items — standalone
        $hadiahItemsGrouped = (new LoyaltyHadiahItemModel())->getByPrograms($standaloneIds);
        $allHadiahIds = [];
        foreach ($hadiahItemsGrouped as $items) {
            foreach ($items as $hi) { $allHadiahIds[] = $hi['id']; }
        }

        // Hadiah items — event programs
        $ehadiahItemsGrouped = (new EventLoyaltyHadiahItemModel())->getByPrograms($eventIds);
        $allEhadiahIds = [];
        foreach ($ehadiahItemsGrouped as $items) {
            foreach ($items as $hi) { $allEhadiahIds[] = $hi['id']; }
        }

        $sMonths = array_column($sModel->getAvailableMonths(), 'bulan');
        $eMonths = array_column($eModel->getAvailableMonths(), 'bulan');
        $monthList = array_unique(array_merge($sMonths, $eMonths));
        if (! in_array($bulan, $monthList)) $monthList[] = $bulan;
        rsort($monthList);

        // Monthly per-program (selected month)
        $sMonthly = $sModel->getMonthlyByPrograms($bulan, $standaloneIds);
        $eMonthly = $eModel->getMonthlyByPrograms($bulan, $eventIds);

        $monthlyData = [];
        foreach ($sMonthly as $id => $data) { $monthlyData['s_' . $id] = $data; }
        foreach ($eMonthly as $id => $data) { $monthlyData['e_' . $id] = $data; }

        // Standalone voucher & hadiah — monthly
        $vMonthly = $vrModel->getMonthlyByItems($bulan, $allVoucherIds);
        $hrModel  = new LoyaltyHadiahRealisasiModel();
        $hMonthly = $hrModel->getMonthlyByItems($bulan, $allHadiahIds);
        $hAllTime = [];
        foreach ($hrModel->getGroupedByItems($allHadiahIds) as $iid => $d) {
            $hAllTime[$iid] = (int)$d['total'];
        }

        // Event voucher & hadiah — monthly
        $evMonthly = $evrModel->getMonthlyByItems($bulan, $allEvoucherIds);
        $ehMonthly = $ehrModel->getMonthlyByItems($bulan, $allEhadiahIds);
        $ehAllTime = [];
        foreach ($ehrModel->getGroupedByItems($allEhadiahIds) as $iid => $d) {
            $ehAllTime[$iid] = (int)$d['total'];
        }

        // Aggregate voucher by program_id (for per-program table)
        $voucherByProgram = [];
        foreach ($voucherItemsGrouped as $progId => $items) {
            $voucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $vMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $voucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $voucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }
        $evoucherByProgram = [];
        foreach ($evoucherItemsGrouped as $progId => $items) {
            $evoucherByProgram[$progId] = ['total_tersebar' => 0, 'total_terpakai' => 0];
            foreach ($items as $vi) {
                $vd = $evMonthly[$vi['id']] ?? null;
                if ($vd) {
                    $evoucherByProgram[$progId]['total_tersebar'] += (int)$vd['total_tersebar'];
                    $evoucherByProgram[$progId]['total_terpakai'] += (int)$vd['total_terpakai'];
                }
            }
        }

        // Aggregate hadiah by program_id (for per-program table)
        $hadiahByProgram = [];
        foreach ($hadiahItemsGrouped as $progId => $items) {
            $hadiahByProgram[$progId] = 0;
            foreach ($items as $hi) {
                $hadiahByProgram[$progId] += (int)($hMonthly[$hi['id']] ?? 0);
            }
        }
        $ehadiahByProgram = [];
        foreach ($ehadiahItemsGrouped as $progId => $items) {
            $ehadiahByProgram[$progId] = 0;
            foreach ($items as $hi) {
                $ehadiahByProgram[$progId] += (int)($ehMonthly[$hi['id']] ?? 0);
            }
        }

        // KPIs for selected month
        $kpiMember      = 0;
        $kpiMemberAktif = 0;
        $kpiTersebar    = 0;
        $kpiTerpakai    = 0;
        $kpiHadiah      = 0;
        foreach ($monthlyData as $data) {
            $kpiMember      += (int)($data['total_jumlah']       ?? 0);
            $kpiMemberAktif += (int)($data['total_member_aktif'] ?? 0);
        }
        foreach ($vMonthly as $vd) {
            $kpiTersebar += (int)$vd['total_tersebar'];
            $kpiTerpakai += (int)$vd['total_terpakai'];
        }
        foreach ($evMonthly as $vd) {
            $kpiTersebar += (int)$vd['total_tersebar'];
            $kpiTerpakai += (int)$vd['total_terpakai'];
        }
        foreach ($hMonthly as $n)  { $kpiHadiah += (int)$n; }
        foreach ($ehMonthly as $n) { $kpiHadiah += (int)$n; }

        // Monthly trend — all months
        $sTotals  = $sModel->getAllMonthlyTotals($standaloneIds);
        $eTotals  = $eModel->getAllMonthlyTotals($eventIds);
        $vTotals  = $vrModel->getAllMonthlyTotals($allVoucherIds);
        $hTotals  = $hrModel->getAllMonthlyTotals($allHadiahIds);
        $evTotals = $evrModel->getAllMonthlyTotals($allEvoucherIds);
        $ehTotals = $ehrModel->getAllMonthlyTotals($allEhadiahIds);

        $empty = ['bulan' => '', 'total_jumlah' => 0, 'total_member_aktif' => 0, 'total_tersebar' => 0, 'total_terpakai' => 0, 'total_hadiah' => 0];
        $allMonthlyTotals = [];
        foreach ($sTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_jumlah']       += (int)$row['total_jumlah'];
            $allMonthlyTotals[$m]['total_member_aktif'] += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($eTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_jumlah']       += (int)$row['total_jumlah'];
            $allMonthlyTotals[$m]['total_member_aktif'] += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($vTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $allMonthlyTotals[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($evTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $allMonthlyTotals[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($hTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        foreach ($ehTotals as $row) {
            $m = $row['bulan'];
            if (! isset($allMonthlyTotals[$m])) $allMonthlyTotals[$m] = array_merge($empty, ['bulan' => $m]);
            $allMonthlyTotals[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        ksort($allMonthlyTotals);
        $currentYear      = date('Y');
        $allMonthlyTotals = array_filter($allMonthlyTotals, fn($row) => str_starts_with($row['bulan'], $currentYear));

        // Daily data for selected month
        $sDailyRows  = $sModel->getDailyForMonth($bulan, $standaloneIds);
        $eDailyRows  = $eModel->getDailyForMonth($bulan, $eventIds);
        $vDailyRows  = $vrModel->getDailyForMonth($bulan, $allVoucherIds);
        $evDailyRows = $evrModel->getDailyForMonth($bulan, $allEvoucherIds);

        $daysInMonth   = (int)date('t', strtotime($bulan . '-01'));
        $chartDates    = [];
        $dailyMember   = array_fill(0, $daysInMonth, 0);
        $dailyTersebar = array_fill(0, $daysInMonth, 0);
        $dailyTerpakai = array_fill(0, $daysInMonth, 0);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartDates[] = str_pad($d, 2, '0', STR_PAD_LEFT);
        }
        foreach ($sDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyMember[$idx] += (int)$row['jumlah'];
        }
        foreach ($eDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyMember[$idx] += (int)$row['jumlah'];
        }
        foreach ($vDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyTersebar[$idx] += (int)$row['tersebar'];
            $dailyTerpakai[$idx] += (int)$row['terpakai'];
        }
        foreach ($evDailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyTersebar[$idx] += (int)$row['tersebar'];
            $dailyTerpakai[$idx] += (int)$row['terpakai'];
        }

        $totalBudget       = array_sum(array_column($programs, 'budget'));
        $totalBudgetActive = array_sum(array_column(array_filter($programs, fn($p) => $p['status'] === 'active'), 'budget'));

        return view('loyalty_program/summary', [
            'user'                 => $this->currentUser(),
            'programs'             => $programs,
            'programMap'           => $programMap,
            'bulan'                => $bulan,
            'monthList'            => $monthList,
            'monthlyData'          => $monthlyData,
            'voucherByProgram'     => $voucherByProgram,
            'evoucherByProgram'    => $evoucherByProgram,
            'evoucherItemsGrouped' => $evoucherItemsGrouped,
            'evMonthly'            => $evMonthly,
            'voucherItemsGrouped'  => $voucherItemsGrouped,
            'vMonthly'             => $vMonthly,
            'hadiahItemsGrouped'   => $hadiahItemsGrouped,
            'hMonthly'             => $hMonthly,
            'hAllTime'             => $hAllTime,
            'ehadiahItemsGrouped'  => $ehadiahItemsGrouped,
            'ehMonthly'            => $ehMonthly,
            'ehAllTime'            => $ehAllTime,
            'allMonthlyTotals'     => array_values($allMonthlyTotals),
            'kpiMember'            => $kpiMember,
            'kpiMemberAktif'       => $kpiMemberAktif,
            'kpiTersebar'          => $kpiTersebar,
            'kpiTerpakai'          => $kpiTerpakai,
            'kpiHadiah'            => $kpiHadiah,
            'hadiahByProgram'      => $hadiahByProgram,
            'ehadiahByProgram'     => $ehadiahByProgram,
            'totalBudget'          => $totalBudget,
            'totalBudgetActive'    => $totalBudgetActive,
            'chartDates'           => $chartDates,
            'dailyMember'          => $dailyMember,
            'dailyTersebar'        => $dailyTersebar,
            'dailyTerpakai'        => $dailyTerpakai,
        ]);
    }

    // ── Detail per program ────────────────────────────────────────────────────

    public function detail(string $source, int $id)
    {
        if (! in_array($source, ['s', 'e'])) return redirect()->to('/loyalty');

        if ($source === 's') {
            $prog = (new LoyaltyProgramModel())->find($id);
            if (! $prog) return redirect()->to('/loyalty')->with('error', 'Program tidak ditemukan.');
            $prog['source']     = 'standalone';
            $prog['event_name'] = null;
            $prog['event_mall'] = null;

            $realModel  = new LoyaltyRealisasiModel();
            $allEntries = $realModel->getByProgram($id);
            $rMonthly   = $realModel->getAllMonthlyTotals([$id]);

            $voucherItems = (new LoyaltyVoucherItemModel())->where('program_id', $id)->orderBy('nilai_voucher', 'DESC')->findAll();
            $vItemIds     = array_column($voucherItems, 'id');
            $voucherReal  = (new LoyaltyVoucherRealisasiModel())->getGroupedByItems($vItemIds);
            $vMonthly     = (new LoyaltyVoucherRealisasiModel())->getAllMonthlyTotals($vItemIds);

            $hadiahItems  = (new LoyaltyHadiahItemModel())->where('program_id', $id)->orderBy('id')->findAll();
            $hItemIds     = array_column($hadiahItems, 'id');
            $hadiahReal   = (new LoyaltyHadiahRealisasiModel())->getGroupedByItems($hItemIds);
            $hMonthly     = (new LoyaltyHadiahRealisasiModel())->getAllMonthlyTotals($hItemIds);
        } else {
            $db  = \Config\Database::connect();
            $prog = $db->table('event_loyalty_programs elp')
                ->select('elp.*, e.name as event_name, e.mall as event_mall')
                ->join('events e', 'e.id = elp.event_id')
                ->where('elp.id', $id)
                ->get()->getRowArray();
            if (! $prog) return redirect()->to('/loyalty')->with('error', 'Program tidak ditemukan.');
            $prog['source'] = 'event';
            $prog['status'] = 'active';

            $realModel  = new EventLoyaltyRealisasiModel();
            $allEntries = $realModel->getByProgram($id);
            $rMonthly   = $realModel->getAllMonthlyTotals([$id]);

            $voucherItems = (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->orderBy('nilai_voucher', 'DESC')->findAll();
            $vItemIds     = array_column($voucherItems, 'id');
            $voucherReal  = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($vItemIds);
            $vMonthly     = (new EventLoyaltyVoucherRealisasiModel())->getAllMonthlyTotals($vItemIds);

            $hadiahItems  = (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->orderBy('id')->findAll();
            $hItemIds     = array_column($hadiahItems, 'id');
            $hadiahReal   = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($hItemIds);
            $hMonthly     = (new EventLoyaltyHadiahRealisasiModel())->getAllMonthlyTotals($hItemIds);
        }

        // All-time KPIs
        $totalMember      = array_sum(array_column($allEntries, 'jumlah'));
        $totalMemberAktif = array_sum(array_map(fn($r) => (int)($r['member_aktif'] ?? 0), $allEntries));
        $totalDiterbitkan = array_sum(array_column($voucherItems, 'total_diterbitkan'));
        $totalTersebar    = 0;
        $totalTerpakai    = 0;
        foreach ($voucherReal as $vd) {
            $totalTersebar += (int)$vd['total_tersebar'];
            $totalTerpakai += (int)$vd['total_terpakai'];
        }
        $totalHadiah = 0;
        $totalStok   = array_sum(array_column($hadiahItems, 'stok'));
        foreach ($hadiahReal as $hd) { $totalHadiah += (int)$hd['total']; }

        // Merge monthly trend
        $emptyRow = ['bulan' => '', 'total_member' => 0, 'total_aktif' => 0, 'total_tersebar' => 0, 'total_terpakai' => 0, 'total_hadiah' => 0];
        $trend = [];
        foreach ($rMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_member'] += (int)$row['total_jumlah'];
            $trend[$m]['total_aktif']  += (int)($row['total_member_aktif'] ?? 0);
        }
        foreach ($vMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_tersebar'] += (int)$row['total_tersebar'];
            $trend[$m]['total_terpakai'] += (int)$row['total_terpakai'];
        }
        foreach ($hMonthly as $row) {
            $m = $row['bulan'];
            if (! isset($trend[$m])) $trend[$m] = array_merge($emptyRow, ['bulan' => $m]);
            $trend[$m]['total_hadiah'] += (int)$row['total_dibagikan'];
        }
        ksort($trend);

        return view('loyalty_program/detail', [
            'user'             => $this->currentUser(),
            'source'           => $source,
            'prog'             => $prog,
            'allEntries'       => $allEntries,
            'voucherItems'     => $voucherItems,
            'voucherReal'      => $voucherReal,
            'hadiahItems'      => $hadiahItems,
            'hadiahReal'       => $hadiahReal,
            'totalMember'      => $totalMember,
            'totalMemberAktif' => $totalMemberAktif,
            'totalDiterbitkan' => $totalDiterbitkan,
            'totalTersebar'    => $totalTersebar,
            'totalTerpakai'    => $totalTerpakai,
            'totalHadiah'      => $totalHadiah,
            'totalStok'        => $totalStok,
            'trend'            => array_values($trend),
        ]);
    }
}
