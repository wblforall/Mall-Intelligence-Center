<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\PestItemModel;

/**
 * Master item temuan pest.
 *
 * Item yang sudah pernah punya temuan TIDAK boleh dihapus — angka historis
 * ikut lenyap. Dinonaktifkan saja: ia hilang dari form input tapi tetap muncul
 * di rekap tahun-tahun sebelumnya. FK pest_findings.item_id memang RESTRICT,
 * pemeriksaan di sini hanya supaya pesannya ramah, bukan galat basis data.
 */
class PestItems extends BaseController
{
    public function index()
    {
        return view('pest/items', [
            'user'  => $this->currentUser(),
            'items' => (new PestItemModel())->semua(),
        ]);
    }

    public function store()
    {
        $model = new PestItemModel();
        $nama  = trim((string) $this->request->getPost('nama'));

        if ($nama === '') {
            return redirect()->to('/pest-items')->with('error', 'Nama item tidak boleh kosong.');
        }
        if ($model->where('nama', $nama)->countAllResults() > 0) {
            return redirect()->to('/pest-items')->with('error', 'Item "' . $nama . '" sudah ada.');
        }

        $maxUrutan = (int) ($model->selectMax('urutan')->first()['urutan'] ?? 0);
        $model->insert([
            'nama'       => $nama,
            'urutan'     => $maxUrutan + 1,
            'aktif'      => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('create', 'pest_item', null, $nama);
        return redirect()->to('/pest-items')->with('success', 'Item ditambahkan.');
    }

    public function update(int $id)
    {
        $model  = new PestItemModel();
        $before = $model->find($id);
        if (! $before) return redirect()->to('/pest-items')->with('error', 'Item tidak ditemukan.');

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return redirect()->to('/pest-items')->with('error', 'Nama item tidak boleh kosong.');

        $bentrok = $model->where('nama', $nama)->where('id !=', $id)->countAllResults();
        if ($bentrok > 0) return redirect()->to('/pest-items')->with('error', 'Nama "' . $nama . '" sudah dipakai item lain.');

        $data = [
            'nama'   => $nama,
            'urutan' => (int) ($this->request->getPost('urutan') ?? 0),
            'aktif'  => $this->request->getPost('aktif') ? 1 : 0,
        ];

        ActivityLog::captureBefore(['nama' => $before['nama'], 'aktif' => (bool) $before['aktif']]);
        $model->update($id, $data);
        ActivityLog::captureAfter(['nama' => $nama, 'aktif' => (bool) $data['aktif']]);
        ActivityLog::write('update', 'pest_item', (string) $id, $nama);

        return redirect()->to('/pest-items')->with('success', 'Item diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new PestItemModel();
        $item  = $model->find($id);
        if (! $item) return redirect()->to('/pest-items')->with('error', 'Item tidak ditemukan.');

        if ($model->dipakai($id)) {
            return redirect()->to('/pest-items')->with(
                'error',
                'Item "' . $item['nama'] . '" sudah punya temuan tercatat dan tidak bisa dihapus — '
                . 'nonaktifkan saja agar angka lamanya tetap utuh.'
            );
        }

        $model->delete($id);
        ActivityLog::write('delete', 'pest_item', (string) $id, $item['nama']);
        return redirect()->to('/pest-items')->with('success', 'Item dihapus.');
    }
}
