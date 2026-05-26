<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\PromoMediaSpotModel;
use App\Models\PromoMediaUsageModel;

class PromoMediaCtrl extends BaseController
{
    private function checkView()
    {
        if (! $this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    private function checkEdit()
    {
        if (! $this->canEditMenu('creative_main')) {
            return redirect()->to('/creative/media-promo')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    private function checkApprove()
    {
        if (! $this->canApprovePromoMedia()) {
            return redirect()->to('/creative/media-promo')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->checkView()) return $r;

        $spotModel  = new PromoMediaSpotModel();
        $usageModel = new PromoMediaUsageModel();
        $usageModel->markDoneExpired();

        $today  = date('Y-m-d');
        $spots  = $spotModel->getAllWithStatus($today);
        $cetak  = array_filter($spots, fn($s) => in_array($s['tipe'], ['t_banner', 'hanging', 'sticker_lift', 'totem_stainless']));
        $digital = array_filter($spots, fn($s) => $s['tipe'] === 'digital');

        $depts = db_connect()->table('departments')->select('id, name')->orderBy('name')->get()->getResultArray();

        return view('creative/media_promo/index', [
            'user'       => $this->currentUser(),
            'cetak'      => array_values($cetak),
            'digital'    => array_values($digital),
            'today'      => $today,
            'canEdit'    => $this->canEditMenu('creative_main'),
            'canApprove' => $this->canApprovePromoMedia(),
            'depts'      => array_column($depts, 'name'),
        ]);
    }

    public function master()
    {
        if ($r = $this->checkEdit()) return $r;

        $spotModel = new PromoMediaSpotModel();
        $spots     = $spotModel->orderBy('tipe')->orderBy('kode')->findAll();
        $cetak     = array_values(array_filter($spots, fn($s) => in_array($s['tipe'], ['t_banner', 'hanging', 'sticker_lift', 'totem_stainless'])));
        $digital   = array_values(array_filter($spots, fn($s) => $s['tipe'] === 'digital'));

        return view('creative/media_promo/master', [
            'user'    => $this->currentUser(),
            'cetak'   => $cetak,
            'digital' => $digital,
        ]);
    }

    public function storeSpot()
    {
        if ($r = $this->checkEdit()) return $r;

        $post      = $this->request->getPost();
        $spotModel = new PromoMediaSpotModel();
        $tipe      = $post['tipe'];
        $back      = request()->getServer('HTTP_REFERER') ?: '/creative/media-promo/master';

        if (! $spotModel->isKodeUnique($post['kode'])) {
            return redirect()->to($back)->with('error', 'Kode sudah digunakan.');
        }

        $totalSlots = $tipe === 'digital' ? max(1, (int)($post['total_slots'] ?? 12)) : 1;

        $id = $spotModel->insert([
            'kode'        => strtoupper(trim($post['kode'])),
            'nama'        => $post['nama'],
            'tipe'        => $tipe,
            'area'        => $post['area'] ?? null,
            'ukuran'      => $post['ukuran'] ?? null,
            'total_slots' => $totalSlots,
            'catatan'     => $post['catatan'] ?? null,
            'created_by'  => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'promo_media_spots', (string)$id, $post['nama']);
        return redirect()->to('/creative/media-promo/master')->with('success', 'Titik media berhasil ditambahkan.');
    }

    public function updateSpot(int $id)
    {
        if ($r = $this->checkEdit()) return $r;

        $post      = $this->request->getPost();
        $spotModel = new PromoMediaSpotModel();
        $spot      = $spotModel->find($id);

        if (! $spotModel->isKodeUnique($post['kode'], $id)) {
            return redirect()->to('/creative/media-promo/master')->with('error', 'Kode sudah digunakan.');
        }

        $data = [
            'kode'    => strtoupper(trim($post['kode'])),
            'nama'    => $post['nama'],
            'area'    => $post['area'] ?? null,
            'ukuran'  => $post['ukuran'] ?? null,
            'catatan' => $post['catatan'] ?? null,
        ];
        if ($spot && $spot['tipe'] === 'digital') {
            $data['total_slots'] = max(1, (int)($post['total_slots'] ?? $spot['total_slots']));
        }

        ActivityLog::captureBefore($spot);
        $spotModel->update($id, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'promo_media_spots', (string)$id, $post['nama']);
        return redirect()->to('/creative/media-promo/master')->with('success', 'Titik media diupdate.');
    }

    public function deleteSpot(int $id)
    {
        if ($r = $this->checkEdit()) return $r;

        $spot  = (new PromoMediaSpotModel())->find($id);
        if (! $spot) return redirect()->to('/creative/media-promo')->with('error', 'Titik tidak ditemukan.');

        $activeUsage = db_connect()->table('promo_media_usage')
            ->where('spot_id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->countAllResults();

        if ($activeUsage > 0) {
            return redirect()->to('/creative/media-promo')->with('error', 'Titik masih memiliki usage aktif/pending.');
        }

        (new PromoMediaSpotModel())->delete($id);
        ActivityLog::write('delete', 'promo_media_spots', (string)$id, $spot['nama']);
        return redirect()->to('/creative/media-promo')->with('success', 'Titik media dihapus.');
    }

    public function pending()
    {
        if ($r = $this->checkView()) return $r;
        if (! $this->canApprovePromoMedia()) {
            return redirect()->to('/creative/media-promo')->with('error', 'Akses ditolak.');
        }

        $usageModel = new PromoMediaUsageModel();
        return view('creative/media_promo/pending', [
            'user'    => $this->currentUser(),
            'groups'  => $usageModel->getPendingGrouped(),
            'canEdit' => true,
        ]);
    }

    public function batchApprove()
    {
        if ($r = $this->checkApprove()) return $r;

        $approveIds = array_filter(array_map('intval', (array)($this->request->getPost('approve_ids') ?? [])));
        $catatan    = trim($this->request->getPost('catatan_approver') ?? '');
        $userId     = $this->currentUser()['id'];
        $now        = date('Y-m-d H:i:s');

        if (empty($approveIds)) {
            return redirect()->to('/creative/media-promo/pending')->with('error', 'Pilih minimal 1 item untuk disetujui.');
        }

        $usageModel = new PromoMediaUsageModel();
        $approved   = 0;
        foreach ($approveIds as $id) {
            $usage = $usageModel->find($id);
            if (! $usage || $usage['status'] !== 'pending') continue;
            $usageModel->update($id, [
                'status'           => 'approved',
                'catatan_approver' => $catatan ?: null,
                'approved_by'      => $userId,
                'approved_at'      => $now,
            ]);
            ActivityLog::write('update', 'promo_media_usage', (string)$id, "Approve: {$usage['nama_materi']}");
            $approved++;
        }

        return redirect()->to('/creative/media-promo/pending')->with('success', $approved . ' item berhasil disetujui.');
    }

    public function rejectBatch()
    {
        if ($r = $this->checkApprove()) return $r;

        $ids    = array_filter(array_map('intval', (array)($this->request->getPost('ids') ?? [])));
        $reason = trim($this->request->getPost('rejection_reason') ?? '');

        if (! $reason) {
            return redirect()->to('/creative/media-promo/pending')->with('error', 'Alasan penolakan wajib diisi.');
        }

        $usageModel = new PromoMediaUsageModel();
        $rejected   = 0;
        foreach ($ids as $id) {
            $usage = $usageModel->find($id);
            if (! $usage || $usage['status'] !== 'pending') continue;
            $usageModel->update($id, [
                'status'           => 'rejected',
                'rejection_reason' => $reason,
                'approved_by'      => $this->currentUser()['id'],
                'approved_at'      => date('Y-m-d H:i:s'),
            ]);
            ActivityLog::write('update', 'promo_media_usage', (string)$id, "Reject: {$usage['nama_materi']}");
            $rejected++;
        }

        return redirect()->to('/creative/media-promo/pending')->with('success', $rejected . ' item ditolak.');
    }

    public function approve(int $id)
    {
        if ($r = $this->checkApprove()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usage      = $usageModel->getWithSpot($id);
        if (! $usage || $usage['status'] !== 'pending') {
            return redirect()->to('/creative/media-promo/pending')->with('error', 'Request tidak ditemukan atau bukan pending.');
        }

        $catatan = trim($this->request->getPost('catatan_approver') ?? '');
        $userId  = $this->currentUser()['id'];

        $usageModel->update($id, [
            'status'           => 'approved',
            'catatan_approver' => $catatan ?: null,
            'approved_by'      => $userId,
            'approved_at'      => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('update', 'promo_media_usage', (string)$id, "Approve: {$usage['nama_materi']}");
        return redirect()->to('/creative/media-promo/pending')->with('success', 'Request disetujui.');
    }

    public function reject(int $id)
    {
        if ($r = $this->checkApprove()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usage      = $usageModel->getWithSpot($id);
        if (! $usage || $usage['status'] !== 'pending') {
            return redirect()->to('/creative/media-promo/pending')->with('error', 'Request tidak ditemukan atau bukan pending.');
        }

        $reason = trim($this->request->getPost('rejection_reason') ?? '');
        if (! $reason) {
            return redirect()->to('/creative/media-promo/pending')->with('error', 'Alasan penolakan wajib diisi.');
        }

        $usageModel->update($id, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'approved_by'      => $this->currentUser()['id'],
            'approved_at'      => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('update', 'promo_media_usage', (string)$id, "Reject: {$usage['nama_materi']}");
        return redirect()->to('/creative/media-promo/pending')->with('success', 'Request ditolak.');
    }

    public function gantt()
    {
        if ($r = $this->checkView()) return $r;

        $tipe = $this->request->getGet('tipe') ?: null;
        $dept = $this->request->getGet('dept') ?: null;

        // Start: awal bulan ini
        $tglMulai = date('Y-m-01');

        // End: ambil tanggal_selesai terpanjang dari DB, minimum 3 bulan ke depan
        $maxTgl = db_connect()
            ->table('promo_media_usage')
            ->selectMax('tanggal_selesai', 'max_tgl')
            ->whereIn('status', ['pending', 'approved'])
            ->get()->getRow()->max_tgl ?? null;

        $minSelesai = date('Y-m-t', strtotime('+2 months', strtotime($tglMulai)));
        $tglSelesai = ($maxTgl && $maxTgl > $minSelesai)
            ? date('Y-m-t', strtotime($maxTgl))   // extend ke akhir bulan terpanjang
            : $minSelesai;

        $usageModel = new PromoMediaUsageModel();
        $spotModel  = new PromoMediaSpotModel();
        $usageModel->markDoneExpired();

        $usages = $usageModel->getForGantt($tglMulai, $tglSelesai, $tipe, $dept);
        $spots  = $spotModel->orderBy("tipe = 'digital'", 'ASC', false)->orderBy('tipe')->orderBy('kode')->findAll();

        $depts = db_connect()->table('promo_media_usage')->select('dept')->distinct()->orderBy('dept')->get()->getResultArray();

        return view('creative/media_promo/gantt', [
            'user'       => $this->currentUser(),
            'usages'     => $usages,
            'spots'      => $spots,
            'tglMulai'   => $tglMulai,
            'tglSelesai' => $tglSelesai,
            'filterTipe' => $tipe,
            'filterDept' => $dept,
            'depts'      => array_column($depts, 'dept'),
            'canEdit'    => $this->canEditMenu('creative_main'),
            'canApprove' => $this->canApprovePromoMedia(),
        ]);
    }

    public function summary()
    {
        if ($r = $this->checkView()) return $r;

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $bulanMulai   = $bulan . '-01';
        $bulanSelesai = date('Y-m-t', strtotime($bulanMulai));
        $totalDays    = (int) date('t', strtotime($bulanMulai));
        $prevBulan    = date('Y-m', strtotime('-1 month', strtotime($bulanMulai)));
        $nextBulan    = date('Y-m', strtotime('+1 month', strtotime($bulanMulai)));

        $db = db_connect();

        // All usages overlapping this month
        $usages = $db->table('promo_media_usage u')
            ->select('u.*, s.kode spot_kode, s.nama spot_nama, s.tipe spot_tipe, s.area spot_area, s.total_slots spot_total_slots')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->where('u.tanggal_mulai <=', $bulanSelesai)
            ->where('u.tanggal_selesai >=', $bulanMulai)
            ->orderBy('s.tipe')->orderBy('s.kode')
            ->get()->getResultArray();

        $statusCounts  = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'done' => 0, 'draft' => 0];
        $deptCounts    = [];
        $tipeCounts    = [];
        $sumberCounts  = ['internal' => 0, 'tenant' => 0, 'external' => 0];
        $berbayarCount = 0;

        foreach ($usages as $u) {
            $s = $u['status'];
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
            $deptCounts[$u['dept']]       = ($deptCounts[$u['dept']] ?? 0) + 1;
            $tipeCounts[$u['spot_tipe']]  = ($tipeCounts[$u['spot_tipe']] ?? 0) + 1;
            $sumberCounts[$u['sumber'] ?? 'internal'] = ($sumberCounts[$u['sumber'] ?? 'internal'] ?? 0) + 1;
            if ($u['is_berbayar']) $berbayarCount++;
        }
        arsort($deptCounts);

        // Occupancy per active spot
        $spots          = (new PromoMediaSpotModel())->where('is_active', 1)->orderBy('tipe')->orderBy('kode')->findAll();
        $approvedUsages = array_filter($usages, fn($u) => in_array($u['status'], ['approved', 'done']));
        $spotOccupancy  = [];

        foreach ($spots as $s) {
            $spotId    = (int) $s['id'];
            $isDigital = $s['tipe'] === 'digital';
            $capacity  = $totalDays * ($isDigital ? (int) $s['total_slots'] : 1);
            $spotU     = array_filter($approvedUsages, fn($u) => (int) $u['spot_id'] === $spotId);

            if ($isDigital) {
                $occupiedDays = 0;
                foreach ($spotU as $u) {
                    $start = max($bulanMulai, $u['tanggal_mulai']);
                    $end   = min($bulanSelesai, $u['tanggal_selesai']);
                    $occupiedDays += max(0, (int) round((strtotime($end) - strtotime($start)) / 86400) + 1);
                }
            } else {
                $occupiedDates = [];
                foreach ($spotU as $u) {
                    $d = strtotime(max($bulanMulai, $u['tanggal_mulai']));
                    $e = strtotime(min($bulanSelesai, $u['tanggal_selesai']));
                    while ($d <= $e) { $occupiedDates[date('Y-m-d', $d)] = true; $d += 86400; }
                }
                $occupiedDays = count($occupiedDates);
            }

            $spotOccupancy[] = [
                'spot'     => $s,
                'occupied' => $occupiedDays,
                'capacity' => $capacity,
                'pct'      => $capacity > 0 ? round($occupiedDays / $capacity * 100) : 0,
            ];
        }
        usort($spotOccupancy, fn($a, $b) => $b['pct'] <=> $a['pct']);

        return view('creative/media_promo/summary', [
            'user'          => $this->currentUser(),
            'bulan'         => $bulan,
            'bulanMulai'    => $bulanMulai,
            'bulanSelesai'  => $bulanSelesai,
            'prevBulan'     => $prevBulan,
            'nextBulan'     => $nextBulan,
            'totalDays'     => $totalDays,
            'usages'        => $usages,
            'statusCounts'  => $statusCounts,
            'deptCounts'    => $deptCounts,
            'tipeCounts'    => $tipeCounts,
            'sumberCounts'  => $sumberCounts,
            'berbayarCount' => $berbayarCount,
            'spotOccupancy' => $spotOccupancy,
        ]);
    }

    public function checkCetakAvailability()
    {
        $tglMulai   = $this->request->getGet('tgl_mulai');
        $tglSelesai = $this->request->getGet('tgl_selesai');

        if (! $tglMulai || ! $tglSelesai || $tglMulai > $tglSelesai) {
            return $this->response->setJSON(['occupied' => []]);
        }

        $rows = db_connect()->table('promo_media_usage')
            ->select('spot_id')
            ->whereIn('status', ['pending', 'approved'])
            ->where('tanggal_mulai <=', $tglSelesai)
            ->where('tanggal_selesai >=', $tglMulai)
            ->get()->getResultArray();

        return $this->response->setJSON(['occupied' => array_column($rows, 'spot_id')]);
    }

    public function getAvailableSlots(int $spotId)
    {
        $tglMulai   = $this->request->getGet('tgl_mulai');
        $tglSelesai = $this->request->getGet('tgl_selesai');
        $excludeId  = (int)($this->request->getGet('exclude_id') ?? 0) ?: null;

        $spot  = (new PromoMediaSpotModel())->find($spotId);
        $total = $spot ? (int)$spot['total_slots'] : 0;

        if (! $tglMulai || ! $tglSelesai) {
            return $this->response->setJSON(['total' => $total, 'available' => range(1, $total)]);
        }

        $available = (new PromoMediaSpotModel())->getAvailableSlots($spotId, $tglMulai, $tglSelesai, $excludeId);
        return $this->response->setJSON(['total' => $total, 'available' => $available]);
    }

    public function printSummary()
    {
        if ($r = $this->checkView()) return $r;

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $bulanMulai   = $bulan . '-01';
        $bulanSelesai = date('Y-m-t', strtotime($bulanMulai));
        $totalDays    = (int) date('t', strtotime($bulanMulai));

        $db     = db_connect();
        $usages = $db->table('promo_media_usage u')
            ->select('u.*, s.kode spot_kode, s.nama spot_nama, s.tipe spot_tipe, s.area spot_area, s.total_slots spot_total_slots')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->where('u.tanggal_mulai <=', $bulanSelesai)
            ->where('u.tanggal_selesai >=', $bulanMulai)
            ->orderBy('s.tipe')->orderBy('s.kode')
            ->get()->getResultArray();

        $statusCounts  = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'done' => 0, 'draft' => 0];
        $deptCounts    = [];
        $tipeCounts    = [];
        $sumberCounts  = ['internal' => 0, 'tenant' => 0, 'external' => 0];
        $berbayarCount = 0;

        foreach ($usages as $u) {
            $s = $u['status'];
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
            $deptCounts[$u['dept']]      = ($deptCounts[$u['dept']] ?? 0) + 1;
            $tipeCounts[$u['spot_tipe']] = ($tipeCounts[$u['spot_tipe']] ?? 0) + 1;
            $sumberCounts[$u['sumber'] ?? 'internal'] = ($sumberCounts[$u['sumber'] ?? 'internal'] ?? 0) + 1;
            if ($u['is_berbayar']) $berbayarCount++;
        }
        arsort($deptCounts);

        $spots          = (new PromoMediaSpotModel())->where('is_active', 1)->orderBy('tipe')->orderBy('kode')->findAll();
        $approvedUsages = array_filter($usages, fn($u) => in_array($u['status'], ['approved', 'done']));
        $spotOccupancy  = [];

        foreach ($spots as $s) {
            $spotId    = (int) $s['id'];
            $isDigital = $s['tipe'] === 'digital';
            $capacity  = $totalDays * ($isDigital ? (int) $s['total_slots'] : 1);
            $spotU     = array_filter($approvedUsages, fn($u) => (int) $u['spot_id'] === $spotId);

            if ($isDigital) {
                $occupiedDays = 0;
                foreach ($spotU as $u) {
                    $start = max($bulanMulai, $u['tanggal_mulai']);
                    $end   = min($bulanSelesai, $u['tanggal_selesai']);
                    $occupiedDays += max(0, (int) round((strtotime($end) - strtotime($start)) / 86400) + 1);
                }
            } else {
                $occupiedDates = [];
                foreach ($spotU as $u) {
                    $d = strtotime(max($bulanMulai, $u['tanggal_mulai']));
                    $e = strtotime(min($bulanSelesai, $u['tanggal_selesai']));
                    while ($d <= $e) { $occupiedDates[date('Y-m-d', $d)] = true; $d += 86400; }
                }
                $occupiedDays = count($occupiedDates);
            }

            $spotOccupancy[] = [
                'spot'     => $s,
                'occupied' => $occupiedDays,
                'capacity' => $capacity,
                'pct'      => $capacity > 0 ? round($occupiedDays / $capacity * 100) : 0,
            ];
        }
        usort($spotOccupancy, fn($a, $b) => $b['pct'] <=> $a['pct']);

        return view('creative/media_promo/print_summary', [
            'bulan'         => $bulan,
            'bulanMulai'    => $bulanMulai,
            'bulanSelesai'  => $bulanSelesai,
            'totalDays'     => $totalDays,
            'statusCounts'  => $statusCounts,
            'deptCounts'    => $deptCounts,
            'tipeCounts'    => $tipeCounts,
            'sumberCounts'  => $sumberCounts,
            'berbayarCount' => $berbayarCount,
            'spotOccupancy' => $spotOccupancy,
            'totalRequest'  => array_sum($statusCounts),
            'printedBy'     => $this->currentUser()['name'] ?? '',
            'printedAt'     => date('d M Y H:i'),
        ]);
    }

    public function printBooking()
    {
        if ($r = $this->checkView()) return $r;

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $bulanMulai   = $bulan . '-01';
        $bulanSelesai = date('Y-m-t', strtotime($bulanMulai));

        $db     = db_connect();
        $usages = $db->table('promo_media_usage u')
            ->select('u.*, s.kode spot_kode, s.nama spot_nama, s.tipe spot_tipe, s.area spot_area')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->where('u.tanggal_mulai <=', $bulanSelesai)
            ->where('u.tanggal_selesai >=', $bulanMulai)
            ->whereIn('u.status', ['pending', 'approved', 'done'])
            ->orderBy('s.tipe')->orderBy('s.kode')->orderBy('u.tanggal_mulai')
            ->get()->getResultArray();

        return view('creative/media_promo/print', [
            'bulan'       => $bulan,
            'bulanMulai'  => $bulanMulai,
            'bulanSelesai'=> $bulanSelesai,
            'usages'      => $usages,
            'printedBy'   => $this->currentUser()['name'] ?? '',
            'printedAt'   => date('d M Y H:i'),
        ]);
    }
}
