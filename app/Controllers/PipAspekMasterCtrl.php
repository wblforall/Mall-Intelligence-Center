<?php

namespace App\Controllers;

use App\Models\PipAspekMasterModel;
use App\Libraries\ActivityLog;

class PipAspekMasterCtrl extends BaseController
{
    private function guard(bool $edit = false): bool
    {
        return $edit
            ? (session()->get('user_role') === 'admin')
            : $this->canViewMenu('people_dev');
    }

    public function index()
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        return view('people/pip/aspek_master', [
            'user'    => $this->currentUser(),
            'grouped' => (new PipAspekMasterModel())->getAllGrouped(),
            'isAdmin' => session()->get('user_role') === 'admin',
        ]);
    }

    public function store()
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        $id   = (new PipAspekMasterModel())->insert([
            'aspek'          => trim($post['aspek']),
            'kategori'       => trim($post['kategori'] ?? '') ?: null,
            'target_default' => trim($post['target_default'] ?? '') ?: null,
            'metrik_default' => trim($post['metrik_default'] ?? '') ?: null,
            'aktif'          => 1,
        ]);

        ActivityLog::write('create', 'pip_aspek_master', (string)$id, trim($post['aspek']));
        return redirect()->to('/people/pip/aspek')->with('success', 'Aspek berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new PipAspekMasterModel())->update($id, [
            'aspek'          => trim($post['aspek']),
            'kategori'       => trim($post['kategori'] ?? '') ?: null,
            'target_default' => trim($post['target_default'] ?? '') ?: null,
            'metrik_default' => trim($post['metrik_default'] ?? '') ?: null,
            'aktif'          => isset($post['aktif']) ? 1 : 0,
        ]);

        ActivityLog::write('update', 'pip_aspek_master', (string)$id, trim($post['aspek']));
        return redirect()->to('/people/pip/aspek')->with('success', 'Aspek diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $aspek = (new PipAspekMasterModel())->find($id);
        (new PipAspekMasterModel())->delete($id);

        ActivityLog::write('delete', 'pip_aspek_master', (string)$id, $aspek['aspek'] ?? '');
        return redirect()->to('/people/pip/aspek')->with('success', 'Aspek dihapus.');
    }

    public function toggle(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $aspek = (new PipAspekMasterModel())->find($id);
        (new PipAspekMasterModel())->update($id, ['aktif' => $aspek['aktif'] ? 0 : 1]);

        ActivityLog::write('update', 'pip_aspek_master', (string)$id, 'toggle aktif');
        return redirect()->to('/people/pip/aspek')->with('success', 'Status aspek diubah.');
    }
}
