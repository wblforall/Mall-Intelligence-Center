<?php

namespace App\Controllers;

use App\Models\SponsorshipProgramModel;
use App\Models\SponsorshipSponsorModel;
use App\Models\SponsorshipSponsorItemModel;
use App\Models\SponsorshipRealisasiModel;
use App\Models\SponsorshipSummaryAnalysisModel;
use App\Models\EventSponsorModel;
use App\Models\EventSponsorRealisasiModel;
use App\Libraries\ActivityLog;

class SponsorshipCtrl extends BaseController
{
    private function assertNotLocked(int $programId): bool
    {
        return ! (new SponsorshipProgramModel())->isLocked($programId);
    }

    private function syncBudget(int $programId): void
    {
        $total = (int)(db_connect()->table('sponsorship_sponsors')
            ->selectSum('nilai')
            ->where('program_id', $programId)
            ->whereIn('status_deal', ['terkonfirmasi', 'lunas'])
            ->get()->getRow()->nilai ?? 0);
        (new SponsorshipProgramModel())->update($programId, ['budget' => $total]);
    }

    private function saveItems(int $sponsorId, int $programId, array $post): int
    {
        $desks  = $post['deskripsi_barang'] ?? [];
        $qtys   = $post['qty'] ?? [];
        $values = $post['nilai_item'] ?? [];
        $model  = new SponsorshipSponsorItemModel();
        $total  = 0;

        foreach ($desks as $i => $desk) {
            $n = (int)str_replace([',', '.', ' '], '', $values[$i] ?? 0);
            $q = max(0, (int)($qtys[$i] ?? 0));
            if (! $desk && ! $q && ! $n) continue;
            $model->insert([
                'program_id'       => $programId,
                'sponsor_id'       => $sponsorId,
                'deskripsi_barang' => $desk ?: null,
                'qty'              => $q ?: null,
                'nilai'            => $n,
            ]);
            $total += $n * ($q > 0 ? $q : 1);
        }
        return $total;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        if (! $this->canViewMenu('sponsorship_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $programs   = (new SponsorshipProgramModel())->getAll();
        $programIds = array_column($programs, 'id');

        $sponsors      = (new SponsorshipSponsorModel())->getByPrograms($programIds);
        $allSponsorIds = [];
        foreach ($sponsors as $spList) {
            foreach ($spList as $sp) { $allSponsorIds[] = $sp['id']; }
        }

        $itemsBySponsors = (new SponsorshipSponsorItemModel())->getBySponsorIds($allSponsorIds);
        $realisasi       = (new SponsorshipRealisasiModel())->getGroupedBySponsors($allSponsorIds);
        $committedMap    = (new SponsorshipSponsorModel())->getCommittedByPrograms($programIds);
        $realisasiMap    = (new SponsorshipRealisasiModel())->getTotalByPrograms($programIds);

        // KPIs
        $activeCount         = count(array_filter($programs, fn($p) => $p['status'] === 'active'));
        $totalNilaiCommitted = 0;
        $totalNilaiCash      = 0;
        $totalNilaiBarang    = 0;
        $totalNilaiTerkumpul = 0;
        $totalSponsorCount   = 0;
        $targetNilai         = 0;
        $targetSponsor       = 0;
        foreach ($programs as $p) {
            if ($p['status'] !== 'active') continue;
            $targetNilai   += (int)($p['target_nilai']   ?? 0);
            $targetSponsor += (int)($p['target_sponsor'] ?? 0);
            $c = $committedMap[$p['id']] ?? [];
            $totalNilaiCommitted += (int)($c['total_nilai']   ?? 0);
            $totalNilaiCash      += (int)($c['total_cash']    ?? 0);
            $totalNilaiBarang    += (int)($c['total_barang']  ?? 0);
            $totalSponsorCount   += (int)($c['total_sponsor'] ?? 0);
            $totalNilaiTerkumpul += (int)($realisasiMap[$p['id']] ?? 0);
        }

        return view('sponsorship/index', [
            'user'                => $this->currentUser(),
            'programs'            => $programs,
            'sponsors'            => $sponsors,
            'itemsBySponsors'     => $itemsBySponsors,
            'realisasi'           => $realisasi,
            'committedMap'        => $committedMap,
            'realisasiMap'        => $realisasiMap,
            'activeCount'         => $activeCount,
            'totalNilaiCommitted' => $totalNilaiCommitted,
            'totalNilaiCash'      => $totalNilaiCash,
            'totalNilaiBarang'    => $totalNilaiBarang,
            'totalNilaiTerkumpul' => $totalNilaiTerkumpul,
            'totalSponsorCount'   => $totalSponsorCount,
            'targetNilai'         => $targetNilai,
            'targetSponsor'       => $targetSponsor,
            'canEdit'             => $this->canEditMenu('sponsorship_main'),
        ]);
    }

    // ── Program CRUD ──────────────────────────────────────────────────────────

    public function storeProgram()
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $model = new SponsorshipProgramModel();
        $model->insert([
            'nama_program'    => trim($post['nama_program']),
            'mall'            => in_array($post['mall'] ?? '', ['ewalk', 'pentacity', 'both']) ? $post['mall'] : null,
            'tanggal_mulai'   => $post['tanggal_mulai']   ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'deskripsi'       => trim($post['deskripsi'] ?? '') ?: null,
            'target_sponsor'  => ($post['target_sponsor'] ?? '') !== '' ? (int)$post['target_sponsor'] : null,
            'target_nilai'    => ($post['target_nilai']   ?? '') !== '' ? (int)str_replace([',', '.', ' '], '', $post['target_nilai']) : null,
            'budget'          => 0,
            'status'          => 'active',
            'catatan'         => trim($post['catatan'] ?? '') ?: null,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'sponsorship_program', (string)$model->getInsertID(), trim($post['nama_program']));
        return redirect()->to('/sponsorship')->with('success', 'Program sponsorship berhasil ditambahkan.');
    }

    public function updateProgram(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Program terkunci.');
        $post       = $this->request->getPost();
        $progModel  = new SponsorshipProgramModel();
        ActivityLog::captureBefore($progModel->find($id));
        $progData = [
            'nama_program'    => trim($post['nama_program']),
            'mall'            => in_array($post['mall'] ?? '', ['ewalk', 'pentacity', 'both']) ? $post['mall'] : null,
            'tanggal_mulai'   => $post['tanggal_mulai']   ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'deskripsi'       => trim($post['deskripsi'] ?? '') ?: null,
            'target_sponsor'  => ($post['target_sponsor'] ?? '') !== '' ? (int)$post['target_sponsor'] : null,
            'target_nilai'    => ($post['target_nilai']   ?? '') !== '' ? (int)str_replace([',', '.', ' '], '', $post['target_nilai']) : null,
            'catatan'         => trim($post['catatan'] ?? '') ?: null,
        ];
        $progModel->update($id, $progData);
        ActivityLog::captureAfter($progModel->find($id));
        ActivityLog::write('update', 'sponsorship_program', (string)$id, trim($post['nama_program']));
        return redirect()->to('/sponsorship')->with('success', 'Program berhasil diperbarui.');
    }

    public function deleteProgram(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($id)) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Program terkunci.');

        $prog      = (new SponsorshipProgramModel())->find($id);
        $sponsors  = (new SponsorshipSponsorModel())->where('program_id', $id)->findAll();
        $sponsorIds = array_column($sponsors, 'id');

        $db = db_connect();
        $realisasiRows = [];
        if ($sponsorIds) {
            $realisasiRows = (new SponsorshipRealisasiModel())->whereIn('sponsor_id', $sponsorIds)->findAll();
        }
        $db->transStart();
        if ($sponsorIds) {
            $db->table('sponsorship_realisasi')->whereIn('sponsor_id', $sponsorIds)->delete();
            $db->table('sponsorship_sponsor_items')->whereIn('sponsor_id', $sponsorIds)->delete();
            $db->table('sponsorship_sponsors')->where('program_id', $id)->delete();
        }
        (new SponsorshipProgramModel())->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $dir = FCPATH . 'uploads/sponsorship/' . $id . '/';
            foreach ($realisasiRows as $r) {
                if ($r['file_bukti'] && file_exists($dir . $r['file_bukti'])) unlink($dir . $r['file_bukti']);
            }
        }

        ActivityLog::write('delete', 'sponsorship_program', (string)$id, $prog['nama_program'] ?? '');
        return redirect()->to('/sponsorship')->with('success', 'Program berhasil dihapus.');
    }

