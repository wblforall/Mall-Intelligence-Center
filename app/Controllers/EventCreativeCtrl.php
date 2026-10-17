<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeFileModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Models\EventCompletionModel;
use App\Models\CreativeReviewModel;
use App\Libraries\ActivityLog;
use App\Services\CreativeApprovalService;
use App\Services\CreativeTemaService;
use App\Services\CreativeTurunanService;
use App\Services\CreativePaketService;
use App\Services\CreativeTitikService;
use App\Services\CreativeTurunanOtomatis;

class EventCreativeCtrl extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('creative')) return null;
        return (new EventModel())->find($eventId);
    }


    /** Kolom Media Cetak (jenis, ukuran, periode pasang, Design induk); NULL untuk jenis lain. */
    private function cetakFields(array $post): array
    {
        return CreativeTitikService::dataCetak($post);
    }

    private function sinkronTurunan(int $eventId, string $alasan = ''): void
    {
        CreativeTurunanOtomatis::sinkron('e' . $eventId, CreativeTurunanOtomatis::SUMBER_CETAK, $alasan);
    }

    /** Tema event yang sudah ditutup mengunci seluruh materi creative-nya. */
    private function tolakBilaTemaTutup(int $eventId)
    {
        if (CreativeTemaService::eventDitutup($eventId)) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);
        }
        return null;
    }

    private function uploadDir(int $eventId): string
    {
        $dir = FCPATH . 'uploads/creative/' . $eventId . '/';
        if (! is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    /** Tombol "Tambahkan paket awal" di tab Creative event (idempoten; tidak membuat item bernama sama dua kali). */
    public function paketAwal(int $eventId)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');
        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $h = (new CreativePaketService())->tambahkan('event', $eventId, (string) $event['name'], (int) $this->currentUser()['id']);
        $msg = $h['dibuat'] > 0
            ? $h['dibuat'] . ' item rencana ditambahkan' . ($h['dilewati'] ? ', ' . $h['dilewati'] . ' sudah ada (dilewati).' : '.')
            : 'Tidak ada item baru: semua item paket sudah ada di event ini.';
        return redirect()->to("/events/{$eventId}/creative")->with('success', $msg);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $itemModel = new EventCreativeItemModel();
        $items     = $itemModel->getByEvent($eventId);
        $itemIds   = array_column($items, 'id');

        // Item "Rencana" (paket awal, belum dikerjakan) tampil di daftar tetapi tidak ikut statistik
        $nRencana    = count(array_filter($items, fn($it) => ! empty($it['dari_paket'])));
        $itemsStat   = array_values(array_filter($items, fn($it) => empty($it['dari_paket'])));
        $turunan     = CreativeTurunanService::forEvent($eventId)->untukItems(array_column(array_filter($items, fn($it) => CreativeApprovalService::isDesain($it['tipe'])), 'id'));

        $files     = (new EventCreativeFileModel())->getGroupedByItems($itemIds);
        $reviews   = (new CreativeReviewModel())->getGroupedByItems(CreativeApprovalService::SCOPE_EVENT, $itemIds);
        $realisasi = (new EventCreativeRealisasiModel())->getGroupedByItems($itemIds);
        $insights  = (new EventCreativeInsightModel())->getGroupedByItems($itemIds);

        $byTipe         = [];
        foreach ($items as $item) { $byTipe[$item['tipe']][] = $item; }
        $totalBudget    = array_sum(array_column($items, 'budget'));
        $totalRealisasi = array_sum(array_map(fn($r) => $r['total'] ?? 0, $realisasi));

        // ACT: build per-event analysis using same rule-based engine as monthly
        $totalReach = 0; $totalImpr = 0; $totalViews = 0; $totalEng = 0; $totalFoll = 0;
        $topReach = 0; $topName = ''; $activeCount = 0;
        foreach ($itemsStat as $item) {
            $iid = $item['id'];
            $ins = $insights[$iid] ?? null;
            $hasReal = ! empty($realisasi[$iid]['entries']);
            if ($ins || $hasReal) $activeCount++;
            if ($ins) {
                $r = (int)($ins['max_reach'] ?? 0);
                $totalReach += $r;
                $totalImpr  += (int)($ins['max_impressions'] ?? 0);
                $totalViews += (int)($ins['max_views'] ?? 0);
                $totalFoll  += (int)($ins['total_followers_gained'] ?? 0);
                $totalEng   += (int)($ins['max_likes'] ?? 0) + (int)($ins['max_comments'] ?? 0)
                             + (int)($ins['max_shares'] ?? 0) + (int)($ins['max_saves'] ?? 0);
                if ($r > $topReach) { $topReach = $r; $topName = $item['nama']; }
            }
        }
        $engRate   = $totalReach > 0 ? round($totalEng / $totalReach * 100, 1) : 0;
        $serapan   = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : 0;
        $today     = date('Y-m-d');
        $overdue   = array_filter($itemsStat, fn($it) =>
            !empty($it['deadline']) && $it['deadline'] < $today
            && ($it['status'] ?? '') !== 'approved'
        );
        $analysis = $this->buildEventAnalysis([
            'activeCount'    => $activeCount,
            'totalItems'     => count($itemsStat),
            'totalReach'     => $totalReach,
            'totalImpr'      => $totalImpr,
            'totalEng'       => $totalEng,
            'totalFoll'      => $totalFoll,
            'engagementRate' => $engRate,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'serapanPct'     => $serapan,
            'topItemReach'   => $topReach,
            'topItemName'    => $topName,
            'overdueCount'   => count($overdue),
        ]);

        $completion = (new EventCompletionModel())->getByEvent($eventId)['creative'] ?? null;

        return view('creative/index', [
            'user'           => $this->currentUser(),
            'event'          => $event,
            'items'          => $items,
            'byTipe'         => $byTipe,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'files'          => $files,
            'reviews'        => $reviews,
            'realisasi'      => $realisasi,
            'completion'     => $completion,
            'temaDitutup'    => CreativeTemaService::eventDitutup($eventId),
            'canEdit'        => $this->canEditMenu('creative') && ! $completion && ! CreativeTemaService::eventDitutup($eventId),
            'insights'       => $insights,
            'canApprove'     => $this->canApproveCreative(true),
            'analysis'       => $analysis,
            'nRencana'       => $nRencana,
            'turunan'        => $turunan,
            'katalog'        => CreativeTurunanService::katalog(true),
            'cetakKatalog'   => CreativeTurunanService::cetakKatalog(),
            'titik'          => CreativeTitikService::forEvent($eventId)->untukItems(array_column(array_filter($items, fn($it) => $it['tipe'] === 'cetak'), 'id')),
            'designs'        => CreativeTurunanOtomatis::designsTema('e' . $eventId),
            'jenisMaster'    => CreativeTitikService::jenisBerMaster(),
        ]);
    }

    public function store(int $eventId)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
        if (! in_array($post['tipe'] ?? '', CreativeTemaService::TIPE_URUT, true)) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', 'Jenis item tidak dikenal.');
        }
        if ($err = CreativeTitikService::validasiCetak($post)) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', $err);
        }
        $isDigital = ($post['tipe'] === 'digital');
        $cid = (new EventCreativeItemModel())->insert([
            'event_id'     => $eventId,
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
        ] + $this->cetakFields($post));
        ActivityLog::write('create', 'creative', (string)$cid, $post['nama'], ['event_id' => $eventId, 'tipe' => $post['tipe'], 'budget' => $post['budget'] ?? 0]);
        if ($post['tipe'] === 'cetak') $this->sinkronTurunan($eventId);

        return redirect()->to("/events/{$eventId}/creative")->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post              = $this->request->getPost();
        $isDigital         = ($post['tipe'] === 'digital');
        $eventCreativeModel = new EventCreativeItemModel();
        $existing           = $eventCreativeModel->find($id);

        if (CreativeApprovalService::isLocked($existing)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        if ($existing && $existing['tipe'] === 'cetak') $post['tipe'] = 'cetak';
        if ($err = CreativeTitikService::validasiCetak($post)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', $err);
        }
        $cetakBaru = $this->cetakFields($post);
        if ($existing && $existing['tipe'] === 'cetak' && ($err = CreativeTitikService::forEvent($eventId)->selaraskan($existing, $cetakBaru))) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', $err);
        }

        ActivityLog::captureBefore($existing);
        $eventCreativeData = [
            'nama'         => $post['nama'],
            'platform'     => $isDigital ? ($post['platform'] ?? null) : null,
            'tanggal_take' => $isDigital ? ($post['tanggal_take'] ?: null) : null,
            'jam_take'     => $isDigital ? ($post['jam_take'] ?: null) : null,
            'pic'          => $isDigital ? ($post['pic'] ?: null) : null,
            'deskripsi'    => $post['deskripsi'] ?? null,
            'budget'       => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'urutan'       => (int)($post['urutan'] ?? 0),
        ] + $this->cetakFields($post);
        $eventCreativeModel->update($id, $eventCreativeData);
        ActivityLog::captureAfter($eventCreativeData);
        ActivityLog::write('update', 'creative', (string)$id, $post['nama'], ['event_id' => $eventId, 'budget' => $post['budget'] ?? 0]);
        if ($existing && $existing['tipe'] === 'cetak') {
            CreativeTitikService::forEvent($eventId)->segarkan($id);
            $berubah = ($existing['jenis_media'] ?? '') !== ($cetakBaru['jenis_media'] ?? '') || ($existing['ukuran'] ?? '') !== ($cetakBaru['ukuran'] ?? '');
            $this->sinkronTurunan($eventId, $berubah ? 'ukuran atau jenis berubah' : '');
        }

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $db        = db_connect();
        $fileModel = new EventCreativeFileModel();
        $files     = $fileModel->where('creative_item_id', $id)->findAll();
        $dir       = $this->uploadDir($eventId);
        $citem     = (new EventCreativeItemModel())->find($id);

        if (CreativeApprovalService::isLocked($citem)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        if (CreativeTurunanService::adaDisetujui(CreativeTurunanService::SCOPE_EVENT, $id) > 0) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                ->with('error', 'Item punya turunan yang sudah disetujui. Buka kembali atau hapus turunannya dulu.');
        }

        $lepas = ($citem && $citem['tipe'] === 'cetak') ? CreativeTitikService::forEvent($eventId)->lepasUntukItem($id) : null;
        $db->transStart();
        $fileModel->where('creative_item_id', $id)->delete();
        (new EventCreativeInsightModel())->where('creative_item_id', $id)->delete();
        (new EventCreativeRealisasiModel())->where('creative_item_id', $id)->delete();
        (new EventCreativeItemModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        CreativeTurunanService::hapusUntukInduk(CreativeTurunanService::SCOPE_EVENT, [$id]);

        // Hapus file fisik setelah DB berhasil
        foreach ($files as $f) {
            if (file_exists($dir . $f['file_name'])) unlink($dir . $f['file_name']);
        }

        ActivityLog::write('delete', 'creative', (string)$id, $citem['nama'] ?? '', ['event_id' => $eventId]);
        if ($lepas !== null) $this->sinkronTurunan($eventId, 'item dihapus');
        $msg = 'Item berhasil dihapus.';
        if ($lepas && ($lepas['dilepas'] + $lepas['riwayat']) > 0) {
            $msg .= ' ' . $lepas['dilepas'] . ' pemesanan titik di Media Promo dilepas' . ($lepas['riwayat'] ? ', ' . $lepas['riwayat'] . ' yang sudah selesai tetap sebagai riwayat' : '') . '.';
        }
        return redirect()->to("/events/{$eventId}/creative")->with('success', $msg);
    }

    public function uploadFile(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $item = (new EventCreativeItemModel())->find($id);
        if (CreativeApprovalService::isLocked($item)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        $file = $this->request->getFile('file_upload');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'File tidak valid.');
        }

        if ($err = $this->validateUpload($file, array_merge(self::MIME_IMAGE, self::MIME_DOC), 20)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', $err);
        }
        $name     = 'creative_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
        $origName = $file->getClientName();
        try {
            $file->move($this->uploadDir($eventId), $name);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memindahkan file creative event: ' . $e->getMessage());
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                ->with('error', 'Gagal menyimpan file ke server. Periksa izin folder upload.');
        }
        \App\Libraries\ImageCompressor::compress($this->uploadDir($eventId) . '/' . $name);

        // Satu file = satu opsi desain, kecuali ditandai sebagai file pendukung.
        $meta = CreativeApprovalService::forEvent($eventId)
            ->uploadMeta($id, ! $this->request->getPost('is_pendukung'));

        $fileId = (new EventCreativeFileModel())->insert([
            'creative_item_id' => $id,
            'event_id'         => $eventId,
            'file_name'        => $name,
            'original_name'    => $origName,
            'catatan'          => $this->request->getPost('catatan') ?? null,
            'uploaded_by'      => $this->currentUser()['id'],
        ] + $meta);
        if (! $fileId) {
            // Jangan tinggalkan file di disk tanpa baris DB yang menunjuknya.
            $path = $this->uploadDir($eventId) . $name;
            if (is_file($path)) unlink($path);
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'Gagal menyimpan file.');
        }

        CreativeTemaService::tandaiDikerjakan('event', $id);
        ActivityLog::write('update', 'creative', (string) $eventId, 'Upload file creative', [
            'opsi' => $meta['opsi_label'], 'versi' => $meta['versi'],
        ]);
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with(
            'success',
            $meta['is_opsi']
                ? 'Opsi ' . $meta['opsi_label'] . ' berhasil diupload.'
                : 'File pendukung berhasil diupload.'
        );
    }

    public function deleteFile(int $eventId, int $id, int $fileId)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $item      = (new EventCreativeItemModel())->find($id);
        $fileModel = new EventCreativeFileModel();
        $row       = $fileModel->find($fileId);
        if ($row) {
            if ((int) $row['creative_item_id'] !== $id) {
                return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'File tidak cocok dengan item.');
            }
            if (CreativeApprovalService::fileTerkunci($item, $row)) {
                return redirect()->to("/events/{$eventId}/creative#item-{$id}")
                    ->with('error', CreativeApprovalService::pesanOpsiTerkunci($item));
            }
            $path = $this->uploadDir($eventId) . $row['file_name'];
            $fileModel->delete($fileId);
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'creative', (string) $eventId, 'Hapus file creative');
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'File berhasil dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // Alur persetujuan materi (lihat CreativeApprovalService)
    // ─────────────────────────────────────────────────────────────

    /** Desainer mengirim materi untuk ditinjau. */
    public function ajukan(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');
        }

        return $this->hasilApproval(
            $eventId,
            $id,
            CreativeApprovalService::forEvent($eventId)->ajukan($id, $this->currentUser())
        );
    }

    /** Peninjau menyetujui sekaligus menetapkan opsi yang dipakai. */
    public function setujui(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canApproveCreative(true)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'Anda tidak punya hak menyetujui materi creative.');
        }

        return $this->hasilApproval(
            $eventId,
            $id,
            CreativeApprovalService::forEvent($eventId)->setujui(
                $id,
                (array) ($this->request->getPost('opsi') ?? []),
                $this->currentUser(),
                $this->request->getPost('catatan')
            )
        );
    }

    /** Peninjau meminta revisi — alasan wajib. */
    public function revisi(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canApproveCreative(true)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'Anda tidak punya hak menyetujui materi creative.');
        }

        $basis = $this->request->getPost('basis_file_id');

        return $this->hasilApproval(
            $eventId,
            $id,
            CreativeApprovalService::forEvent($eventId)->revisi(
                $id,
                (string) ($this->request->getPost('catatan') ?? ''),
                $basis !== null && $basis !== '' ? (int) $basis : null,
                $this->currentUser()
            )
        );
    }

    /** Membuka kembali materi yang sudah disetujui agar bisa diubah. */
    public function buka(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative') && ! $this->canApproveCreative(true)) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');
        }

        return $this->hasilApproval(
            $eventId,
            $id,
            CreativeApprovalService::forEvent($eventId)->buka(
                $id,
                (string) ($this->request->getPost('catatan') ?? ''),
                $this->currentUser()
            )
        );
    }

    private function hasilApproval(int $eventId, int $id, array $hasil)
    {
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")
            ->with($hasil['ok'] ? 'success' : 'error', $hasil['message']);
    }

    public function storeRealisasi(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $fileName = null;
        $origName = null;
        $pendingMoves = [];

        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $fileName = 'real_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $origName = $file->getClientName();
            $pendingMoves[] = [$file, $this->uploadDir($eventId), $fileName];
        }

        $stFileName = null;
        $stOrigName = null;
        $stFile = $this->request->getFile('serah_terima');
        if ($stFile && $stFile->isValid() && ! $stFile->hasMoved()) {
            if ($err = $this->validateUpload($stFile, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $stFileName = 'st_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($stFile);
            $stOrigName = $stFile->getClientName();
            $pendingMoves[] = [$stFile, $this->uploadDir($eventId), $stFileName];
        }

        $btFileName = null;
        $btOrigName = null;
        $btFile = $this->request->getFile('bukti_terpasang');
        if ($btFile && $btFile->isValid() && ! $btFile->hasMoved()) {
            if ($err = $this->validateUpload($btFile, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $btFileName = 'bt_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($btFile);
            $btOrigName = $btFile->getClientName();
            $pendingMoves[] = [$btFile, $this->uploadDir($eventId), $btFileName];
        }

        $rid = (new EventCreativeRealisasiModel())->insert([
            'event_id'                      => $eventId,
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
        if (! $rid) {
            return redirect()->back()->with('error', 'Gagal menyimpan realisasi.');
        }
        foreach ($pendingMoves as [$f, $dir, $name]) {
            $f->move($dir, $name);
            \App\Libraries\ImageCompressor::compress($dir . '/' . $name);
        }

        CreativeTemaService::tandaiDikerjakan('event', $id);
        ActivityLog::write('update', 'creative', (string) $eventId, 'Tambah realisasi creative');
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $id, int $rid)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $model = new EventCreativeRealisasiModel();
        $row   = $model->find($rid);
        $model->delete($rid);
        if ($row) {
            $dir = $this->uploadDir($eventId);
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($row[$col] && file_exists($dir . $row[$col])) unlink($dir . $row[$col]);
            }
        }

        ActivityLog::write('delete', 'creative', (string) $eventId, 'Hapus realisasi creative');
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Realisasi berhasil dihapus.');
    }

    public function storeInsight(int $eventId, int $id)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $item     = (new EventCreativeItemModel())->find($id);
        $fileName = null;
        $origName = null;

        $file = $this->request->getFile('screenshot');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $fileName = 'insight_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $this->safeExt($file);
            $origName = $file->getClientName();
            $file->move($this->uploadDir($eventId), $fileName);
            \App\Libraries\ImageCompressor::compress($this->uploadDir($eventId) . '/' . $fileName);
        }

        (new EventCreativeInsightModel())->insert([
            'event_id'         => $eventId,
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

        CreativeTemaService::tandaiDikerjakan('event', $id);
        ActivityLog::write('update', 'creative', (string) $eventId, 'Simpan insight creative');
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Insight berhasil disimpan.');
    }

    public function deleteInsight(int $eventId, int $id, int $iid)
    {
        if ($r = $this->tolakBilaTemaTutup($eventId)) return $r;
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $model = new EventCreativeInsightModel();
        $row   = $model->find($iid);
        $model->delete($iid);
        if ($row && $row['file_name']) {
            $path = $this->uploadDir($eventId) . $row['file_name'];
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'creative', (string) $eventId, 'Hapus insight creative');
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Insight berhasil dihapus.');
    }

    private function buildEventAnalysis(array $m): array
    {
        $a   = [];
        $fmt = fn($n) => number_format((int)$n);
        $rp  = fn($n) => 'Rp ' . number_format((int)$n, 0, ',', '.');

        if ($m['activeCount'] <= 0) {
            return ['Belum ada aktivitas (realisasi/insight) creative yang tercatat untuk event ini.'];
        }
        $a[] = "{$m['activeCount']} dari {$m['totalItems']} item creative aktif di event ini.";

        if ($m['totalReach'] > 0) {
            $a[] = "Total reach " . $fmt($m['totalReach']) . " dari seluruh konten digital.";
        }
        if ($m['totalEng'] > 0) {
            $r    = $m['engagementRate'];
            $nilai = $r >= 3 ? 'tergolong sangat baik' : ($r >= 1 ? 'tergolong sehat' : 'tergolong rendah');
            $a[] = "Engagement " . $fmt($m['totalEng']) . " (rate {$r}% dari reach), {$nilai}.";
        }
        if ($m['totalBudget'] > 0) {
            $s   = $m['serapanPct'];
            $cat = $s > 100 ? 'melebihi budget' : ($s >= 80 ? 'mendekati batas budget' : 'masih dalam batas aman');
            $a[] = "Realisasi " . $rp($m['totalRealisasi']) . "; serapan budget {$s}% ({$cat}).";
        } elseif ($m['totalRealisasi'] > 0) {
            $a[] = "Realisasi " . $rp($m['totalRealisasi']) . " (budget belum di-set).";
        }
        if ($m['totalFoll'] > 0) {
            $a[] = "Pertumbuhan follower +" . $fmt($m['totalFoll']) . " dari konten event ini.";
        }
        if ($m['topItemReach'] > 0) {
            $a[] = "Konten terbaik: \"{$m['topItemName']}\" — " . $fmt($m['topItemReach']) . " reach.";
        }

        $rec = [];
        if ($m['totalEng'] > 0 && $m['engagementRate'] < 1) {
            $rec[] = 'tingkatkan konten interaktif untuk mendongkrak engagement';
        }
        if ($m['totalBudget'] > 0 && $m['serapanPct'] < 50) {
            $rec[] = 'percepat realisasi agar serapan budget tidak menumpuk';
        }
        if (($m['overdueCount'] ?? 0) > 0) {
            $a[] = ($m['overdueCount'] === 1 ? '1 item' : $m['overdueCount'] . ' item') . ' melewati deadline dan belum approved — segera tindak lanjuti.';
        }
        if ($rec) $a[] = 'Rekomendasi: ' . implode('; ', $rec) . '.';

        return $a;
    }
}
