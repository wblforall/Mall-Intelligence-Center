<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventRundownModel;
use App\Models\EventContentItemModel;
use App\Models\EventCompletionModel;
use App\Models\EventLocationModel;
use App\Models\EventContentRealisasiModel;
use App\Libraries\ActivityLog;

class EventContent extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('content')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $items     = (new EventContentItemModel())->getByEvent($eventId);
        $locModel  = new EventLocationModel();
        $locations = $locModel->getEventLocations($eventId) ?: $locModel->getByMall($event['mall']);
        $realisasi = (new EventContentRealisasiModel())->getGroupedByEvent($eventId);

        $programs       = array_filter($items, fn($i) => ($i['tipe'] ?? 'program') === 'program');
        $biayaItems     = array_filter($items, fn($i) => ($i['tipe'] ?? 'program') === 'biaya');
        $budgetProgram  = array_sum(array_column(array_values($programs), 'budget'));
        $budgetBiaya    = array_sum(array_column(array_values($biayaItems), 'budget'));
        $totalBudget    = $budgetProgram + $budgetBiaya;
        $totalRealisasi = 0;
        foreach ($realisasi as $rGroup) { $totalRealisasi += array_sum(array_column($rGroup, 'nilai')); }
        $pctGlobal      = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : 0;
        $barGlobal      = $pctGlobal >= 100 ? 'danger' : ($pctGlobal >= 75 ? 'warning' : 'success');

        return view('content/index', [
            'user'           => $this->currentUser(),
            'event'          => $event,
            'items'          => $items,
            'programs'       => $programs,
            'biayaItems'     => $biayaItems,
            'budgetProgram'  => $budgetProgram,
            'budgetBiaya'    => $budgetBiaya,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'pctGlobal'      => $pctGlobal,
            'barGlobal'      => $barGlobal,
            'locations'      => $locations,
            'realisasi'      => $realisasi,
            'completion'     => ($completion = (new EventCompletionModel())->getByEvent($eventId)['content'] ?? null),
            'canEdit'        => $this->canEditMenu('content') && ! $completion,
        ]);
    }

    public function rundown(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $rundown = (new EventRundownModel())->getByEvent($eventId);

        $grouped = [];
        foreach ($rundown as $r) {
            $grouped[$r['hari_ke']][] = $r;
        }

        return view('content/rundown', [
            'user'       => $this->currentUser(),
            'event'      => $event,
            'grouped'    => $grouped,
            'completion' => ($completion = (new EventCompletionModel())->getByEvent($eventId)['content'] ?? null),
            'canEdit'    => $this->canEditMenu('content') && ! $completion,
        ]);
    }

    public function printRundown(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $rundown = (new EventRundownModel())->getByEvent($eventId);
        $grouped = [];
        foreach ($rundown as $r) {
            $grouped[$r['hari_ke']][] = $r;
        }

        return view('content/rundown_print', [
            'event'   => $event,
            'grouped' => $grouped,
        ]);
    }

    public function saveContent(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        (new EventModel())->update($eventId, [
            'content' => $this->request->getPost('content'),
        ]);

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Deskripsi berhasil disimpan.');
    }

    public function addItem(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post       = $this->request->getPost();
        $itemModel  = new EventContentItemModel();
        $count      = $itemModel->where('event_id', $eventId)->countAllResults();

        $tipe   = $post['tipe'] === 'biaya' ? 'biaya' : 'program';
        $itemId = $itemModel->insert([
            'event_id'      => $eventId,
            'nama'          => $post['nama'],
            'tipe'          => $tipe,
            'tanggal'       => $tipe === 'program' ? ($post['tanggal'] ?: null) : null,
            'waktu_mulai'   => $tipe === 'program' ? ($post['waktu_mulai'] ?: null) : null,
            'waktu_selesai' => $tipe === 'program' ? ($post['waktu_selesai'] ?: null) : null,
            'jenis'         => $post['jenis'] ?? null,
            'pic'           => $post['pic'] ?? null,
            'lokasi'        => $tipe === 'program' ? ($post['lokasi'] ?? null) : null,
            'budget'        => (int) str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'keterangan'    => $post['keterangan'] ?? null,
            'urutan'        => $count + 1,
            'created_by'    => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'content', (string)$itemId, $post['nama'], ['event_id' => $eventId, 'tipe' => $tipe, 'budget' => $post['budget'] ?? 0]);

        // Auto-sync to rundown only for program type
        if ($tipe === 'program' && ! empty($post['tanggal'])) {
            $item = $itemModel->find($itemId);
            (new EventRundownModel())->syncFromContentItem($item, $eventId, strtotime($event['start_date']));
        }

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil ditambahkan.');
    }

    public function editItem(int $eventId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post      = $this->request->getPost();
        $itemModel = new EventContentItemModel();

        $existing = $itemModel->find($id);
        $tipe     = $existing['tipe'] ?? 'program';

        $itemModel->update($id, [
            'nama'          => $post['nama'],
            'tanggal'       => $tipe === 'program' ? ($post['tanggal'] ?: null) : null,
            'waktu_mulai'   => $tipe === 'program' ? ($post['waktu_mulai'] ?: null) : null,
            'waktu_selesai' => $tipe === 'program' ? ($post['waktu_selesai'] ?: null) : null,
            'jenis'         => $post['jenis'] ?? null,
            'pic'           => $post['pic'] ?? null,
            'lokasi'        => $tipe === 'program' ? ($post['lokasi'] ?? null) : null,
            'budget'        => (int) str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'keterangan'    => $post['keterangan'] ?? null,
        ]);
        ActivityLog::write('update', 'content', (string)$id, $post['nama'], ['event_id' => $eventId]);

        // Re-sync to rundown only for program type
        $item = $itemModel->find($id);
        if ($tipe === 'program') {
            if (! empty($item['tanggal'])) {
                (new EventRundownModel())->syncFromContentItem($item, $eventId, strtotime($event['start_date']));
            } else {
                (new EventRundownModel())->deleteByContentItem($id);
            }
        }

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil diperbarui.');
    }

    public function deleteItem(int $eventId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $item = (new EventContentItemModel())->find($id);

        $db->transStart();
        (new EventRundownModel())->deleteByContentItem($id);
        (new EventContentItemModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/content")->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        ActivityLog::write('delete', 'content', (string)$id, $item['nama'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil dihapus.');
    }

    public function storeRealisasi(int $eventId, int $itemId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
        $uploadDir = FCPATH . 'uploads/content-realisasi/' . $eventId . '/';
        if (! is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileFoto   = null;
        $fileTerima = null;

        $foto = $this->request->getFile('file_foto');
        if ($foto && $foto->isValid() && ! $foto->hasMoved()) {
            if ($err = $this->validateUpload($foto, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'foto_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($foto);
            $foto->move($uploadDir, $name);
            $fileFoto = $name;
        }

        $terima = $this->request->getFile('file_terima');
        if ($terima && $terima->isValid() && ! $terima->hasMoved()) {
            if ($err = $this->validateUpload($terima, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'terima_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($terima);
            $terima->move($uploadDir, $name);
            $fileTerima = $name;
        }

        $nilai = (int) str_replace([',', '.', ' '], '', $post['nilai'] ?? 0);
        (new EventContentRealisasiModel())->insert([
            'event_id'        => $eventId,
            'content_item_id' => $itemId,
            'tanggal'         => $post['tanggal'] ?: null,
            'nilai'           => $nilai,
            'catatan'         => $post['catatan'] ?? null,
            'file_foto'       => $fileFoto,
            'file_terima'     => $fileTerima,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'content_realisasi', (string)$itemId, 'Realisasi Content', ['event_id' => $eventId, 'tanggal' => $post['tanggal'] ?? null, 'nilai' => $nilai]);

        return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $itemId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $model = new EventContentRealisasiModel();
        $row   = $model->find($id);
        if ($row) {
            $dir = FCPATH . 'uploads/content-realisasi/' . $eventId . '/';
            if ($row['file_foto']   && file_exists($dir . $row['file_foto']))   unlink($dir . $row['file_foto']);
            if ($row['file_terima'] && file_exists($dir . $row['file_terima'])) unlink($dir . $row['file_terima']);
            $model->delete($id);
            ActivityLog::write('delete', 'content_realisasi', (string)$id, 'Realisasi Content', ['event_id' => $eventId, 'item_id' => $itemId]);
        }

        return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('success', 'Realisasi berhasil dihapus.');
    }

    public function saveRundown(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/rundown")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post     = $this->request->getPost();
        $model    = new EventRundownModel();
        $tanggals = $post['tanggal'] ?? [];

        $model->deleteByEvent($eventId);

        $startTs = strtotime($event['start_date']);

        foreach ($tanggals as $i => $tanggal) {
            if (empty($post['sesi'][$i])) continue;

            $hariKe = (int)((strtotime($tanggal) - $startTs) / 86400) + 1;

            $model->insert([
                'event_id'      => $eventId,
                'hari_ke'       => $hariKe,
                'tanggal'       => $tanggal,
                'waktu_mulai'   => $post['waktu_mulai'][$i] ?: null,
                'waktu_selesai' => $post['waktu_selesai'][$i] ?: null,
                'sesi'          => $post['sesi'][$i],
                'deskripsi'     => $post['deskripsi'][$i] ?? null,
                'pic'           => $post['pic'][$i] ?? null,
                'lokasi'        => $post['lokasi'][$i] ?? null,
                'urutan'        => $i + 1,
            ]);
        }

        return redirect()->to("/events/{$eventId}/rundown")->with('success', 'Rundown berhasil disimpan.');
    }
}
