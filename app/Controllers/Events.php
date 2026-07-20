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
            'canCreate'       => $this->can('can_create_event') || $this->canEditMenu('content'),
        ]);
    }

    public function create()
    {
        if (! $this->can('can_create_event') && ! $this->canEditMenu('content')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Anda tidak memiliki izin membuat event.');
        }
        $locModel = new EventLocationModel();
        return view('events/create', [
            'user'      => $this->currentUser(),
            'locations' => $locModel->where('aktif', 1)->orderBy('mall')->orderBy('nama')->findAll(),
        ]);
    }

    public function store()
    {
        if (! $this->can('can_create_event') && ! $this->canEditMenu('content')) {
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

        ActivityLog::captureBefore($event);
        $eventData = [
            'name'       => $this->request->getPost('name'),
            'tema'       => $this->request->getPost('tema'),
            'mall'       => $this->request->getPost('mall'),
            'start_date' => $startDate,
            'event_days' => $eventDays,
        ];
        $this->eventModel->update($id, $eventData);
        ActivityLog::captureAfter($eventData);
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
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $db = db_connect();

        // Program loyalty event ini (dipakai utk kumpul foto & hapus anak bersarang).
        $loyaltyProgIds = array_column(
            $db->table('event_loyalty_programs')->select('id')->where('event_id', $id)->get()->getResultArray(), 'id');

        // Kumpulkan nama file bukti loyalty (foto flat di loyalty-realisasi) — via program_id.
        $flatLoyaltyFotos = [];
        if ($loyaltyProgIds) {
            foreach (['event_loyalty_hadiah_realisasi', 'event_loyalty_voucher_realisasi'] as $t) {
                $flatLoyaltyFotos = array_merge($flatLoyaltyFotos, array_column(
                    $db->table($t)->select('foto')->whereIn('program_id', $loyaltyProgIds)
                       ->where('foto IS NOT NULL')->get()->getResultArray(), 'foto'));
            }
        }

        // Hapus seluruh sub-data event dalam satu transaksi (tak ada FK cascade di DB).
        $db->transStart();

        // 1. Anak bersarang loyalty (via program_id) — hapus sebelum induknya.
        if ($loyaltyProgIds) {
            foreach (['event_loyalty_voucher_realisasi', 'event_loyalty_voucher_items',
                      'event_loyalty_hadiah_realisasi', 'event_loyalty_hadiah_items'] as $t) {
                $db->table($t)->whereIn('program_id', $loyaltyProgIds)->delete();
            }
        }
        // 2. Item sponsor (via sponsor_id).
        $sponsorIds = array_column(
            $db->table('event_sponsors')->select('id')->where('event_id', $id)->get()->getResultArray(), 'id');
        if ($sponsorIds) {
            $db->table('event_sponsor_items')->whereIn('sponsor_id', $sponsorIds)->delete();
        }
        // 3. Promo media hanya di-lepas (workflow terpisah, bukan milik event).
        $db->table('promo_media_usage')->where('event_id', $id)->update(['event_id' => null]);

        // 4. Semua tabel anak ber-event_id.
        foreach ([
            'event_loyalty_realisasi', 'event_loyalty_programs',
            'event_sponsor_realisasi', 'event_sponsors',
            'event_content_realisasi', 'event_content_items',
            'event_creative_realisasi', 'event_creative_files', 'event_creative_insight', 'event_creative_items',
            'event_vm_realisasi', 'event_vm_items',
            'event_exhibitor_targets', 'event_exhibitors', 'exhibitor_programs',
            'event_rundown', 'event_budgets', 'event_location_map', 'event_completions',
        ] as $t) {
            $db->table($t)->where('event_id', $id)->delete();
        }
        // 5. Event-nya sendiri.
        $db->table('events')->where('id', $id)->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/events')->with('error', 'Gagal menghapus event — tidak ada perubahan disimpan.');
        }

        // Hapus file fisik setelah commit sukses (best-effort).
        foreach (['content-realisasi', 'sponsor-realisasi', 'creative', 'vm'] as $dir) {
            $this->rrmdir(FCPATH . 'uploads/' . $dir . '/' . $id);
        }
        foreach (array_filter($flatLoyaltyFotos) as $f) {
            $p = FCPATH . 'uploads/loyalty-realisasi/' . basename($f);
            if (is_file($p)) @unlink($p);
        }

        ActivityLog::write('delete', 'event', (string)$id, $event['name'] ?? '', [
            'mall' => $event['mall'] ?? '', 'status' => $event['status'] ?? '',
        ]);
        return redirect()->to('/events')->with('success', 'Event beserta seluruh data sub-modulnya berhasil dihapus.');
    }

    // Hapus folder + isinya secara rekursif (aman bila folder tak ada).
    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $dir . '/' . $f;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
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
