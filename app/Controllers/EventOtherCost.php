<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventBudgetModel;
use App\Models\DepartmentModel;
use App\Libraries\ActivityLog;

class EventOtherCost extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('budget')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $items       = (new EventBudgetModel())->getByEvent($eventId);
        $departments = (new DepartmentModel())->orderBy('name')->findAll();
        $total       = array_sum(array_column($items, 'jumlah'));

        return view('other_cost/index', [
            'user'        => $this->currentUser(),
            'event'       => $event,
            'items'       => $items,
            'departments' => $departments,
            'total'       => $total,
            'canEdit'     => $this->canEditMenu('budget'),
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('budget')) {
            return redirect()->to("/events/{$eventId}/other-cost")->with('error', 'Akses ditolak.');
        }

        $post    = $this->request->getPost();
        $jumlah  = (int)str_replace([',', '.', ' '], '', $post['jumlah'] ?? 0);
        $model   = new EventBudgetModel();

        $newId = $model->insert([
            'event_id'      => $eventId,
            'department_id' => (int)$post['department_id'],
            'kategori'      => trim($post['kategori']),
            'keterangan'    => trim($post['keterangan'] ?? ''),
            'jumlah'        => $jumlah,
            'created_by'    => $this->currentUser()['id'],
        ]);

        ActivityLog::write('create', 'other_cost', (string)$newId, $post['kategori'], [
            'event_id' => $eventId,
            'jumlah'   => $jumlah,
        ]);

        return redirect()->to("/events/{$eventId}/other-cost")->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if (! $this->canEditMenu('budget')) {
            return redirect()->to("/events/{$eventId}/other-cost")->with('error', 'Akses ditolak.');
        }

        $post   = $this->request->getPost();
        $jumlah = (int)str_replace([',', '.', ' '], '', $post['jumlah'] ?? 0);

        (new EventBudgetModel())->update($id, [
            'department_id' => (int)$post['department_id'],
            'kategori'      => trim($post['kategori']),
            'keterangan'    => trim($post['keterangan'] ?? ''),
            'jumlah'        => $jumlah,
        ]);

        ActivityLog::write('update', 'other_cost', (string)$id, $post['kategori'], [
            'event_id' => $eventId,
            'jumlah'   => $jumlah,
        ]);

        return redirect()->to("/events/{$eventId}/other-cost")->with('success', 'Item berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('budget')) {
            return redirect()->to("/events/{$eventId}/other-cost")->with('error', 'Akses ditolak.');
        }

        $item = (new EventBudgetModel())->find($id);
        (new EventBudgetModel())->delete($id);

        ActivityLog::write('delete', 'other_cost', (string)$id, $item['kategori'] ?? '', ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/other-cost")->with('success', 'Item berhasil dihapus.');
    }
}
