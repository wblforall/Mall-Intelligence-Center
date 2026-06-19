<?php

namespace App\Controllers;

use App\Models\ThemePeriodModel;
use App\Libraries\ActivityLog;

class ThemePeriods extends BaseController
{
    private ThemePeriodModel $model;

    public function __construct()
    {
        $this->model = new ThemePeriodModel();
    }

    public function index()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        return view('theme_periods/index', [
            'user'    => $this->currentUser(),
            'periods' => $this->model->orderBy('start_date', 'DESC')->findAll(),
        ]);
    }

    public function add()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $data = [
            'nama'       => trim($this->request->getPost('nama')),
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
            'alert_days' => (int) $this->request->getPost('alert_days'),
            'animation'  => $this->request->getPost('animation'),
            'emoji'      => trim($this->request->getPost('emoji') ?: '🎉'),
            'pesan'      => trim($this->request->getPost('pesan') ?: ''),
            'is_active'  => 1,
        ];

        if (! $data['nama'] || ! $data['start_date'] || ! $data['end_date']) {
            return redirect()->back()->with('error', 'Nama, tanggal mulai, dan tanggal selesai wajib diisi.');
        }

        $newId = $this->model->insert($data);
        ActivityLog::write('create', 'theme_period', (string) $newId, $data['nama']);
        return redirect()->to('theme-periods')->with('success', 'Periode berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $data = [
            'nama'       => trim($this->request->getPost('nama')),
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
            'alert_days' => (int) $this->request->getPost('alert_days'),
            'animation'  => $this->request->getPost('animation'),
            'emoji'      => trim($this->request->getPost('emoji') ?: '🎉'),
            'pesan'      => trim($this->request->getPost('pesan') ?: ''),
            'is_active'  => (int) $this->request->getPost('is_active'),
        ];

        ActivityLog::captureBefore($this->model->find($id));
        $this->model->update($id, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'theme_period', (string) $id, $data['nama']);
        return redirect()->to('theme-periods')->with('success', 'Periode berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $p = $this->model->find($id);
        $this->model->delete($id);
        ActivityLog::write('delete', 'theme_period', (string) $id, $p['nama'] ?? '');
        return redirect()->to('theme-periods')->with('success', 'Periode dihapus.');
    }

    public function toggle(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $period = $this->model->find($id);
        if ($period) {
            $newActive = $period['is_active'] ? 0 : 1;
            $this->model->update($id, ['is_active' => $newActive]);
            ActivityLog::write('update', 'theme_period', (string) $id, $period['nama'] ?? '', ['is_active' => $newActive ? 'Aktif' : 'Nonaktif']);
        }
        return redirect()->to('theme-periods');
    }

    // JSON endpoint — dipanggil layout.php untuk cek periode aktif hari ini
    public function today()
    {
        return $this->response->setJSON($this->model->getTodayPeriods());
    }
}