    public function toggleStatus(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        $pm = new SponsorshipProgramModel();
        if ($pm->isLocked($id)) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Program terkunci.');
        ActivityLog::captureBefore($pm->find($id));
        $pm->toggleStatus($id);
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'sponsorship_program', (string)$id, '', ['action' => 'toggle_status']);
        return redirect()->to('/sponsorship')->with('success', 'Status program diperbarui.');
    }

    public function lock(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        $post        = $this->request->getPost();
        $evalStatus  = in_array($post['eval_status'] ?? '', ['berhasil', 'sebagian', 'gagal']) ? $post['eval_status'] : null;
        $pm = new SponsorshipProgramModel();
        ActivityLog::captureBefore($pm->find($id));
        $pm->lock($id, $this->currentUser()['id'], $evalStatus, $post['eval_kendala'] ?? null, $post['eval_rekomendasi'] ?? null);
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'sponsorship_program', (string)$id, '', ['action' => 'lock', 'eval_status' => $evalStatus]);
        return redirect()->to('/sponsorship#program-' . $id)->with('success', 'Program berhasil dikunci.');
    }

    public function unlock(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Hanya admin yang bisa membuka kunci.');
        $pm = new SponsorshipProgramModel();
        ActivityLog::captureBefore($pm->find($id));
        $pm->unlock($id);
        ActivityLog::captureAfter($pm->find($id));
        ActivityLog::write('update', 'sponsorship_program', (string)$id, '', ['action' => 'unlock']);
        return redirect()->to('/sponsorship#program-' . $id)->with('success', 'Kunci program berhasil dibuka.');
    }

    // ── Sponsor deals ─────────────────────────────────────────────────────────

    public function storeSponsor(int $programId)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Program terkunci.');

        $post     = $this->request->getPost();
        $isBarang = ($post['jenis'] ?? 'cash') === 'barang';
        $clean    = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);

        $model = new SponsorshipSponsorModel();
        $id    = $model->insert([
            'program_id'  => $programId,
            'nama_sponsor'=> trim($post['nama_sponsor']),
            'kategori'    => trim($post['kategori'] ?? '') ?: null,
            'jenis'       => $post['jenis'] ?? 'cash',
            'nilai'       => $isBarang ? 0 : $clean($post['nilai']),
            'status_deal' => $post['status_deal'] ?? 'prospek',
            'detail'      => trim($post['detail'] ?? '') ?: null,
            'catatan'     => trim($post['catatan'] ?? '') ?: null,
            'created_by'  => $this->currentUser()['id'],
        ]);
        if (! $id) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Gagal menyimpan sponsor.');

        if ($isBarang) {
            $total = $this->saveItems($id, $programId, $post);
            $model->update($id, ['nilai' => $total]);
        }

        $this->syncBudget($programId);
        ActivityLog::write('create', 'sponsorship_sponsor', (string)$id, trim($post['nama_sponsor']), ['program_id' => $programId]);
        return redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Sponsor berhasil ditambahkan.');
    }

    public function updateSponsor(int $programId, int $sponsorId)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Program terkunci.');

        $post     = $this->request->getPost();
        $isBarang = ($post['jenis'] ?? 'cash') === 'barang';
        $clean    = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);

        $model = new SponsorshipSponsorModel();
        ActivityLog::captureBefore($model->find($sponsorId));
        $sponsorData = [
            'nama_sponsor'=> trim($post['nama_sponsor']),
            'kategori'    => trim($post['kategori'] ?? '') ?: null,
            'jenis'       => $post['jenis'] ?? 'cash',
            'nilai'       => $isBarang ? 0 : $clean($post['nilai']),
            'status_deal' => $post['status_deal'] ?? 'prospek',
            'detail'      => trim($post['detail'] ?? '') ?: null,
            'catatan'     => trim($post['catatan'] ?? '') ?: null,
        ];
        $model->update($sponsorId, $sponsorData);
        if ($isBarang) {
            (new SponsorshipSponsorItemModel())->deleteBySponsor($sponsorId);
            $total = $this->saveItems($sponsorId, $programId, $post);
            $model->update($sponsorId, ['nilai' => $total]);
        }
        ActivityLog::captureAfter($model->find($sponsorId));

        $this->syncBudget($programId);
        ActivityLog::write('update', 'sponsorship_sponsor', (string)$sponsorId, trim($post['nama_sponsor']), ['program_id' => $programId]);
        return redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Sponsor berhasil diperbarui.');
    }

    public function deleteSponsor(int $programId, int $sponsorId)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Program terkunci.');

        $db  = db_connect();
        $dir = FCPATH . 'uploads/sponsorship/' . $programId . '/';

        $realisasiRows = (new SponsorshipRealisasiModel())->where('sponsor_id', $sponsorId)->findAll();

        $db->transStart();
        $db->table('sponsorship_realisasi')->where('sponsor_id', $sponsorId)->delete();
        (new SponsorshipSponsorItemModel())->deleteBySponsor($sponsorId);
        (new SponsorshipSponsorModel())->delete($sponsorId);
        $db->transComplete();

        if ($db->transStatus()) {
            foreach ($realisasiRows as $r) {
                if ($r['file_bukti'] && file_exists($dir . $r['file_bukti'])) {
                    unlink($dir . $r['file_bukti']);
                }
            }
        }

        $this->syncBudget($programId);
        ActivityLog::write('delete', 'sponsorship_sponsor', (string)$sponsorId, '', ['program_id' => $programId]);
        return redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Sponsor dihapus.');
    }

    // ── Realisasi ─────────────────────────────────────────────────────────────

    public function storeRealisasi(int $programId, int $sponsorId)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Program terkunci.');

        $post      = $this->request->getPost();
        $uploadDir = FCPATH . 'uploads/sponsorship/' . $programId . '/';
        if (! is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileBukti   = null;
        $pendingFile = null;
        $pendingName = null;
        $file = $this->request->getFile('file_bukti');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $pendingName = 'bukti_' . $sponsorId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $fileBukti   = $pendingName;
            $pendingFile = $file;
        }

        $nilai    = (int)str_replace([',', '.', ' '], '', $post['nilai'] ?? 0);
        $model    = new SponsorshipRealisasiModel();
        $insertId = $model->insert([
            'program_id' => $programId,
            'sponsor_id' => $sponsorId,
            'tanggal'    => $post['tanggal'] ?: null,
            'nilai'      => $nilai,
            'catatan'    => trim($post['catatan'] ?? '') ?: null,
            'file_bukti' => $fileBukti,
            'created_by' => $this->currentUser()['id'],
        ]);
        if (! $insertId) return redirect()->back()->with('error', 'Gagal menyimpan realisasi.');
        if ($pendingFile) { $pendingFile->move($uploadDir, $pendingName); \App\Libraries\ImageCompressor::compress($uploadDir . '/' . $pendingName); }

        ActivityLog::write('create', 'sponsorship_realisasi', (string)$insertId, $post['tanggal'] ?? '', ['program_id' => $programId, 'sponsor_id' => $sponsorId, 'nilai' => $nilai]);
        return redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Realisasi berhasil disimpan.');
    }

    public function deleteRealisasi(int $programId, int $sponsorId, int $rid)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if (! $this->assertNotLocked($programId)) return redirect()->to('/sponsorship#program-' . $programId)->with('error', 'Program terkunci.');

        $model = new SponsorshipRealisasiModel();
        $row   = $model->find($rid);
        if ($row) {
            $dir = FCPATH . 'uploads/sponsorship/' . $programId . '/';
            $model->delete($rid);
            if ($row['file_bukti'] && file_exists($dir . $row['file_bukti'])) {
                unlink($dir . $row['file_bukti']);
            }
        }

        ActivityLog::write('delete', 'sponsorship_realisasi', (string)$rid, '', ['program_id' => $programId]);
        return redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Realisasi dihapus.');
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function summary()
    {
        if (! $this->canViewMenu('sponsorship_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $programs   = (new SponsorshipProgramModel())->getAll(); // sudah urut per periode
        $programIds = array_column($programs, 'id');

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $realModel = new SponsorshipRealisasiModel();
        $spModel   = new SponsorshipSponsorModel();

        // ── Sponsor dari EVENT (ikutkan ke summary; sebelumnya hanya standalone) ──
        $evModel   = new EventSponsorModel();
        $evrModel  = new EventSponsorRealisasiModel();
        $eventAggs = $evModel->getEventAggregates();
        $eventIds  = array_column($eventAggs, 'event_id');

        // Daftar bulan dropdown: gabungan bulan REALISASI + PERIODE program +
        // periode event. Sebelumnya hanya dari realisasi — bila realisasi masih
        // kosong, dropdown cuma berisi bulan berjalan sehingga bulan lain (yang
        // programnya ada) tak bisa dipilih.
        $monthSet = [$bulan => true];
        foreach ($realModel->getAvailableMonths($programIds) as $r) { $monthSet[$r['bulan']] = true; }
        foreach ($evrModel->getAllMonthlyTotals($eventIds) as $r)    { $monthSet[$r['bulan']] = true; }
        foreach ($programs as $p) {
            $s = substr((string)($p['tanggal_mulai'] ?? ''), 0, 7);
            if ($s === '') continue;
            $e = substr((string)($p['tanggal_selesai'] ?? '') ?: $s, 0, 7);
            for ($m = $s; $m <= $e; $m = date('Y-m', strtotime($m . '-01 +1 month'))) { $monthSet[$m] = true; }
        }
        foreach ($eventAggs as $e) {
            $s = substr((string)($e['event_start_date'] ?? ''), 0, 7);
            if ($s !== '') $monthSet[$s] = true;
        }
        $monthList = array_keys($monthSet);
        rsort($monthList);

        // Per-program realisasi for selected month
        $monthlyReal      = $realModel->getMonthlyByPrograms($bulan, $programIds);
        $committedMap     = $spModel->getCommittedByPrograms($programIds);
        $allTimeRealMap   = $realModel->getTotalByPrograms($programIds);
        $evMonthly = $evrModel->getMonthlyByEvents($bulan, $eventIds);   // realisasi bulan ini per event
        $evGrand   = array_sum(array_column($evrModel->getAllMonthlyTotals($eventIds), 'total_nilai'));
        $evDeal    = 0; $evSponsorCount = 0;
        foreach ($eventAggs as &$e) {
            $e['deal']      = (int)$e['total_cash'] + (int)$e['total_barang'];
            $e['realisasi'] = (int)($evMonthly[$e['event_id']] ?? 0);
            $evDeal        += $e['deal'];
            $evSponsorCount += (int)$e['jumlah_sponsor'];
        }
        unset($e);

        // KPIs for selected month (standalone + event)
        $kpiTerkumpul  = array_sum($monthlyReal) + array_sum($evMonthly);
        $kpiCommitted  = $evDeal;
        $kpiSponsor    = $evSponsorCount;
        foreach ($programs as $p) {
            $c = $committedMap[$p['id']] ?? [];
            $kpiCommitted += (int)($c['total_nilai']   ?? 0);
            $kpiSponsor   += (int)($c['total_sponsor'] ?? 0);
        }
        $grandTotal = array_sum($allTimeRealMap) + $evGrand; // all-time terkumpul (standalone + event)

        // All-time monthly trend (standalone + event, digabung per bulan)
        $monthMap = [];
        foreach ($realModel->getAllMonthlyTotals($programIds) as $r) { $monthMap[$r['bulan']] = (int)$r['total_nilai']; }
        foreach ($evrModel->getAllMonthlyTotals($eventIds) as $r)    { $monthMap[$r['bulan']] = ($monthMap[$r['bulan']] ?? 0) + (int)$r['total_nilai']; }
        ksort($monthMap);
        $currentYear = date('Y');
        $allMonthlyTotals = [];
        foreach ($monthMap as $b => $v) {
            if (str_starts_with($b, $currentYear)) $allMonthlyTotals[] = ['bulan' => $b, 'total_nilai' => $v];
        }

        // Daily chart for selected month (standalone + event)
        $daysInMonth = (int)date('t', strtotime($bulan . '-01'));
        $chartDates  = [];
        $dailyNilai  = array_fill(0, $daysInMonth, 0);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartDates[] = str_pad($d, 2, '0', STR_PAD_LEFT);
        }
        foreach (array_merge($realModel->getDailyForMonth($bulan, $programIds), $evrModel->getDailyForMonth($bulan, $eventIds)) as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            if ($idx >= 0 && $idx < $daysInMonth) $dailyNilai[$idx] += (int)$row['nilai'];
        }

        // Per-program sponsor breakdown
        $sponsors = $spModel->getByPrograms($programIds);

        // Analisa per program (ACT phase)
        $aModel      = new SponsorshipSummaryAnalysisModel();
        $prevBulan   = date('Y-m', strtotime($bulan . '-01 -1 month'));
        $analisaMap  = $aModel->getMapByMonth($bulan);
        $prevAnalisaMap = $aModel->getMapByMonth($prevBulan);

        return view('sponsorship/summary', [
            'user'             => $this->currentUser(),
            'programs'         => $programs,
            'bulan'            => $bulan,
            'monthList'        => $monthList,
            'monthlyReal'      => $monthlyReal,
            'committedMap'     => $committedMap,
            'allTimeRealMap'   => $allTimeRealMap,
            'kpiTerkumpul'     => $kpiTerkumpul,
            'kpiCommitted'     => $kpiCommitted,
            'kpiSponsor'       => $kpiSponsor,
            'grandTotal'       => $grandTotal,
            'eventAggs'        => $eventAggs,
            'allMonthlyTotals' => $allMonthlyTotals,
            'chartDates'       => $chartDates,
            'dailyNilai'       => $dailyNilai,
            'sponsors'         => $sponsors,
            'analisaMap'       => $analisaMap,
            'prevAnalisaMap'   => $prevAnalisaMap,
            'canEdit'          => $this->canEditMenu('sponsorship_main'),
        ]);
    }

    // ── Laporan Bulanan (print) ───────────────────────────────────────────────
    public function printSummary()
    {
        if (! $this->canViewMenu('sponsorship_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');
        $prevBulan = date('Y-m', strtotime($bulan . '-01 -1 month'));

        // ── Standalone ────────────────────────────────────────────────────
        $programs   = (new SponsorshipProgramModel())->getAll();
        $programIds = array_column($programs, 'id');

        $realModel = new SponsorshipRealisasiModel();
        $spModel   = new SponsorshipSponsorModel();

        $monthlyReal  = $realModel->getMonthlyByPrograms($bulan, $programIds);
        $prevReal     = $realModel->getMonthlyByPrograms($prevBulan, $programIds);
        $cumReal      = $realModel->getCumulativeByPrograms($bulan, $programIds);
        $committedMap = $spModel->getCommittedByPrograms($programIds);
        $sponsorsMap  = $spModel->getByPrograms($programIds);

        // Pipeline per program (jumlah sponsor per status deal)
        $pipelineMap = [];
        $pipelineTotal = ['prospek' => 0, 'negosiasi' => 0, 'terkonfirmasi' => 0, 'lunas' => 0, 'batal' => 0];
        foreach ($sponsorsMap as $pid => $rows) {
            foreach ($rows as $sp) {
                $st = $sp['status_deal'] ?? 'prospek';
                $pipelineMap[$pid][$st] = ($pipelineMap[$pid][$st] ?? 0) + 1;
                if (isset($pipelineTotal[$st])) $pipelineTotal[$st]++;
            }
        }

        // ── Per-event ─────────────────────────────────────────────────────
        $evModel   = new EventSponsorModel();
        $evrModel  = new EventSponsorRealisasiModel();
        $eventAggs = $evModel->getEventAggregates();
        $eventIds  = array_column($eventAggs, 'event_id');

        $evMonthly = $evrModel->getMonthlyByEvents($bulan, $eventIds);
        $evPrev    = $evrModel->getMonthlyByEvents($prevBulan, $eventIds);
        $evCum     = $evrModel->getCumulativeByEvents($bulan, $eventIds);

        // ── KPI bulan terpilih ────────────────────────────────────────────
        $kpiSponsorDeal = array_sum(array_column($committedMap, 'total_sponsor'))
                        + array_sum(array_map(fn($e) => (int)$e['jumlah_sponsor'], $eventAggs));
        $kpiKomitmen    = array_sum(array_column($committedMap, 'total_nilai'))
                        + array_sum(array_map(fn($e) => (int)$e['total_cash'] + (int)$e['total_barang'], $eventAggs));
        $kpiRealisasi     = array_sum($monthlyReal) + array_sum($evMonthly);
        $kpiRealisasiPrev = array_sum($prevReal)    + array_sum($evPrev);
        $kpiKumulatif     = array_sum($cumReal)     + array_sum($evCum);

        $targetNilaiAktif = array_sum(array_map(
            fn($p) => $p['status'] === 'active' ? (int)($p['target_nilai'] ?? 0) : 0, $programs));
        $capaianPct = $targetNilaiAktif > 0 ? round(array_sum($cumReal) / $targetNilaiAktif * 100, 1) : 0;

        // ── Program baru per mall (mulai di bulan terpilih) ───────────────
        $mallCounts = ['ewalk' => 0, 'pentacity' => 0, 'both' => 0, 'unset' => 0];
        foreach ($programs as $p) {
            if (substr((string)($p['tanggal_mulai'] ?? ''), 0, 7) !== $bulan) continue;
            $mall = $p['mall'] ?? '';
            $mallCounts[isset($mallCounts[$mall]) ? $mall : 'unset']++;
        }
        foreach ($eventAggs as $e) {
            if (substr((string)($e['event_start_date'] ?? ''), 0, 7) !== $bulan) continue;
            $mall = $e['event_mall'] ?? '';
            $mallCounts[isset($mallCounts[$mall]) ? $mall : 'unset']++;
        }

        // ── Tren 6 bulan & aktivitas harian utk grafik ────────────────────
        $trendMap = [];
        foreach ($realModel->getAllMonthlyTotals($programIds) as $r) {
            $trendMap[$r['bulan']] = ($trendMap[$r['bulan']] ?? 0) + (int)$r['total_nilai'];
        }
        foreach ($evrModel->getAllMonthlyTotals($eventIds) as $r) {
            $trendMap[$r['bulan']] = ($trendMap[$r['bulan']] ?? 0) + (int)$r['total_nilai'];
        }
        $trendMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime($bulan . '-01 -' . $i . ' month'));
            $trendMonths[] = ['bulan' => $m, 'total_nilai' => (int)($trendMap[$m] ?? 0)];
        }

        $daysInMonth = (int)date('t', strtotime($bulan . '-01'));
        $dailyNilai  = array_fill(0, $daysInMonth, 0);
        foreach (array_merge(
            $realModel->getDailyForMonth($bulan, $programIds),
            $evrModel->getDailyForMonth($bulan, $eventIds)
        ) as $row) {
            $dailyNilai[(int)date('j', strtotime($row['tanggal'])) - 1] += (int)$row['nilai'];
        }

        // ── Insight otomatis (rule-based) ─────────────────────────────────
        $fmtRp    = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $fmtDelta = function (int $now, int $prev) use ($fmtRp): string {
            if ($prev <= 0) return $now > 0 ? 'naik dari 0 bulan lalu' : 'sama dengan bulan lalu (0)';
            $pct = round(($now - $prev) / $prev * 100);
            return ($pct >= 0 ? 'naik ' : 'turun ') . abs($pct) . '% dari bulan lalu (' . $fmtRp($prev) . ')';
        };

        $topName = null; $topVal = 0; $noActivity = 0;
        foreach ($programs as $p) {
            $val = (int)($monthlyReal[$p['id']] ?? 0);
            if ($val > $topVal) { $topVal = $val; $topName = $p['nama_program']; }
            if ($val === 0 && $p['status'] === 'active') $noActivity++;
        }
        foreach ($eventAggs as $e) {
            $val = (int)($evMonthly[$e['event_id']] ?? 0);
            if ($val > $topVal) { $topVal = $val; $topName = 'Event ' . $e['event_name']; }
        }

        $outstanding = max(0, $kpiKomitmen - $kpiKumulatif);
        $insights   = [];
        $insights[] = 'Penerimaan sponsorship bulan ini ' . $fmtRp($kpiRealisasi) . ' — ' . $fmtDelta($kpiRealisasi, $kpiRealisasiPrev) . '.';
        $insights[] = 'Komitmen deal (terkonfirmasi + lunas) ' . $fmtRp($kpiKomitmen) . ' dari ' . number_format($kpiSponsorDeal) . ' sponsor; realisasi kumulatif ' . $fmtRp($kpiKumulatif)
            . ($outstanding > 0 ? ' — sisa komitmen belum cair ' . $fmtRp($outstanding) . '.' : '.');
        if ($pipelineTotal['prospek'] + $pipelineTotal['negosiasi'] > 0) {
            $insights[] = 'Pipeline berjalan: ' . $pipelineTotal['prospek'] . ' prospek dan ' . $pipelineTotal['negosiasi'] . ' dalam negosiasi — potensi tambahan penerimaan.';
        }
        if ($topName) $insights[] = 'Penerimaan terbesar bulan ini: ' . $topName . ' (' . $fmtRp($topVal) . ').';
        if ($noActivity > 0) $insights[] = $noActivity . ' program aktif belum mencatat penerimaan bulan ini — perlu ditindaklanjuti.';
        if ($targetNilaiAktif > 0) $insights[] = 'Capaian kumulatif vs target program aktif: ' . $capaianPct . '% dari ' . $fmtRp($targetNilaiAktif) . '.';

        return view('sponsorship/print_summary', [
            'bulan'          => $bulan,
            'prevBulan'      => $prevBulan,
            'programs'       => $programs,
            'monthlyReal'    => $monthlyReal,
            'prevReal'       => $prevReal,
            'cumReal'        => $cumReal,
            'committedMap'   => $committedMap,
            'pipelineMap'    => $pipelineMap,
            'pipelineTotal'  => $pipelineTotal,
            'eventAggs'      => $eventAggs,
            'evMonthly'      => $evMonthly,
            'evPrev'         => $evPrev,
            'evCum'          => $evCum,
            'kpiSponsorDeal' => $kpiSponsorDeal,
            'kpiKomitmen'    => $kpiKomitmen,
            'kpiRealisasi'   => $kpiRealisasi,
            'kpiKumulatif'   => $kpiKumulatif,
            'targetNilaiAktif' => $targetNilaiAktif,
            'capaianPct'     => $capaianPct,
            'mallCounts'     => $mallCounts,
            'trendMonths'    => $trendMonths,
            'dailyNilai'     => $dailyNilai,
            'insights'       => $insights,
            'analisaMap'     => (new SponsorshipSummaryAnalysisModel())->getMapByMonth($bulan),
            'signatories'    => \App\Libraries\ReportSignatories::resolve('sponsorship_main'),
            'printedBy'      => $this->currentUser()['name'] ?? '',
            'printedAt'      => date('d M Y H:i'),
        ]);
    }

    // Simpan analisa per program (AJAX) — dipakai di halaman summary
    public function saveAnalisa()
    {
        if (! $this->canEditMenu('sponsorship_main')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Akses ditolak.']);
        }
        $bulan     = (string)$this->request->getPost('bulan');
        $programId = (int)$this->request->getPost('program_id');
        $highlight    = trim((string)$this->request->getPost('highlight'));
        $kendala      = trim((string)$this->request->getPost('kendala'));
        $tindakLanjut = trim((string)$this->request->getPost('tindak_lanjut'));

        if (! preg_match('/^\d{4}-\d{2}$/', $bulan) || $programId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Data tidak valid.', 'csrf' => csrf_hash()]);
        }
        if (! (new SponsorshipProgramModel())->find($programId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Program tidak ditemukan.', 'csrf' => csrf_hash()]);
        }

        $analisaModel = new SponsorshipSummaryAnalysisModel();
        $existing     = $analisaModel->where('bulan', $bulan)->where('program_id', $programId)->first();
        $analisaModel->saveAnalisa($bulan, $programId, $this->currentUser()['id'], $highlight, $kendala, $tindakLanjut);
        $action = $existing ? 'update' : 'create';
        ActivityLog::write($action, 'sponsorship_analisa', (string)$programId, 'Analisa program — ' . $bulan, ['bulan' => $bulan]);

        $filled = $highlight !== '' || $kendala !== '' || $tindakLanjut !== '';
        return $this->response->setJSON(['ok' => true, 'filled' => $filled, 'csrf' => csrf_hash()]);
    }

    // Serve file bukti realisasi sponsorship lewat auth (tidak boleh diakses publik langsung).
    public function viewFile(int $pid, string $name)
    {
        if (! $this->canViewMenu('sponsorship_main')) return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        $name = basename($name);
        $path = FCPATH . 'uploads/sponsorship/' . $pid . '/' . $name;
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
