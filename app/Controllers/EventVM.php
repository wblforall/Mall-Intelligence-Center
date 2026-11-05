<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventVMModel;
use App\Models\EventVMRealisasiModel;
use App\Libraries\EventFsPo;
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
        $realisasi = EventFsPo::tandaiGrup('vm', (new EventVMRealisasiModel())->getGroupedByEvent($eventId));

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

        $post    = $this->request->getPost();
        $vmModel = new EventVMModel();
        ActivityLog::captureBefore($vmModel->find($id));
        $vmData = [
            'nama_item'           => $post['nama_item'],
            'deskripsi_referensi' => $post['deskripsi_referensi'] ?? null,
            'budget'              => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'catatan'             => $post['catatan'] ?? null,
            'tanggal_deadline'    => $post['tanggal_deadline'] ?: null,
        ];
        $vmModel->update($id, $vmData);
        ActivityLog::captureAfter($vmData);
        ActivityLog::write('update', 'vm', (string)$id, $post['nama_item'], ['event_id' => $eventId, 'budget' => $post['budget'] ?? 0]);

        return redirect()->to("/events/{$eventId}/vm")->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $item = (new EventVMModel())->find($id);

        if (! $item || (int) $item['event_id'] !== $eventId) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Item tidak ditemukan.');

        $db->transStart();
        (new EventFsPo($db))->lepas('vm', array_column((new EventVMRealisasiModel())->select('id')->where('vm_item_id', $id)->findAll(), 'id'), (int) $this->currentUser()['id']);
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
        $item = (new EventVMModel())->find($itemId);
        if (! $item || (int) $item['event_id'] !== $eventId) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Item tidak ditemukan.');

        $post = $this->request->getPost();
        // PO FlowStore VM (opsional): nilai & tanggal mengikuti BTB yang dipilih; barisnya keluar dari antrean FlowStore.
        $fsPo = new EventFsPo();
        [$errPo, $siapPo, $nilaiPo, $tglPo] = $fsPo->siapkan('vm', $post['fs_po'] ?? null);
        if ($errPo !== null) return redirect()->back()->withInput()->with('error', $errPo);

        $fotoName  = null;
        $fotoOrig  = null;
        $uploadPath = FCPATH . 'uploads/vm/' . $eventId . '/';
        $file      = $this->request->getFile('foto');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 10)) {
                return redirect()->back()->with('error', $err);
            }
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fotoName = 'real_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
            $fotoOrig = $file->getClientName();
            $file->move($uploadPath, $fotoName);
            \App\Libraries\ImageCompressor::compress($uploadPath . '/' . $fotoName);
        }

        [$err, $jumlah, $tanggal] = $this->simpanRealisasi($fsPo, $siapPo, $nilaiPo, $tglPo, $post, $itemId, $eventId, $fotoName, $fotoOrig);
        if ($err !== null) {
            if ($fotoName && is_file($uploadPath . $fotoName)) @unlink($uploadPath . $fotoName);
            return redirect()->back()->withInput()->with('error', $err);
        }
        ActivityLog::write('create', 'vm_realisasi', (string)$itemId, 'Realisasi VM', ['event_id' => $eventId, 'tanggal' => $tanggal, 'jumlah' => $jumlah, 'catatan' => $post['catatan'] ?? null]
            + ($siapPo ? ['po_fs' => array_column($siapPo, 'id')] : []));

        return redirect()->to("/events/{$eventId}/vm#item-{$itemId}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    /**
     * Insert entri realisasi VM (+ tautan PO FlowStore) dalam satu transaksi. Dipakai juga VMStandalone.
     * @return array{0:?string,1:int,2:?string} [pesan error, jumlah, tanggal]
     */
    public static function simpanRealisasi(EventFsPo $fsPo, array $siapPo, int $nilaiPo, ?string $tglPo, array $post, int $itemId, ?int $eventId, ?string $fotoName, ?string $fotoOrig): array
    {
        $uid     = (int) session()->get('user_id');
        $jumlah  = $siapPo ? $nilaiPo : (int) str_replace([',', '.', ' '], '', $post['jumlah'] ?? 0);
        $tanggal = $siapPo ? $tglPo : ($post['tanggal'] ?? null);
        $db = db_connect();
        $db->transBegin();
        $rid = (new EventVMRealisasiModel())->insert([
            'vm_item_id'         => $itemId,
            'event_id'           => $eventId,
            'tanggal'            => $tanggal,
            'jumlah'             => $jumlah,
            'catatan'            => $post['catatan'] ?? null,
            'foto_file_name'     => $fotoName,
            'foto_original_name' => $fotoOrig,
            'created_by'         => $uid,
        ]);
        $err = $rid ? ($siapPo ? $fsPo->tautkan('vm', $siapPo, (int) $rid, $uid) : null) : 'Gagal menyimpan realisasi.';
        if ($err === null && ! $db->transStatus()) $err = 'Gagal menyimpan realisasi.';
        if ($err !== null) {
            $db->transRollback();
            return [$siapPo ? 'Realisasi tidak disimpan: ' . $err : $err, $jumlah, $tanggal];
        }
        $db->transCommit();
        return [null, $jumlah, $tanggal];
    }

    public function deleteRealisasi(int $eventId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('vm')) return redirect()->to("/events/{$eventId}/vm")->with('error', 'Akses ditolak.');

        $entry = (new EventVMRealisasiModel())->where('event_id', $eventId)->where('vm_item_id', $itemId)->find($rid);
        if (! $entry) return redirect()->to("/events/{$eventId}/vm#item-{$itemId}")->with('error', 'Entri realisasi tidak ditemukan.');
        if ($entry['foto_file_name']) {
            $path = FCPATH . 'uploads/vm/' . $eventId . '/' . $entry['foto_file_name'];
            if (file_exists($path)) unlink($path);
        }
        $db = db_connect();
        $db->transStart();
        (new EventFsPo($db))->lepas('vm', [$rid], (int) $this->currentUser()['id']);
        (new EventVMRealisasiModel())->delete($rid);
        $db->transComplete();
        ActivityLog::write('delete', 'vm_realisasi', (string)$rid, 'Realisasi VM', ['event_id' => $eventId, 'item_id' => $itemId]);
        return redirect()->to("/events/{$eventId}/vm#item-{$itemId}")->with('success', 'Realisasi berhasil dihapus.');
    }
}
