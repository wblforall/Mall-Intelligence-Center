<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventLocationModel;
use App\Libraries\ActivityLog;

class Events extends BaseController
{
    private EventModel $eventModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('events')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $user       = $this->currentUser();
        $canApprove = $this->canApproveEvents();
        $events     = $this->eventModel->getEventsForUser($user['id'], $user['role'], $canApprove);

        $incompleteCount = count(array_filter($events, fn($e) => $e['status'] === 'waiting_data' && $e['approval_status'] === 'approved'));
        $pendingCount    = $canApprove ? count(array_filter($events, fn($e) => $e['approval_status'] === 'pending')) : 0;

        return view('events/index', [
            'user'            => $user,
            'events'          => $events,
            'incompleteCount' => $incompleteCount,
            'pendingCount'    => $pendingCount,
            'canApprove'      => $canApprove,
        ]);
    }

    public function create()
    {
        if (! $this->canEditMenu('content')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Hanya Event & Promo yang dapat membuat event.');
        }
        $locModel = new EventLocationModel();
        return view('events/create', [
            'user'      => $this->currentUser(),
            'locations' => $locModel->where('aktif', 1)->orderBy('mall')->orderBy('nama')->findAll(),
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('content')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $rules = [
            'name'       => 'required|min_length[3]',
            'mall'       => 'required',
            'start_date' => 'required|valid_date',
            'end_date'   => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date');
        $eventDays = max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);

        $eventId = $this->eventModel->insert([
            'name'       => $this->request->getPost('name'),
            'tema'       => $this->request->getPost('tema'),
            'content'    => $this->request->getPost('content'),
            'mall'       => $this->request->getPost('mall'),
            'start_date' => $startDate,
            'event_days' => $eventDays,
            'status'     => 'draft',
            'created_by' => $this->currentUser()['id'],
        ]);

        $locationIds = array_filter(array_map('intval', (array)($this->request->getPost('location_ids') ?? [])));
        if ($locationIds) {
            (new EventLocationModel())->syncEventLocations((int)$eventId, $locationIds);
        }

        ActivityLog::write('create', 'event', (string)$eventId, $this->request->getPost('name'), [
            'mall' => $this->request->getPost('mall'), 'start_date' => $this->request->getPost('start_date'),
        ]);
        return redirect()->to("/events/{$eventId}/content")->with('success', 'Event berhasil dibuat. Lengkapi rundown acara.');
    }

    public function show(int $id)
    {
        return redirect()->to("/events/{$id}/summary");
    }

    public function edit(int $id)
    {
        $event = $this->eventModel->find($id);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');
        $locModel = new EventLocationModel();
        return view('events/edit', [
            'user'                => $this->currentUser(),
            'event'               => $event,
            'locations'           => $locModel->where('aktif', 1)->orderBy('mall')->orderBy('nama')->findAll(),
            'selectedLocationIds' => $locModel->getEventLocationIds($id),
        ]);
    }

    public function update(int $id)
    {
        $event = $this->eventModel->find($id);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $rules = [
            'name'       => 'required|min_length[3]',
            'mall'       => 'required',
            'start_date' => 'required|valid_date',
            'end_date'   => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date');
        $eventDays = max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);

        $this->eventModel->update($id, [
            'name'       => $this->request->getPost('name'),
            'tema'       => $this->request->getPost('tema'),
            'mall'       => $this->request->getPost('mall'),
            'start_date' => $startDate,
            'event_days' => $eventDays,
        ]);
        $locationIds = array_filter(array_map('intval', (array)($this->request->getPost('location_ids') ?? [])));
        (new EventLocationModel())->syncEventLocations($id, $locationIds);

        ActivityLog::write('update', 'event', (string)$id, $this->request->getPost('name'), [
            'before' => ['name' => $event['name'], 'mall' => $event['mall']],
            'after'  => ['name' => $this->request->getPost('name'), 'mall' => $this->request->getPost('mall')],
        ]);
        return redirect()->to("/events/{$id}/summary")->with('success', 'Event berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/events')->with('error', 'Hanya admin yang bisa menghapus event.');
        }
        $event = $this->eventModel->find($id);
        $this->eventModel->delete($id);
        ActivityLog::write('delete', 'event', (string)$id, $event['name'] ?? '', [
            'mall' => $event['mall'] ?? '', 'status' => $event['status'] ?? '',
        ]);
        return redirect()->to('/events')->with('success', 'Event berhasil dihapus.');
    }

    public function approve(int $id)
    {
        if (! $this->canApproveEvents()) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }
        $event = $this->eventModel->find($id);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $this->eventModel->update($id, [
            'approval_status' => 'approved',
            'approved_by'     => $this->currentUser()['id'],
            'approved_at'     => date('Y-m-d H:i:s'),
            'rejection_reason'=> null,
        ]);

        ActivityLog::write('approve', 'event', (string)$id, $event['name']);
        return redirect()->to('/events')->with('success', 'Event "' . $event['name'] . '" telah disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->canApproveEvents()) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }
        $event = $this->eventModel->find($id);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $reason = trim($this->request->getPost('rejection_reason') ?? '');
        if ($reason === '') {
            return redirect()->to('/events')->with('error', 'Alasan penolakan wajib diisi.');
        }

        $this->eventModel->update($id, [
            'approval_status'  => 'rejected',
            'approved_by'      => $this->currentUser()['id'],
            'approved_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ]);

        ActivityLog::write('reject', 'event', (string)$id, $event['name'], ['reason' => $reason]);
        return redirect()->to('/events')->with('success', 'Event "' . $event['name'] . '" ditolak.');
    }
}
