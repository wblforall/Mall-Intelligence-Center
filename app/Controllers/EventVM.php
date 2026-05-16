<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventVMModel;
use App\Models\EventVMRealisasiModel;
use App\Models\EventCompletionModel;
use App\Libraries\ActivityLog;

class EventVM extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('vm')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $items     = (new EventVMModel())->getByEvent($eventId);
        $realisasi = (new EventVMRealisasiModel())->getGroupedByEvent($eventId);

        $totalBudgetVM    = array_sum(array_column($items, 'budget'));
        $totalRealisasiVM = array_sum(array_column($realisasi, 'total'));
        $totalPct         = $totalBudgetVM > 0 ? min(100, round($totalRealisasiVM / $totalBudgetVM * 100)) : 0;

        return view('vm/index', [
            'user'             => $this->currentUser(),
            'event'            => $event,
            'items'            => $items,
            'realisasi'        => $realisasi,
            'totalBudgetVM'    => $totalBudgetVM,
            'totalRealisasiVM' => $totalRealisasiVM,
            'totalPct'         => $totalPct,
            'completion'       => ($completion = (new EventCompletionModel())->getByEvent($eventId)['vm'] ?? null),
            'canEdit'          => $this->canEditMenu('vm') && ! $completion,
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        $vmModel = new EventVMModel();
        $newId   = $vmModel->insert([
            'event_id'            => $eventId,
            'nama_item'           => $post['nama_item'],
            'deskripsi_referensi' => $post['deskripsi_referensi'] ?? null,
            'budget'              => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'             => $post['catatan'] ?? null,
            'tanggal_deadline'    => $post['tanggal_deadline'] ?: null,
            'created_by'          => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'vm', (string)$newId, $post['nama_item'], ['event_id' => $eventId, 'budget' => $post['budget'] ?? 0]);

        return redirect()->to("/events/{$eventId}/vm")->with('success', 'Item dekorasi berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new EventVMModel())->update($id, [
            'nama_item'           => $post['nama_item'],
            'deskripsi_referensi' => $post['deskripsi_referensi'] ?? null,
            'budget'              => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'             => $post['catatan'] ?? null,
            'tanggal_deadline'    => $post['tanggal_deadline'] ?: null,
        ]);
        ActivityLog::write('update', 'vm', (string)$id, $post['nama_item'], ['event_id' => $eventId, 'budget' => $post['budget'] ?? 0]);

        return redirect()->to("/events/{$eventId}/vm")->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $item = (new EventVMModel())->find($id);

        $db->transStart();
        (new EventVMRealisasiModel())->where('vm_item_id', $id)->delete();
        (new EventVMModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/vm")->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        ActivityLog::write('delete', 'vm', (string)$id, $item['nama_item'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/vm")->with('success', 'Item berhasil dihapus.');
    }

    public function storeRealisasi(int $eventId, int $itemId)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
        $fotoName  = null;
        $fotoOrig  = null;
        $file      = $this->request->getFile('foto');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $uploadPath = FCPATH . 'uploads/vm/' . $eventId . '/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fotoName = 'real_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
            $fotoOrig = $file->getClientName();
            $file->move($uploadPath, $fotoName);
        }

        $jumlah = (int)str_replace([',', '.', ' '], '', $post['jumlah'] ?? 0);
        (new EventVMRealisasiModel())->insert([
            'vm_item_id'         => $itemId,
            'event_id'           => $eventId,
            'tanggal'            => $post['tanggal'],
            'jumlah'             => $jumlah,
            'catatan'            => $post['catatan'] ?? null,
            'foto_file_name'     => $fotoName,
            'foto_original_name' => $fotoOrig,
            'created_by'         => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'vm_realisasi', (string)$itemId, 'Realisasi VM', ['event_id' => $eventId, 'tanggal' => $post['tanggal'], 'jumlah' => $jumlah, 'catatan' => $post['catatan'] ?? null]);

        return redirect()->to("/events/{$eventId}/vm#item-{$itemId}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $entry = (new EventVMRealisasiModel())->find($rid);
        if ($entry && $entry['foto_file_name']) {
            $path = FCPATH . 'uploads/vm/' . $eventId . '/' . $entry['foto_file_name'];
            if (file_exists($path)) unlink($path);
        }
        (new EventVMRealisasiModel())->delete($rid);
        ActivityLog::write('delete', 'vm_realisasi', (string)$rid, 'Realisasi VM', ['event_id' => $eventId, 'item_id' => $itemId]);
        return redirect()->to("/events/{$eventId}/vm#item-{$itemId}")->with('success', 'Realisasi berhasil dihapus.');
    }
}
