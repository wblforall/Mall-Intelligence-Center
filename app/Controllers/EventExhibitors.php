<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventExhibitorModel;
use App\Models\EventExhibitorProgramModel;
use App\Models\EventExhibitorTargetModel;
use App\Models\EventCompletionModel;
use App\Libraries\ActivityLog;

class EventExhibitors extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('exhibitors')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $model      = new EventExhibitorModel();
        $exhibitors = $model->getByEvent($eventId);
        $programs   = (new EventExhibitorProgramModel())->getByEventGrouped($eventId);

        $completion = (new EventCompletionModel())->getByEvent($eventId)['exhibitors'] ?? null;

        return view('exhibitors/index', [
            'user'            => $this->currentUser(),
            'event'           => $event,
            'exhibitors'      => $exhibitors,
            'programs'        => $programs,
            'kategoriOptions' => $model->getKategoriOptions($eventId),
            'completion'      => $completion,
            'canEdit'         => $this->canEditMenu('exhibitors') && ! $completion,
            'target'          => (new EventExhibitorTargetModel())->getByEvent($eventId) ?? [],
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('exhibitors')) return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $kategori = $post['kategori'] === 'Lainnya'
            ? trim($post['kategori_custom'] ?? '')
            : $post['kategori'];
        $exId = (new EventExhibitorModel())->insert([
            'event_id'       => $eventId,
            'nama_exhibitor' => $post['nama_exhibitor'],
            'kategori'       => $kategori,
            'nilai_dealing'  => (int)str_replace([',', '.', ' '], '', $post['nilai_dealing'] ?? 0),
            'lokasi_booth'   => $post['lokasi_booth'] ?? null,
            'catatan'        => $post['catatan'] ?? null,
            'created_by'     => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'exhibitor', (string)$exId, $post['nama_exhibitor'], ['event_id' => $eventId, 'kategori' => $kategori, 'nilai_dealing' => $post['nilai_dealing'] ?? 0]);

        return redirect()->to("/events/{$eventId}/exhibitors")->with('success', 'Exhibitor berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if (! $this->canEditMenu('exhibitors')) return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $kategori = $post['kategori'] === 'Lainnya'
            ? trim($post['kategori_custom'] ?? '')
            : $post['kategori'];
        (new EventExhibitorModel())->update($id, [
            'nama_exhibitor' => $post['nama_exhibitor'],
            'kategori'       => $kategori,
            'nilai_dealing'  => (int)str_replace([',', '.', ' '], '', $post['nilai_dealing'] ?? 0),
            'lokasi_booth'   => $post['lokasi_booth'] ?? null,
            'catatan'        => $post['catatan'] ?? null,
        ]);
        ActivityLog::write('update', 'exhibitor', (string)$id, $post['nama_exhibitor'], ['event_id' => $eventId, 'nilai_dealing' => $post['nilai_dealing'] ?? 0]);

        return redirect()->to("/events/{$eventId}/exhibitors")->with('success', 'Exhibitor berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('exhibitors')) return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');
        $ex = (new EventExhibitorModel())->find($id);
        (new EventExhibitorModel())->delete($id);
        ActivityLog::write('delete', 'exhibitor', (string)$id, $ex['nama_exhibitor'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/exhibitors")->with('success', 'Exhibitor berhasil dihapus.');
    }

    public function saveTarget(int $eventId)
    {
        if (! $this->canEditMenu('exhibitors')) {
            return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');
        }

        $post        = $this->request->getPost();
        $targetJumlah = (int)($post['target_jumlah'] ?? 0);
        $targetNilai  = (int)str_replace([',', '.', ' '], '', $post['target_nilai_dealing'] ?? 0);

        (new EventExhibitorTargetModel())->saveTarget($eventId, $targetJumlah, $targetNilai);
        ActivityLog::write('update', 'exhibitor_target', (string)$eventId, 'Target Exhibition', ['event_id' => $eventId, 'target_jumlah' => $targetJumlah, 'target_nilai_dealing' => $targetNilai]);

        return redirect()->to("/events/{$eventId}/exhibitors")->with('success', 'Target exhibition berhasil disimpan.');
    }

    public function addProgram(int $eventId, int $exhibitorId)
    {
        if (! $this->canEditMenu('exhibitors')) return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new EventExhibitorProgramModel())->insert([
            'exhibitor_id'    => $exhibitorId,
            'event_id'        => $eventId,
            'nama_program'    => $post['nama_program'],
            'tanggal_mulai'   => $post['tanggal_mulai'] ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'jam_mulai'       => $post['jam_mulai'] ?: null,
            'jam_selesai'     => $post['jam_selesai'] ?: null,
            'deskripsi'       => $post['deskripsi'] ?? null,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'exhibitor_program', (string)$exhibitorId, $post['nama_program'], ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/exhibitors#ex-{$exhibitorId}")->with('success', 'Program berhasil ditambahkan.');
    }

    public function deleteProgram(int $eventId, int $exhibitorId, int $programId)
    {
        if (! $this->canEditMenu('exhibitors')) return redirect()->to("/events/{$eventId}/exhibitors")->with('error', 'Akses ditolak.');
        $prog = (new EventExhibitorProgramModel())->find($programId);
        (new EventExhibitorProgramModel())->delete($programId);
        ActivityLog::write('delete', 'exhibitor_program', (string)$programId, $prog['nama_program'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/exhibitors#ex-{$exhibitorId}")->with('success', 'Program berhasil dihapus.');
    }

}
