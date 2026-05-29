<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPermitModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalPermitController extends BaseController
{
    private LegalPermitModel $model;

    public function __construct()
    {
        $this->model = new LegalPermitModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'mall_id' => $this->request->getGet('mall_id'),
            'status'  => $this->request->getGet('status'),
            'jenis'   => $this->request->getGet('jenis'),
            'q'       => $this->request->getGet('q'),
        ];

        return view('legal/permits/index', [
            'title'    => 'Perizinan & Lisensi',
            'permits'  => $this->model->getFiltered($f),
            'filters'  => $f,
            'canEdit'  => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/permits')->with('error', 'Akses ditolak.');
        return view('legal/permits/form', ['title' => 'Tambah Perizinan', 'permit' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/permits')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_izin','nama_izin','jenis_izin','instansi_penerbit','mall_id',
            'tanggal_terbit','tanggal_berakhir','status','catatan',
        ]);
        $data['tanggal_berakhir'] = $data['tanggal_berakhir'] ?: null;
        $data['created_by'] = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal', 'create', $id, $data['nama_izin']);
        return redirect()->to('/legal/permits/' . $id)->with('success', 'Perizinan berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $permit = $this->model->find($id);
        if (! $permit) return redirect()->to('/legal/permits')->with('error', 'Data tidak ditemukan.');

        return view('legal/permits/show', [
            'title'     => $permit['nama_izin'],
            'permit'    => $permit,
            'documents' => (new LegalDocumentModel())->getForEntity('permit', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/permits')->with('error', 'Akses ditolak.');
        $permit = $this->model->find($id);
        if (! $permit) return redirect()->to('/legal/permits')->with('error', 'Data tidak ditemukan.');
        return view('legal/permits/form', ['title' => 'Edit Perizinan', 'permit' => $permit]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/permits')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_izin','nama_izin','jenis_izin','instansi_penerbit','mall_id',
            'tanggal_terbit','tanggal_berakhir','status','catatan',
        ]);
        $data['tanggal_berakhir'] = $data['tanggal_berakhir'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal', 'update', $id, $data['nama_izin']);
        return redirect()->to('/legal/permits/' . $id)->with('success', 'Perizinan diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/permits')->with('error', 'Akses ditolak.');

        $permit = $this->model->find($id);
        if (! $permit) return redirect()->to('/legal/permits')->with('error', 'Data tidak ditemukan.');

        $docModel = new LegalDocumentModel();
        foreach ($docModel->getForEntity('permit', $id) as $doc) {
            $docModel->deleteWithFile($doc['id']);
        }
        $this->model->delete($id);
        ActivityLog::write('legal', 'delete', $id, $permit['nama_izin']);
        return redirect()->to('/legal/permits')->with('success', 'Perizinan dihapus.');
    }
}
