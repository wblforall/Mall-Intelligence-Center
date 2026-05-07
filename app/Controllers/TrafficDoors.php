<?php

namespace App\Controllers;

use App\Models\TrafficDoorModel;
use App\Libraries\ActivityLog;

class TrafficDoors extends BaseController
{
    public function index()
    {
        return view('traffic_doors/index', [
            'user'    => $this->currentUser(),
            'grouped' => (new TrafficDoorModel())->getAllGrouped(),
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $model = new TrafficDoorModel();

        if (empty(trim($post['nama_pintu'] ?? ''))) {
            return redirect()->to('/traffic-doors')->with('error', 'Nama pintu tidak boleh kosong.');
        }

        // Auto urutan: append at end of this mall's list
        $maxUrutan = $model->where('mall', $post['mall'])->selectMax('urutan')->first()['urutan'] ?? 0;

        $model->insert([
            'mall'       => $post['mall'],
            'nama_pintu' => trim($post['nama_pintu']),
            'urutan'     => (int)$maxUrutan + 1,
            'aktif'      => 1,
        ]);

        ActivityLog::write('create', 'door', null, trim($post['nama_pintu']), ['mall' => $post['mall']]);
        return redirect()->to('/traffic-doors')->with('success', 'Pintu berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post  = $this->request->getPost();
        $model = new TrafficDoorModel();

        $before = $model->find($id);
        if (! $before) {
            return redirect()->to('/traffic-doors')->with('error', 'Data tidak ditemukan.');
        }

        $model->update($id, [
            'mall'       => $post['mall'],
            'nama_pintu' => trim($post['nama_pintu']),
            'urutan'     => (int)($post['urutan'] ?? 0),
            'aktif'      => isset($post['aktif']) ? 1 : 0,
        ]);
        ActivityLog::write('update', 'door', (string)$id, trim($post['nama_pintu']), [
            'before' => ['nama_pintu' => $before['nama_pintu'], 'aktif' => (bool)$before['aktif']],
            'after'  => ['nama_pintu' => trim($post['nama_pintu']), 'aktif' => isset($post['aktif'])],
        ]);
        return redirect()->to('/traffic-doors')->with('success', 'Pintu berhasil diperbarui.');
    }

    public function reorder()
    {
        $ids   = $this->request->getPost('ids') ?? [];
        $model = new TrafficDoorModel();
        foreach ($ids as $position => $id) {
            $model->update((int)$id, ['urutan' => $position + 1]);
        }
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function delete(int $id)
    {
        $door = (new TrafficDoorModel())->find($id);
        (new TrafficDoorModel())->delete($id);
        ActivityLog::write('delete', 'door', (string)$id, $door['nama_pintu'] ?? '', ['mall' => $door['mall'] ?? '']);
        return redirect()->to('/traffic-doors')->with('success', 'Pintu berhasil dihapus.');
    }
}
