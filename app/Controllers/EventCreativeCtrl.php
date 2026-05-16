<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeFileModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Models\EventCompletionModel;
use App\Libraries\ActivityLog;

class EventCreativeCtrl extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('creative')) return null;
        return (new EventModel())->find($eventId);
    }

    private function uploadDir(int $eventId): string
    {
        $dir = FCPATH . 'uploads/creative/' . $eventId . '/';
        if (! is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $itemModel = new EventCreativeItemModel();
        $items     = $itemModel->getByEvent($eventId);
        $itemIds   = array_column($items, 'id');

        $files     = (new EventCreativeFileModel())->getGroupedByItems($itemIds);
        $realisasi = (new EventCreativeRealisasiModel())->getGroupedByItems($itemIds);
        $insights  = (new EventCreativeInsightModel())->getGroupedByItems($itemIds);

        $byTipe         = [];
        foreach ($items as $item) { $byTipe[$item['tipe']][] = $item; }
        $totalBudget    = array_sum(array_column($items, 'budget'));
        $totalRealisasi = array_sum(array_map(fn($r) => $r['total'] ?? 0, $realisasi));

        $completion = (new EventCompletionModel())->getByEvent($eventId)['creative'] ?? null;

        return view('creative/index', [
            'user'           => $this->currentUser(),
            'event'          => $event,
            'items'          => $items,
            'byTipe'         => $byTipe,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'files'          => $files,
            'realisasi'      => $realisasi,
            'completion'     => $completion,
            'canEdit'        => $this->canEditMenu('creative') && ! $completion,
            'insights'       => $insights,
            'canApprove'     => in_array($this->currentUser()['role'] ?? '', ['admin', 'manager']),
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
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
        ]);
        ActivityLog::write('create', 'creative', (string)$cid, $post['nama'], ['event_id' => $eventId, 'tipe' => $post['tipe'], 'budget' => $post['budget'] ?? 0]);

        return redirect()->to("/events/{$eventId}/creative")->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
        $isDigital = ($post['tipe'] === 'digital');
        (new EventCreativeItemModel())->update($id, [
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
        ActivityLog::write('update', 'creative', (string)$id, $post['nama'], ['event_id' => $eventId, 'budget' => $post['budget'] ?? 0]);

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $db        = db_connect();
        $fileModel = new EventCreativeFileModel();
        $files     = $fileModel->where('creative_item_id', $id)->findAll();
        $dir       = $this->uploadDir($eventId);
        $citem     = (new EventCreativeItemModel())->find($id);

        $db->transStart();
        $fileModel->where('creative_item_id', $id)->delete();
        (new EventCreativeRealisasiModel())->where('creative_item_id', $id)->delete();
        (new EventCreativeItemModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/creative")->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        // Hapus file fisik setelah DB berhasil
        foreach ($files as $f) {
            if (file_exists($dir . $f['file_name'])) unlink($dir . $f['file_name']);
        }

        ActivityLog::write('delete', 'creative', (string)$id, $citem['nama'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/creative")->with('success', 'Item berhasil dihapus.');
    }

    public function uploadFile(int $eventId, int $id)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $file = $this->request->getFile('file_upload');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'File tidak valid.');
        }

        if ($err = $this->validateUpload($file, array_merge(self::MIME_IMAGE, self::MIME_DOC), 20)) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', $err);
        }
        $name     = 'creative_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
        $origName = $file->getClientName();
        $file->move($this->uploadDir($eventId), $name);

        (new EventCreativeFileModel())->insert([
            'creative_item_id' => $id,
            'event_id'         => $eventId,
            'file_name'        => $name,
            'original_name'    => $origName,
            'catatan'          => $this->request->getPost('catatan') ?? null,
            'uploaded_by'      => $this->currentUser()['id'],
        ]);

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'File berhasil diupload.');
    }

    public function deleteFile(int $eventId, int $id, int $fileId)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $fileModel = new EventCreativeFileModel();
        $row       = $fileModel->find($fileId);
        if ($row) {
            $path = $this->uploadDir($eventId) . $row['file_name'];
            if (file_exists($path)) unlink($path);
            $fileModel->delete($fileId);
        }

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'File berhasil dihapus.');
    }

    public function updateStatus(int $eventId, int $id)
    {
        $user   = $this->currentUser();
        $status = $this->request->getPost('status');

        $approveStatuses = ['approved', 'revision'];
        if (in_array($status, $approveStatuses) && ! in_array($user['role'] ?? '', ['admin', 'manager'])) {
            return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('error', 'Hanya admin/manager yang bisa approve.');
        }

        (new EventCreativeItemModel())->update($id, ['status' => $status]);

        $labels = ['draft' => 'Draft', 'review' => 'Diajukan untuk review', 'approved' => 'Approved', 'revision' => 'Perlu revisi'];
        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Status: ' . ($labels[$status] ?? $status));
    }

    public function storeRealisasi(int $eventId, int $id)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $fileName = null;
        $origName = null;

        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $fileName = 'real_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $origName = $file->getClientName();
            $file->move($this->uploadDir($eventId), $fileName);
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
            $stFile->move($this->uploadDir($eventId), $stFileName);
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
            $btFile->move($this->uploadDir($eventId), $btFileName);
        }

        (new EventCreativeRealisasiModel())->insert([
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

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $id, int $rid)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $model = new EventCreativeRealisasiModel();
        $row   = $model->find($rid);
        if ($row) {
            $dir = $this->uploadDir($eventId);
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($row[$col] && file_exists($dir . $row[$col])) unlink($dir . $row[$col]);
            }
        }
        $model->delete($rid);

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Realisasi berhasil dihapus.');
    }

    public function storeInsight(int $eventId, int $id)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $item     = (new EventCreativeItemModel())->find($id);
        $fileName = null;
        $origName = null;

        $file = $this->request->getFile('screenshot');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext      = $file->getClientExtension();
            $fileName = 'insight_' . $id . '_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            $origName = $file->getClientName();
            $file->move($this->uploadDir($eventId), $fileName);
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

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Insight berhasil disimpan.');
    }

    public function deleteInsight(int $eventId, int $id, int $iid)
    {
        if (! $this->canEditMenu('creative')) return redirect()->to("/events/{$eventId}/creative")->with('error', 'Akses ditolak.');

        $model = new EventCreativeInsightModel();
        $row   = $model->find($iid);
        if ($row && $row['file_name']) {
            $path = $this->uploadDir($eventId) . $row['file_name'];
            if (file_exists($path)) unlink($path);
        }
        $model->delete($iid);

        return redirect()->to("/events/{$eventId}/creative#item-{$id}")->with('success', 'Insight berhasil dihapus.');
    }
}
