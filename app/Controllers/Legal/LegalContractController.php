<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalContractModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalContractController extends BaseController
{
    private LegalContractModel $model;

    public function __construct()
    {
        $this->model = new LegalContractModel();
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

        return view('legal/contracts/index', [
            'title'     => 'Kontrak Vendor',
            'contracts' => $this->model->getFiltered($f),
            'filters'   => $f,
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/contracts')->with('error', 'Akses ditolak.');
        return view('legal/contracts/form', ['title' => 'Tambah Kontrak Vendor', 'contract' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/contracts')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','nama_vendor','jenis_kontrak','lingkup_pekerjaan','mall_id',
            'tanggal_mulai','tanggal_berakhir','nilai_kontrak','status','catatan',
        ]);
        $data['mall_id']       = $data['mall_id'] ?: null;
        $data['nilai_kontrak'] = $data['nilai_kontrak'] ? str_replace([',', '.', ' '], '', $data['nilai_kontrak']) : null;
        $data['created_by']    = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('create', 'legal', $id, $data['nama_vendor']);
        return redirect()->to('/legal/contracts/' . $id)->with('success', 'Kontrak berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $contract = $this->model->find($id);
        if (! $contract) return redirect()->to('/legal/contracts')->with('error', 'Data tidak ditemukan.');

        return view('legal/contracts/show', [
            'title'     => $contract['nama_vendor'],
            'contract'  => $contract,
            'documents' => (new LegalDocumentModel())->getForEntity('contract', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/contracts')->with('error', 'Akses ditolak.');
        $contract = $this->model->find($id);
        if (! $contract) return redirect()->to('/legal/contracts')->with('error', 'Data tidak ditemukan.');
        return view('legal/contracts/form', ['title' => 'Edit Kontrak', 'contract' => $contract]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/contracts')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','nama_vendor','jenis_kontrak','lingkup_pekerjaan','mall_id',
            'tanggal_mulai','tanggal_berakhir','nilai_kontrak','status','catatan',
        ]);
        $data['mall_id']       = $data['mall_id'] ?: null;
        $data['nilai_kontrak'] = $data['nilai_kontrak'] ? str_replace([',', '.', ' '], '', $data['nilai_kontrak']) : null;

        $this->model->update($id, $data);
        ActivityLog::write('update', 'legal', $id, $data['nama_vendor']);
        return redirect()->to('/legal/contracts/' . $id)->with('success', 'Kontrak diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/contracts')->with('error', 'Akses ditolak.');

        $contract = $this->model->find($id);
        if (! $contract) return redirect()->to('/legal/contracts')->with('error', 'Data tidak ditemukan.');

        $docModel = new LegalDocumentModel();
        foreach ($docModel->getForEntity('contract', $id) as $doc) {
            $docModel->deleteWithFile($doc['id']);
        }
        $this->model->delete($id);
        ActivityLog::write('delete', 'legal', $id, $contract['nama_vendor']);
        return redirect()->to('/legal/contracts')->with('success', 'Kontrak dihapus.');
    }
}
