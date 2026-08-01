<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * Pusat notifikasi in-app GENERIK (lonceng navbar) — tabel `notifications`.
 * Kanban = produser pertama; modul lain memakai endpoint yang sama.
 */
class Notifications extends BaseController
{
    private function uid(): int
    {
        return (int) $this->currentUser()['id'];
    }

    /** Daftar notif terbaru + jumlah unread (JSON, dipanggil dropdown lonceng). */
    public function index()
    {
        $m = new NotificationModel();
        return $this->response->setJSON([
            'success' => true,
            'unread'  => $m->unreadCount($this->uid()),
            'items'   => $m->latestFor($this->uid(), 20),
            'csrf'    => csrf_hash(),
        ]);
    }

    /** Tandai terbaca: satu notif (id) atau semua (all=1). */
    public function markRead()
    {
        $m = new NotificationModel();
        if ((string) $this->request->getPost('all') === '1') {
            db_connect()->table('notifications')->where('user_id', $this->uid())->where('is_read', 0)->update(['is_read' => 1]);
        } else {
            $id = (int) $this->request->getPost('id');
            $n = $m->find($id);
            if ($n && (int) $n['user_id'] === $this->uid()) $m->update($id, ['is_read' => 1]);
        }
        return $this->response->setJSON([
            'success' => true,
            'unread'  => $m->unreadCount($this->uid()),
            'csrf'    => csrf_hash(),
        ]);
    }
}
