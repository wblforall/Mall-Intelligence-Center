<?php

namespace App\Controllers;

use App\Models\SponsorshipProgramModel;
use App\Models\SponsorshipSponsorModel;
use App\Models\SponsorshipSponsorItemModel;
use App\Models\SponsorshipRealisasiModel;
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
        $post = $this->request->getPost();
        (new SponsorshipProgramModel())->update($id, [
            'nama_program'    => trim($post['nama_program']),
            'tanggal_mulai'   => $post['tanggal_mulai']   ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'deskripsi'       => trim($post['deskripsi'] ?? '') ?: null,
            'target_sponsor'  => ($post['target_sponsor'] ?? '') !== '' ? (int)$post['target_sponsor'] : null,
            'target_nilai'    => ($post['target_nilai']   ?? '') !== '' ? (int)str_replace([',', '.', ' '], '', $post['target_nilai']) : null,
            'catatan'         => trim($post['catatan'] ?? '') ?: null,
        ]);
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
        $db->transStart();
        if ($sponsorIds) {
            $realisasiRows = (new SponsorshipRealisasiModel())->whereIn('sponsor_id', $sponsorIds)->findAll();
            foreach ($realisasiRows as $r) {
                if ($r['file_bukti']) {
                    $path = FCPATH . 'uploads/sponsorship/' . $id . '/' . $r['file_bukti'];
                    if (file_exists($path)) unlink($path);
                }
            }
            $db->table('sponsorship_realisasi')->whereIn('sponsor_id', $sponsorIds)->delete();
            $db->table('sponsorship_sponsor_items')->whereIn('sponsor_id', $sponsorIds)->delete();
            $db->table('sponsorship_sponsors')->where('program_id', $id)->delete();
        }
        (new SponsorshipProgramModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'sponsorship_program', (string)$id, $prog['nama_program'] ?? '');
        return redirect()->to('/sponsorship')->with('success', 'Program berhasil dihapus.');
    }

    public function toggleStatus(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        if ((new SponsorshipProgramModel())->isLocked($id)) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Program terkunci.');
        (new SponsorshipProgramModel())->toggleStatus($id);
        ActivityLog::write('update', 'sponsorship_program', (string)$id, '', ['action' => 'toggle_status']);
        return redirect()->to('/sponsorship')->with('success', 'Status program diperbarui.');
    }

    public function lock(int $id)
    {
        if (! $this->canEditMenu('sponsorship_main')) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');
        (new SponsorshipProgramModel())->lock($id, $this->currentUser()['id']);
        ActivityLog::write('update', 'sponsorship_program', (string)$id, '', ['action' => 'lock']);
        return redirect()->to('/sponsorship#program-' . $id)->with('success', 'Program berhasil dikunci.');
    }

    public function unlock(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/sponsorship#program-' . $id)->with('error', 'Hanya admin yang bisa membuka kunci.');
        (new SponsorshipProgramModel())->unlock($id);
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
        $model->update($sponsorId, [
            'nama_sponsor'=> trim($post['nama_sponsor']),
            'kategori'    => trim($post['kategori'] ?? '') ?: null,
            'jenis'       => $post['jenis'] ?? 'cash',
            'nilai'       => $isBarang ? 0 : $clean($post['nilai']),
            'status_deal' => $post['status_deal'] ?? 'prospek',
            'detail'      => trim($post['detail'] ?? '') ?: null,
            'catatan'     => trim($post['catatan'] ?? '') ?: null,
        ]);

        if ($isBarang) {
            (new SponsorshipSponsorItemModel())->deleteBySponsor($sponsorId);
            $total = $this->saveItems($sponsorId, $programId, $post);
            $model->update($sponsorId, ['nilai' => $total]);
        }

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

        $fileBukti = null;
        $file = $this->request->getFile('file_bukti');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'bukti_' . $sponsorId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $file->move($uploadDir, $name);
            $fileBukti = $name;
        }

        $nilai = (int)str_replace([',', '.', ' '], '', $post['nilai'] ?? 0);
        $model = new SponsorshipRealisasiModel();
        $model->insert([
            'program_id' => $programId,
            'sponsor_id' => $sponsorId,
            'tanggal'    => $post['tanggal'] ?: null,
            'nilai'      => $nilai,
            'catatan'    => trim($post['catatan'] ?? '') ?: null,
            'file_bukti' => $fileBukti,
            'created_by' => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'sponsorship_realisasi', (string)$model->getInsertID(), $post['tanggal'] ?? '', ['program_id' => $programId, 'sponsor_id' => $sponsorId, 'nilai' => $nilai]);
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
            if ($row['file_bukti'] && file_exists($dir . $row['file_bukti'])) {
                unlink($dir . $row['file_bukti']);
            }
            $model->delete($rid);
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

        $programs   = (new SponsorshipProgramModel())->getAll();
        $programIds = array_column($programs, 'id');

        $bulan = $this->request->getGet('bulan') ?: date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $realModel = new SponsorshipRealisasiModel();
        $spModel   = new SponsorshipSponsorModel();

        $monthRows = $realModel->getAvailableMonths($programIds);
        $monthList = array_column($monthRows, 'bulan');
        if (! in_array($bulan, $monthList)) $monthList[] = $bulan;
        rsort($monthList);

        // Per-program realisasi for selected month
        $monthlyReal      = $realModel->getMonthlyByPrograms($bulan, $programIds);
        $committedMap     = $spModel->getCommittedByPrograms($programIds);
        $allTimeRealMap   = $realModel->getTotalByPrograms($programIds);

        // KPIs for selected month
        $kpiTerkumpul  = array_sum($monthlyReal);
        $kpiCommitted  = 0;
        $kpiSponsor    = 0;
        foreach ($programs as $p) {
            $c = $committedMap[$p['id']] ?? [];
            $kpiCommitted += (int)($c['total_nilai']   ?? 0);
            $kpiSponsor   += (int)($c['total_sponsor'] ?? 0);
        }

        // All-time monthly trend
        $allTotals = $realModel->getAllMonthlyTotals($programIds);
        $currentYear = date('Y');
        $allMonthlyTotals = [];
        foreach ($allTotals as $row) {
            if (str_starts_with($row['bulan'], $currentYear)) {
                $allMonthlyTotals[] = $row;
            }
        }

        // Daily chart for selected month
        $dailyRows   = $realModel->getDailyForMonth($bulan, $programIds);
        $daysInMonth = (int)date('t', strtotime($bulan . '-01'));
        $chartDates  = [];
        $dailyNilai  = array_fill(0, $daysInMonth, 0);
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartDates[] = str_pad($d, 2, '0', STR_PAD_LEFT);
        }
        foreach ($dailyRows as $row) {
            $idx = (int)date('j', strtotime($row['tanggal'])) - 1;
            $dailyNilai[$idx] += (int)$row['nilai'];
        }

        // Per-program sponsor breakdown
        $sponsors = $spModel->getByPrograms($programIds);

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
            'allMonthlyTotals' => $allMonthlyTotals,
            'chartDates'       => $chartDates,
            'dailyNilai'       => $dailyNilai,
            'sponsors'         => $sponsors,
        ]);
    }
}
