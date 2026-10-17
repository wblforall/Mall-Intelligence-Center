<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventLocationModel;
use App\Models\BudgetPosModel;
use App\Models\RabDokumenModel;
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

        // Data pendamping daftar (hanya baca): modul yang sudah ditandai selesai & lokasi tiap event.
        $ids       = array_column($events, 'id');
        $doneMap   = [];
        $locMap    = [];
        if ($ids) {
            $db = \Config\Database::connect();
            foreach ($db->table('event_completions')->select('event_id, module')->whereIn('event_id', $ids)->get()->getResultArray() as $r) {
                $doneMap[(int) $r['event_id']][] = $r['module'];
            }
            foreach ($db->table('event_location_map elm')->select('elm.event_id, el.nama')
                        ->join('event_locations el', 'el.id = elm.location_id')
                        ->whereIn('elm.event_id', $ids)->orderBy('el.mall')->orderBy('el.nama')
                        ->get()->getResultArray() as $r) {
                $locMap[(int) $r['event_id']][] = $r['nama'];
            }
        }

        return view('events/index', [
            'user'            => $user,
            'events'          => $events,
            'doneMap'         => $doneMap,
            'locMap'          => $locMap,
            'requiredModules' => \App\Models\EventCompletionModel::REQUIRED_MODULES,
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
            'user'       => $this->currentUser(),
            'locations'  => $locModel->where('aktif', 1)->orderBy('mall')->orderBy('nama')->findAll(),
            'posPilihan' => (new BudgetPosModel())->untukEvent(),
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
            'jenis'      => $this->request->getPost('jenis') === 'rutin' ? 'rutin' : 'event',
            'pos_id'     => $this->posSah($this->request->getPost('pos_id')),
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

        // Paket item awal Creative (item "Rencana") untuk event baru
        (new \App\Services\CreativePaketService())->tambahkan('event', (int) $eventId, (string) $this->request->getPost('name'), (int) $this->currentUser()['id']);

        // Event baru berstatus pending → beri tahu pemegang izin approve,
        // kalau tidak mereka harus rajin membuka daftar event untuk tahu.
        \App\Libraries\Notify::send(
            \App\Libraries\OrgRecipients::merge(\App\Libraries\OrgRecipients::withRolePerm('can_approve_events'), \App\Libraries\OrgRecipients::menuApprovers('events')),
            (int) $this->currentUser()['id'], 'event', 'approval',
            'Event menunggu persetujuan: ' . $this->request->getPost('name'),
            ($this->currentUser()['name'] ?? '') . ' · ' . ucfirst((string) $this->request->getPost('mall')),
            'event', (int) $eventId, 'events'
        );

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
            'posPilihan'          => (new BudgetPosModel())->untukEvent(),
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

        $eventData = [
            'name'       => $this->request->getPost('name'),
            'tema'       => $this->request->getPost('tema'),
            'jenis'      => $this->request->getPost('jenis') === 'rutin' ? 'rutin' : 'event',
            'pos_id'     => $this->posSah($this->request->getPost('pos_id')),
            'mall'       => $this->request->getPost('mall'),
            'start_date' => $startDate,
            'event_days' => $eventDays,
        ];

        // Biaya event ini sudah tercantum di RAB yang diajukan/disetujui: mal, pos
        // bawaan, dan tanggal ikut menentukan angka RAB → ubah lewat revisi RAB.
        if ($nomor = $this->rabMengunci($id)) {
            $berubah = [];
            if ($eventData['mall'] !== $event['mall'])                              $berubah[] = 'mal';
            if ((int) $eventData['pos_id'] !== (int) $event['pos_id'])               $berubah[] = 'pos budget';
            if ($startDate !== $event['start_date'] || $eventDays !== (int) $event['event_days']) $berubah[] = 'tanggal';
            if ($berubah) {
                return redirect()->back()->withInput()->with('error', 'Biaya event ini sudah masuk ' . $nomor . '. '
                    . ucfirst(implode(', ', $berubah)) . ' tidak bisa diubah sebelum RAB direvisi (Budget Departemen → RAB → Revisi).');
            }
        }
        ActivityLog::captureBefore($event);
        // Event yang DITOLAK lalu direvisi → diajukan ulang (kembali pending),
        // jejak penolakan lama dibersihkan. Tanpa ini penolakan bersifat final:
        // pembuat merevisi tapi statusnya tetap "ditolak" selamanya.
        $diajukanUlang = ($event['approval_status'] ?? '') === 'rejected';
        if ($diajukanUlang) {
            $eventData['approval_status']  = 'pending';
            $eventData['rejection_reason'] = null;
            $eventData['approved_by']      = null;
            $eventData['approved_at']      = null;
        }

        $this->eventModel->update($id, $eventData);
        ActivityLog::captureAfter($eventData);

        if ($diajukanUlang) {
            \App\Libraries\Notify::send(
                \App\Libraries\OrgRecipients::merge(\App\Libraries\OrgRecipients::withRolePerm('can_approve_events'), \App\Libraries\OrgRecipients::menuApprovers('events')),
                (int) $this->currentUser()['id'], 'event', 'approval',
                'Event diajukan ulang: ' . $this->request->getPost('name'),
                'Sudah direvisi setelah ditolak — menunggu persetujuan.',
                'event', $id, 'events'
            );
        }
        $locationIds = array_filter(array_map('intval', (array)($this->request->getPost('location_ids') ?? [])));
        (new EventLocationModel())->syncEventLocations($id, $locationIds);

        ActivityLog::write('update', 'event', (string)$id, $this->request->getPost('name'), [
            'before' => ['name' => $event['name'], 'mall' => $event['mall']],
            'after'  => ['name' => $this->request->getPost('name'), 'mall' => $this->request->getPost('mall')],
        ]);
        return redirect()->to("/events/{$id}/summary")->with('success', 'Event berhasil diperbarui.');
    }

    /** Pos boleh dipakai event hanya pos aktif dept yang terhubung ke Events. */
    private function posSah($posId): ?int
    {
        $posId = (int) $posId;
        if (! $posId) return null;
        return in_array($posId, array_map('intval', array_column((new BudgetPosModel())->untukEvent(), 'id')), true) ? $posId : null;
    }

    /** Nomor RAB aktif yang mencantumkan biaya event ini (null = tidak terkunci). */
    private function rabMengunci(int $eventId): ?string
    {
        $kunci = (new RabDokumenModel())->itemTerkunci();
        if (! $kunci) return null;
        $ids = array_column(db_connect()->table('event_content_items')->select('id')->where('event_id', $eventId)->get()->getResultArray(), 'id');
        foreach ($ids as $i) if (isset($kunci[(int) $i])) return $kunci[(int) $i];
        return null;
    }

    public function delete(int $id)
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/events')->with('error', 'Hanya admin yang bisa menghapus event.');
        }
        $event = $this->eventModel->find($id);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');
        if ($nomor = $this->rabMengunci($id)) {
            return redirect()->to("/events/{$id}/summary")->with('error', 'Biaya event ini tercantum di ' . $nomor . ' — revisi RAB dulu sebelum menghapus event.');
        }

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

        // 3b. Turunan Design / Motion Graphic milik item Creative event (tabelnya ber-item_id, bukan event_id).
        \App\Services\CreativeTurunanService::hapusUntukInduk(
            'event',
            array_column($db->table('event_creative_items')->select('id')->where('event_id', $id)->get()->getResultArray(), 'id')
        );

        // 3c. Titik Media Cetak milik item Creative event: lepas pemesanan terhubung di Media Promo (yang sudah 'done' tetap riwayat).
        foreach ($db->table('event_creative_items')->select('id')->where('event_id', $id)->where('tipe', 'cetak')->get()->getResultArray() as $ci) {
            \App\Services\CreativeTitikService::forEvent((int) $id)->lepasUntukItem((int) $ci['id']);
        }

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

        // Notifikasi hasil ke pembuat event
        \App\Libraries\Notify::send(
            [(int) ($event['created_by'] ?? 0)], (int) $this->currentUser()['id'], 'event', 'result',
            'Event disetujui: ' . $event['name'], null, 'event', $id, 'events/' . $id . '/summary'
        );

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

        // Notifikasi hasil + alasan ke pembuat event
        \App\Libraries\Notify::send(
            [(int) ($event['created_by'] ?? 0)], (int) $this->currentUser()['id'], 'event', 'result',
            'Event ditolak: ' . $event['name'], 'Alasan: ' . $reason, 'event', $id, 'events'
        );

        return redirect()->to('/events')->with('success', 'Event "' . $event['name'] . '" ditolak.');
    }
}
