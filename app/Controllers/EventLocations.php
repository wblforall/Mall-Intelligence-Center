<?php

namespace App\Controllers;

use App\Models\EventLocationModel;
use App\Libraries\ActivityLog;

class EventLocations extends BaseController
{
    private EventLocationModel $model;

    public function __construct()
    {
        $this->model = new EventLocationModel();
    }

    public function index()
    {
        return view('event_locations/index', [
            'user'    => $this->currentUser(),
            'grouped' => $this->model->getAllGrouped(),
        ]);
    }

    public function store()
    {
        $nama = trim($this->request->getPost('nama') ?? '');
        $mall = $this->request->getPost('mall');

        if (empty($nama)) {
            return redirect()->to('/event-locations')->with('error', 'Nama lokasi tidak boleh kosong.');
        }

        $id = $this->model->insert(['nama' => $nama, 'mall' => $mall, 'aktif' => 1]);
        ActivityLog::write('create', 'event_location', (string)$id, $nama, ['mall' => $mall]);
        return redirect()->to('/event-locations')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/event-locations')->with('error', 'Data tidak ditemukan.');

        $nama  = trim($this->request->getPost('nama') ?? '');
        $mall  = $this->request->getPost('mall');
        $aktif = $this->request->getPost('aktif') ? 1 : 0;

        $this->model->update($id, ['nama' => $nama, 'mall' => $mall, 'aktif' => $aktif]);
        ActivityLog::write('update', 'event_location', (string)$id, $nama, [
            'before' => ['nama' => $row['nama'], 'aktif' => (bool)$row['aktif']],
            'after'  => ['nama' => $nama, 'aktif' => (bool)$aktif],
        ]);
        return redirect()->to('/event-locations')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/event-locations')->with('error', 'Data tidak ditemukan.');

        $this->model->delete($id);
        ActivityLog::write('delete', 'event_location', (string)$id, $row['nama'], ['mall' => $row['mall']]);
        return redirect()->to('/event-locations')->with('success', 'Lokasi berhasil dihapus.');
    }
}
