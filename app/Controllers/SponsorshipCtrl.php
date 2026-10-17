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
use App\Libraries\SponsorshipDobel;

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

    // ── Bantuan tampilan gabungan (program mandiri + event) ───────────────────

    /** Hak mengelola pendeteksi dobel = hak ubah menu Sponsorship (admin otomatis). User hanya-lihat tidak melihat apa pun soal dobel. */
    private function bolehDobel(): bool
    {
        return $this->canEditMenu('sponsorship_main');
    }

    /** "50× Pastry; 10× MUG" dari baris item; kosong bila tak ada. */
    private function teksItem(array $items): string
    {
        $out = [];
        foreach ($items as $it) {
            $d = trim((string)($it['deskripsi_barang'] ?? ''));
            $q = (int)($it['qty'] ?? 0);
            if ($d === '' && $q <= 0) continue;
            $out[] = ($q > 0 ? $q . '× ' : '') . $d;
        }
        return implode('; ', $out);
    }

    /**
     * Rincian sponsor seragam untuk Summary & daftar: satu bentuk untuk sponsor program maupun event.
     * 'dobel' berisi keterangan "dicatat di ..." bila sponsor itu dikecualikan dari total.
     */
    private function rincianSponsor(array $rows, array $itemsBySp, array $realBySp, array $exIds, array $labelKec, bool $isProgram): array
    {
        $out = [];
        foreach ($rows as $sp) {
            $sid = (int)$sp['id'];
            $txt = $this->teksItem($itemsBySp[$sid] ?? []);
            if ($txt === '' && ! $isProgram) {
                $q = (int)($sp['qty'] ?? 0);
                $d = trim((string)($sp['deskripsi_barang'] ?? ''));
                $txt = ($q > 0 ? $q . '× ' : '') . $d;
            }
            if ($txt === '') $txt = trim((string)($sp['detail'] ?? ''));
            $ex = in_array($sid, $exIds, true);
            $out[] = [
                'id'     => $sid,
                'nama'   => (string)$sp['nama_sponsor'],
                'jenis'  => (string)$sp['jenis'],
                'rincian'=> trim($txt, ' ;'),
                'nilai'  => (int)$sp['nilai'],
                'status' => $isProgram ? (string)($sp['status_deal'] ?? '') : null,
                'real'   => (int)($realBySp[$sid] ?? 0),
                'dobel'  => $ex ? ($labelKec[$sid] ?? 'sisi lain') : null,
            ];
        }
        return $out;
    }

    /**
     * Sponsor per event untuk tampilan: daftar event beragregat (sudah mengecualikan dobel) + rincian sponsornya.
     *
     * @return array{aggs:array, rincian:array<int,array>}
     */
    private function dataEventSponsor(array $exE, array $labelKecE): array
    {
        $evModel  = new EventSponsorModel();
        $evrModel = new EventSponsorRealisasiModel();
        $aggs     = $evModel->getEventAggregates($exE);
        $ids      = array_column($aggs, 'event_id');
        $cum      = $evrModel->getCumulativeByEvents('9999-12', $ids, $exE);
        $itemModel = new \App\Models\EventSponsorItemModel();
        $rincian  = [];
        foreach ($aggs as &$e) {
            $eid = (int)$e['event_id'];
            $e['deal']      = (int)$e['total_cash'] + (int)$e['total_barang'];
            $e['kumulatif'] = (int)($cum[$eid] ?? 0);
            $rows  = $evModel->getByEvent($eid);
            $items = [];
            foreach ($itemModel->getBySponsorIds(array_column($rows, 'id')) as $it) { $items[(int)$it['sponsor_id']][] = $it; }
            $real = [];
            foreach ($evrModel->getGroupedByEvent($eid) as $sid => $rr) { $real[(int)$sid] = array_sum(array_column($rr, 'nilai')); }
            $rincian[$eid] = $this->rincianSponsor($rows, $items, $real, $exE, $labelKecE, false);
        }
        unset($e);
        return ['aggs' => $aggs, 'rincian' => $rincian];
    }

    /** Tambahkan peringatan lunak (tidak memblokir simpan) bila sponsor serupa sudah ada di event beririsan. */
    private function denganPeringatanDobel($redirect, int $programId, int $nilai, string $nama, string $statusDeal)
    {
        if (! $this->bolehDobel()) return $redirect;
        if (! in_array($statusDeal, SponsorshipDobel::STATUS_DEAL, true)) return $redirect;   // prospek/batal tidak masuk total
        $serupa = SponsorshipDobel::cariSerupa('program', $programId, $nama, $nilai);
        return $serupa ? $redirect->with('warning', SponsorshipDobel::teksPeringatan($serupa)) : $redirect;
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

        // Bagian baca-saja "Sponsor dari Event" + (bila berhak) ringkasan pendeteksi dobel.
        $kec       = SponsorshipDobel::idDikecualikan();
        $labelKec  = SponsorshipDobel::labelDikecualikan();
        $evData    = $this->dataEventSponsor($kec['event'], $labelKec['event']);
        $bolehDobel = $this->bolehDobel();
        $excludedProgSp = $kec['program'];
        $progLabelKec   = $labelKec['program'];

        return view('sponsorship/index', [
            'user'                => $this->currentUser(),
            'eventAggs'           => $evData['aggs'],
            'rincianE'            => $evData['rincian'],
            'bolehDobel'          => $bolehDobel,
            'dobel'               => $bolehDobel ? SponsorshipDobel::deteksi() : null,
            'exProgSp'            => $excludedProgSp,
            'labelKecProg'        => $progLabelKec,
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
        return $this->denganPeringatanDobel(
            redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Sponsor berhasil ditambahkan.'),
            $programId, (int)($model->find($id)['nilai'] ?? 0), trim($post['nama_sponsor']), $post['status_deal'] ?? 'prospek'
        );
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
        return $this->denganPeringatanDobel(
            redirect()->to('/sponsorship#program-' . $programId)->with('success', 'Sponsor berhasil diperbarui.'),
            $programId, (int)($model->find($sponsorId)['nilai'] ?? 0), trim($post['nama_sponsor']), $post['status_deal'] ?? 'prospek'
        );
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

        $bulanSekarang = date('Y-m');
        $bulanGet = $this->request->getGet('bulan');
        $bulan = $bulanGet ?: $bulanSekarang;
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) { $bulan = $bulanSekarang; $bulanGet = null; }

        $realModel = new SponsorshipRealisasiModel();
        $spModel   = new SponsorshipSponsorModel();

        // Pengecualian dobel: SATU sumber (SponsorshipDobel::idDikecualikan) dipakai oleh semua agregat di bawah.
        $kec = SponsorshipDobel::idDikecualikan();
        $exP = $kec['program'];
        $exE = $kec['event'];

        // ── Sponsor dari EVENT (ikutkan ke summary; sebelumnya hanya standalone) ──
        $evModel   = new EventSponsorModel();
        $evrModel  = new EventSponsorRealisasiModel();
        $eventAggs = $evModel->getEventAggregates($exE);
        $eventIds  = array_column($eventAggs, 'event_id');

        // Total realisasi per bulan, semua tahun (standalone + event digabung per bulan)
        $monthMap = [];
        foreach ($realModel->getAllMonthlyTotals($programIds, $exP) as $r) { $monthMap[$r['bulan']] = (int)$r['total_nilai']; }
        foreach ($evrModel->getAllMonthlyTotals($eventIds, $exE) as $r)    { $monthMap[$r['bulan']] = ($monthMap[$r['bulan']] ?? 0) + (int)$r['total_nilai']; }
        ksort($monthMap);

        // Bulan bawaan cerdas: bila parameter `bulan` TIDAK diberikan dan bulan berjalan belum punya
        // realisasi sama sekali, pakai bulan terbaru (<= bulan berjalan) yang punya realisasi.
        // Bulan yang diminta eksplisit selalu dihormati.
        $bulanFallback = null;
        if (! $bulanGet && (int)($monthMap[$bulan] ?? 0) <= 0) {
            $adaReal = array_keys(array_filter($monthMap, fn($v) => (int)$v > 0));
            rsort($adaReal);
            foreach ($adaReal as $m) {
                if ($m <= $bulanSekarang) { $bulan = $m; $bulanFallback = $m; break; }
            }
            if ($bulanFallback === null && $adaReal) { $bulan = $adaReal[0]; $bulanFallback = $bulan; }
        }

        // Daftar bulan dropdown: gabungan bulan REALISASI + PERIODE program +
        // periode event. Sebelumnya hanya dari realisasi — bila realisasi masih
        // kosong, dropdown cuma berisi bulan berjalan sehingga bulan lain (yang
        // programnya ada) tak bisa dipilih.
        $monthSet = [$bulan => true];
        foreach ($realModel->getAvailableMonths($programIds) as $r) { $monthSet[$r['bulan']] = true; }
        foreach ($evrModel->getAllMonthlyTotals($eventIds, $exE) as $r)    { $monthSet[$r['bulan']] = true; }
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
        $monthlyReal      = $realModel->getMonthlyByPrograms($bulan, $programIds, $exP);
        $committedMap     = $spModel->getCommittedByPrograms($programIds, $exP);
        $allTimeRealMap   = $realModel->getTotalByPrograms($programIds, $exP);
        $evMonthly = $evrModel->getMonthlyByEvents($bulan, $eventIds, $exE);   // realisasi bulan ini per event
        $evGrand   = array_sum(array_column($evrModel->getAllMonthlyTotals($eventIds, $exE), 'total_nilai'));
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

        // All-time monthly trend (standalone + event, digabung per bulan) — $monthMap dihitung di atas
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
        foreach (array_merge($realModel->getDailyForMonth($bulan, $programIds, $exP), $evrModel->getDailyForMonth($bulan, $eventIds, $exE)) as $row) {
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

        // ── Pandangan gabungan: rincian sponsor per sumber + (bila berhak) panel dobel ──
        $labelKec  = SponsorshipDobel::labelDikecualikan();
        $evData    = $this->dataEventSponsor($exE, $labelKec['event']);
        $eventAggs = $evData['aggs'];
        foreach ($eventAggs as &$e) { $e['realisasi'] = (int)($evMonthly[(int)$e['event_id']] ?? 0); }
        unset($e);

        $allSpIds = [];
        foreach ($sponsors as $list) { foreach ($list as $sp) { $allSpIds[] = (int)$sp['id']; } }
        $itemsP = (new SponsorshipSponsorItemModel())->getBySponsorIds($allSpIds);   // sudah dikelompokkan per sponsor_id
        $realP = [];
        foreach ($realModel->getGroupedBySponsors($allSpIds) as $sid => $g) { $realP[(int)$sid] = (int)$g['total_nilai']; }
        $rincianP = [];
        foreach ($sponsors as $pid => $list) { $rincianP[(int)$pid] = $this->rincianSponsor($list, $itemsP, $realP, $exP, $labelKec['program'], true); }

        $bolehDobel = $this->bolehDobel();

        return view('sponsorship/summary', [
            'user'             => $this->currentUser(),
            'rincianP'         => $rincianP,
            'rincianE'         => $evData['rincian'],
            'nDikecualikan'    => count($exP) + count($exE),
            'bolehDobel'       => $bolehDobel,
            'dobel'            => $bolehDobel ? SponsorshipDobel::deteksi() : null,
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
            // Turunan untuk halaman analisis (tidak mengubah angka di atas)
            'bulanSekarang'    => $bulanSekarang,
            'bulanFallback'    => $bulanFallback,
            'kpiTerkumpulPrev' => $monthMap[$prevBulan] ?? null, // null = bulan lalu tak punya catatan realisasi
            'targetTotal'      => array_sum(array_map(fn($p) => (int)($p['target_nilai'] ?? 0), $programs)),
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

        // ── Model + data mentah (semua program/event, utk tren & filter) ──
        $realModel = new SponsorshipRealisasiModel();
        $spModel   = new SponsorshipSponsorModel();
        $evModel   = new EventSponsorModel();
        $evrModel  = new EventSponsorRealisasiModel();

        // Pengecualian dobel — fungsi yang SAMA dengan summary().
        $kec = SponsorshipDobel::idDikecualikan();
        $exP = $kec['program'];
        $exE = $kec['event'];

        $allPrograms   = (new SponsorshipProgramModel())->getAll();
        $allProgramIds = array_column($allPrograms, 'id');
        $allEventAggs  = $evModel->getEventAggregates($exE);
        $allEventIds   = array_column($allEventAggs, 'event_id');

        // Program/event RELEVAN ke sebuah bulan: periode overlap bulan itu ATAU
        // ada penerimaan bulan itu. Laporan hanya menampilkan & menghitung yang
        // relevan ke $bulan (KPI, pipeline, insight, tabel) — bukan semua program.
        $relProg = function (string $m) use ($allPrograms, $allProgramIds, $realModel, $exP) {
            $ms = $m . '-01'; $me = date('Y-m-t', strtotime($ms));
            $realM = $realModel->getMonthlyByPrograms($m, $allProgramIds, $exP);
            return array_values(array_filter($allPrograms, function ($p) use ($ms, $me, $realM) {
                $mulai = $p['tanggal_mulai'] ?? ''; $selesai = $p['tanggal_selesai'] ?? '';
                if ($mulai && $mulai <= $me && (empty($selesai) || $selesai >= $ms)) return true;
                return (int)($realM[$p['id']] ?? 0) > 0;
            }));
        };
        $relEvent = function (string $m) use ($allEventAggs, $allEventIds, $evrModel, $exE) {
            $evM = $evrModel->getMonthlyByEvents($m, $allEventIds, $exE);
            return array_values(array_filter($allEventAggs, function ($e) use ($m, $evM) {
                if (substr((string)($e['event_start_date'] ?? ''), 0, 7) === $m) return true;
                return (int)($evM[$e['event_id']] ?? 0) > 0;
            }));
        };

        // Scoped ke $bulan
        $programs   = $relProg($bulan);
        $programIds = array_column($programs, 'id');
        $eventAggs  = $relEvent($bulan);
        $eventIds   = array_column($eventAggs, 'event_id');

        $monthlyReal  = $realModel->getMonthlyByPrograms($bulan, $programIds, $exP);
        $prevReal     = $realModel->getMonthlyByPrograms($prevBulan, $programIds, $exP);
        $cumReal      = $realModel->getCumulativeByPrograms($bulan, $programIds, $exP);
        $committedMap = $spModel->getCommittedByPrograms($programIds, $exP);
        $sponsorsMap  = $spModel->getByPrograms($programIds);

        // Pipeline per program (jumlah sponsor per status deal)
        $pipelineMap = [];
        $pipelineTotal = ['prospek' => 0, 'negosiasi' => 0, 'terkonfirmasi' => 0, 'lunas' => 0, 'batal' => 0];
        foreach ($sponsorsMap as $pid => $rows) {
            foreach ($rows as $sp) {
                if (in_array((int)$sp['id'], $exP, true)) continue;   // dikecualikan karena dobel
                $st = $sp['status_deal'] ?? 'prospek';
                $pipelineMap[$pid][$st] = ($pipelineMap[$pid][$st] ?? 0) + 1;
                if (isset($pipelineTotal[$st])) $pipelineTotal[$st]++;
            }
        }

        $evMonthly = $evrModel->getMonthlyByEvents($bulan, $eventIds, $exE);
        $evPrev    = $evrModel->getMonthlyByEvents($prevBulan, $eventIds, $exE);
        $evCum     = $evrModel->getCumulativeByEvents($bulan, $eventIds, $exE);

        // ── Komparasi bulan sebelumnya: program/event relevan ke prevBulan ──
        $prevPrograms   = $relProg($prevBulan);
        $prevProgIds    = array_column($prevPrograms, 'id');
        $prevEvents     = $relEvent($prevBulan);
        $prevEventIds   = array_column($prevEvents, 'event_id');
        $prevCommitted  = $spModel->getCommittedByPrograms($prevProgIds, $exP);
        $prevSponsorDeal = array_sum(array_column($prevCommitted, 'total_sponsor'))
                         + array_sum(array_map(fn($e) => (int)$e['jumlah_sponsor'], $prevEvents));
        $prevKomitmen    = array_sum(array_column($prevCommitted, 'total_nilai'))
                         + array_sum(array_map(fn($e) => (int)$e['total_cash'] + (int)$e['total_barang'], $prevEvents));

        // ── KPI bulan terpilih ────────────────────────────────────────────
        $kpiSponsorDeal = array_sum(array_column($committedMap, 'total_sponsor'))
                        + array_sum(array_map(fn($e) => (int)$e['jumlah_sponsor'], $eventAggs));
        $kpiKomitmen    = array_sum(array_column($committedMap, 'total_nilai'))
                        + array_sum(array_map(fn($e) => (int)$e['total_cash'] + (int)$e['total_barang'], $eventAggs));
        $kpiRealisasi     = array_sum($monthlyReal) + array_sum($evMonthly);
        // Penerimaan bulan lalu = realisasi bulan lalu dari program yang relevan
        // BULAN LALU (bukan program bulan ini di bulan lalu) → komparasi adil.
        $kpiRealisasiPrev = array_sum($realModel->getMonthlyByPrograms($prevBulan, $prevProgIds, $exP))
                          + array_sum($evrModel->getMonthlyByEvents($prevBulan, $prevEventIds, $exE));
        $kpiKumulatif     = array_sum($cumReal)     + array_sum($evCum);

        // Target = program relevan bulan ini (bukan hanya yg berstatus active,
        // karena program bulanan sering sudah inactive namun tetap dilaporkan).
        $targetNilaiAktif = array_sum(array_map(fn($p) => (int)($p['target_nilai'] ?? 0), $programs));
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

        // ── Tren 6 bulan (SEMUA program/event — tren lintas program) ──────
        $trendMap = [];
        foreach ($realModel->getAllMonthlyTotals($allProgramIds, $exP) as $r) {
            $trendMap[$r['bulan']] = ($trendMap[$r['bulan']] ?? 0) + (int)$r['total_nilai'];
        }
        foreach ($evrModel->getAllMonthlyTotals($allEventIds, $exE) as $r) {
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
            $realModel->getDailyForMonth($bulan, $programIds, $exP),
            $evrModel->getDailyForMonth($bulan, $eventIds, $exE)
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
        $insights[] = 'Komitmen deal (terkonfirmasi + lunas) ' . $fmtRp($kpiKomitmen) . ' dari ' . number_format($kpiSponsorDeal, 0, ',', '.') . ' sponsor — ' . $fmtDelta($kpiKomitmen, $prevKomitmen)
            . '; sponsor ' . ($kpiSponsorDeal >= $prevSponsorDeal ? '+' : '') . ($kpiSponsorDeal - $prevSponsorDeal) . ' vs bln lalu (' . $prevSponsorDeal . ').';
        $insights[] = 'Realisasi kumulatif ' . $fmtRp($kpiKumulatif)
            . ($outstanding > 0 ? ' — sisa komitmen belum cair ' . $fmtRp($outstanding) . '.' : '.');
        if ($pipelineTotal['prospek'] + $pipelineTotal['negosiasi'] > 0) {
            $insights[] = 'Pipeline berjalan: ' . $pipelineTotal['prospek'] . ' prospek dan ' . $pipelineTotal['negosiasi'] . ' dalam negosiasi — potensi tambahan penerimaan.';
        }
        if ($topName) $insights[] = 'Penerimaan terbesar bulan ini: ' . $topName . ' (' . $fmtRp($topVal) . ').';
        if ($noActivity > 0) $insights[] = $noActivity . ' program aktif belum mencatat penerimaan bulan ini — perlu ditindaklanjuti.';
        if ($targetNilaiAktif > 0) $insights[] = 'Capaian kumulatif vs target program bulan ini: ' . rtrim(rtrim(number_format($capaianPct, 1, ',', '.'), '0'), ',') . '% dari ' . $fmtRp($targetNilaiAktif) . '.';

        // ── Detail sponsor per program standalone (laporan achievement) ──
        $allSponsorIds = [];
        foreach ($sponsorsMap as $rows) { foreach ($rows as $s) { $allSponsorIds[] = (int)$s['id']; } }
        $realBySponsor = $realModel->getGroupedBySponsors($allSponsorIds); // sponsor_id => ['total_nilai'=>..]

        // ── Detail sponsor per event ──
        $eventSponsors = [];   // event_id => [event_sponsors]
        $eventRealBySp = [];   // event_id => [sponsor_id => realisasi total]
        foreach ($eventAggs as $e) {
            $eid = (int) $e['event_id'];
            $eventSponsors[$eid] = $evModel->getByEvent($eid);
            foreach ($evrModel->getGroupedByEvent($eid) as $sid => $rows) {
                $eventRealBySp[$eid][$sid] = array_sum(array_column($rows, 'nilai'));
            }
        }

        // Sponsor yang dikecualikan karena dobel di antara program/event yang MUNCUL di laporan ini
        // (dasar catatan di blok keterangan). Yang dikecualikan tetap tampil di rincian, dicoret.
        $nKecualiProg = 0; $nKecualiEvent = 0;
        foreach ($sponsorsMap as $rows) { foreach ($rows as $sp) { if (in_array((int)$sp['id'], $exP, true)) $nKecualiProg++; } }
        foreach ($eventSponsors as $rows) { foreach ($rows as $sp) { if (in_array((int)$sp['id'], $exE, true)) $nKecualiEvent++; } }
        $labelKec = SponsorshipDobel::labelDikecualikan();

        // Analisa bulan ini + program yang beranalisa tetapi di luar daftar relevan (tidak memengaruhi KPI).
        $analisaModel = new SponsorshipSummaryAnalysisModel();
        $analisaMap   = $analisaModel->getMapByMonth($bulan);
        $programAnalisaLain = array_values(array_filter(
            $allPrograms,
            fn($p) => isset($analisaMap[(int)$p['id']]) && ! in_array($p['id'], $programIds)
        ));

        return view('sponsorship/print_summary', [
            'bulan'          => $bulan,
            'prevBulan'      => $prevBulan,
            'programs'       => $programs,
            'sponsorsMap'    => $sponsorsMap,
            'realBySponsor'  => $realBySponsor,
            'eventSponsors'  => $eventSponsors,
            'eventRealBySp'  => $eventRealBySp,
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
            'prevSponsorDeal' => $prevSponsorDeal,
            'prevKomitmen'    => $prevKomitmen,
            'kpiRealisasiPrev'=> $kpiRealisasiPrev,
            'targetNilaiAktif' => $targetNilaiAktif,
            'capaianPct'     => $capaianPct,
            'mallCounts'     => $mallCounts,
            'trendMonths'    => $trendMonths,
            'dailyNilai'     => $dailyNilai,
            'insights'       => $insights,
            'analisaMap'     => $analisaMap,
            'prevAnalisaMap' => $analisaModel->getMapByMonth($prevBulan),
            'programAnalisaLain' => $programAnalisaLain,
            'exP'            => $exP,
            'exE'            => $exE,
            'labelKec'       => $labelKec,
            'nKecualiProg'   => $nKecualiProg,
            'nKecualiEvent'  => $nKecualiEvent,
            'signatories'    => \App\Libraries\ReportSignatories::resolve('sponsorship_main'),
            'printedBy'      => $this->currentUser()['name'] ?? '',
            'printedAt'      => date('d M Y H:i'),
        ]);
    }

    // ── Keputusan sponsor dobel (hanya yang berhak) ───────────────────────────

    /** Tujuan kembali yang aman setelah aksi dobel. */
    private function tujuanDobel(): string
    {
        $k = (string)$this->request->getPost('kembali');
        if ($k === 'summary') {
            $b = (string)$this->request->getPost('bulan');
            return '/sponsorship/summary' . (preg_match('/^\d{4}-\d{2}$/', $b) ? '?bulan=' . $b : '') . '#dobel';
        }
        return '/sponsorship#dobel';
    }

    public function putuskanDobel()
    {
        $tujuan = $this->tujuanDobel();
        if (! $this->bolehDobel()) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');

        $aksi = (string)$this->request->getPost('aksi');
        if (! in_array($aksi, ['dobel', 'bukan_dobel'], true)) return redirect()->to($tujuan)->with('error', 'Aksi tidak dikenal.');
        $sisi = (string)$this->request->getPost('sisi');
        if ($aksi === 'dobel' && ! in_array($sisi, ['program', 'event'], true)) $sisi = 'program';   // bawaan: program mandiri dikecualikan
        $catatan = trim((string)$this->request->getPost('catatan'));
        $catatan = $catatan === '' ? null : mb_substr($catatan, 0, 500);

        $minta = [];
        foreach ((array)$this->request->getPost('pasangan') as $v) {
            if (preg_match('/^(\d+):(\d+)$/', (string)$v, $m)) $minta[$m[1] . ':' . $m[2]] = [(int)$m[1], (int)$m[2]];
        }
        if (! $minta) return redirect()->to($tujuan)->with('error', 'Pilih minimal satu pasangan sponsor.');

        // Hanya pasangan yang memang terdeteksi (atau sudah punya keputusan) yang diterima.
        $deteksi = SponsorshipDobel::deteksi();
        $dikenal = [];
        foreach ($deteksi['grup'] as $g) { foreach ($g['pasangan'] as $ps) { $dikenal[$ps['ps_id'] . ':' . $ps['es_id']] = true; } }
        foreach ($deteksi['keputusan'] as $k) { $dikenal[(int)$k['program_sponsor_id'] . ':' . (int)$k['event_sponsor_id']] = true; }

        $db    = db_connect();
        $uid   = (int)$this->currentUser()['id'];
        $now   = date('Y-m-d H:i:s');
        $baru = $ubah = $sama = $ditolak = 0;
        $detail = [];

        $db->transStart();
        foreach ($minta as $kunci => [$psId, $esId]) {
            if (! isset($dikenal[$kunci])) { $ditolak++; continue; }
            $ps = $db->table('sponsorship_sponsors')->where('id', $psId)->get()->getRowArray();
            $es = $db->table('event_sponsors')->where('id', $esId)->get()->getRowArray();
            if (! $ps || ! $es) { $ditolak++; continue; }

            $ada = $db->table('sponsorship_dobel')->where('program_sponsor_id', $psId)->where('event_sponsor_id', $esId)->get()->getRowArray();

            if ($aksi === 'dobel') {
                // Sisi yang DIPERTAHANKAN tidak boleh sudah dikecualikan oleh keputusan lain (total hilang dua kali).
                $dipertahankan = $sisi === 'program' ? ['program', $psId] : ['event', $esId];
                $q = $db->table('sponsorship_dobel')->where('status', 'dobel')->where('sisi_kecuali', $dipertahankan[0]);
                $q->where($dipertahankan[0] === 'program' ? 'program_sponsor_id' : 'event_sponsor_id', $dipertahankan[1]);
                if ($ada) $q->where('id !=', (int)$ada['id']);
                if ($q->countAllResults() > 0) { $ditolak++; continue; }
            }

            $data = ['status' => $aksi, 'sisi_kecuali' => $aksi === 'dobel' ? $sisi : null];
            if ($ada) {
                $serupa = $ada['status'] === $data['status'] && $ada['sisi_kecuali'] === $data['sisi_kecuali']
                    && ($catatan === null || $catatan === $ada['catatan']);
                if ($serupa) { $sama++; continue; }   // idempoten
                $data['catatan'] = $catatan ?? $ada['catatan'];
                $data['diubah_oleh'] = $uid;
                $data['diubah_pada'] = $now;
                $db->table('sponsorship_dobel')->where('id', (int)$ada['id'])->update($data);
                $ubah++;
                $detail[] = ['id' => (int)$ada['id'], 'pasangan' => $kunci, 'dari' => $ada['status'] . '/' . ($ada['sisi_kecuali'] ?? '-'), 'ke' => $data['status'] . '/' . ($data['sisi_kecuali'] ?? '-')];
            } else {
                $data += ['program_sponsor_id' => $psId, 'event_sponsor_id' => $esId, 'catatan' => $catatan, 'dibuat_oleh' => $uid, 'dibuat_pada' => $now];
                $db->table('sponsorship_dobel')->insert($data);
                $baru++;
                $detail[] = ['id' => (int)$db->insertID(), 'pasangan' => $kunci, 'status' => $data['status'], 'sisi_kecuali' => $data['sisi_kecuali'],
                             'program_sponsor' => $ps['nama_sponsor'], 'event_sponsor' => $es['nama_sponsor']];
            }
        }
        $db->transComplete();
        SponsorshipDobel::lupakanMemo();

        if ($baru + $ubah > 0) {
            ActivityLog::write($baru > 0 ? 'create' : 'update', 'sponsorship_dobel', null,
                ($aksi === 'dobel' ? 'Konfirmasi dobel' : 'Bukan dobel') . ' · ' . ($baru + $ubah) . ' pasangan',
                ['aksi' => $aksi, 'sisi_kecuali' => $aksi === 'dobel' ? $sisi : null, 'catatan' => $catatan, 'pasangan' => $detail]);
        }

        $label = $aksi === 'dobel' ? 'dikonfirmasi dobel (sisi ' . ($sisi === 'program' ? 'program mandiri' : 'event') . ' dikecualikan dari total)' : 'ditandai bukan dobel';
        $pesan = ($baru + $ubah) . ' pasangan sponsor ' . $label . '.';
        if ($sama > 0)    $pesan .= ' ' . $sama . ' sudah berstatus sama.';
        $redirect = redirect()->to($tujuan);
        if ($ditolak > 0) $redirect = $redirect->with('warning', $ditolak . ' pasangan dilewati (tidak dikenali, sponsor sudah dihapus, atau sisi yang dipertahankan sudah dikecualikan oleh keputusan lain).');
        return $redirect->with($baru + $ubah + $sama > 0 ? 'success' : 'error', $baru + $ubah + $sama > 0 ? $pesan : 'Tidak ada pasangan yang diproses.');
    }

    public function batalkanDobel()
    {
        $tujuan = $this->tujuanDobel();
        if (! $this->bolehDobel()) return redirect()->to('/sponsorship')->with('error', 'Akses ditolak.');

        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->request->getPost('id')))));
        if (! $ids) return redirect()->to($tujuan)->with('error', 'Tidak ada keputusan yang dipilih.');

        $db   = db_connect();
        $rows = $db->table('sponsorship_dobel')->whereIn('id', $ids)->get()->getResultArray();
        if ($rows) {
            $db->table('sponsorship_dobel')->whereIn('id', array_column($rows, 'id'))->delete();
            SponsorshipDobel::lupakanMemo();
            ActivityLog::write('delete', 'sponsorship_dobel', null, 'Batalkan keputusan dobel · ' . count($rows) . ' pasangan',
                ['dibatalkan' => array_map(fn($r) => [
                    'id' => (int)$r['id'], 'program_sponsor_id' => (int)$r['program_sponsor_id'], 'event_sponsor_id' => (int)$r['event_sponsor_id'],
                    'status' => $r['status'], 'sisi_kecuali' => $r['sisi_kecuali'],
                ], $rows)]);
        }
        $n = count($rows);
        return redirect()->to($tujuan)->with('success', $n > 0 ? $n . ' keputusan dibatalkan; angka gabungan dihitung ulang.' : 'Keputusan itu sudah tidak ada.');
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
