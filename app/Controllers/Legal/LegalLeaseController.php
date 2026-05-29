<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalLeaseModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalLeaseController extends BaseController
{
    private LegalLeaseModel $model;

    public function __construct()
    {
        $this->model = new LegalLeaseModel();
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

        return view('legal/leases/index', [
            'title'   => 'Perjanjian Sewa',
            'leases'  => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/leases')->with('error', 'Akses ditolak.');
        return view('legal/leases/form', ['title' => 'Tambah Perjanjian Sewa', 'lease' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/leases')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','tenant_name','unit_no','mall_id','jenis_sewa',
            'tanggal_mulai','tanggal_berakhir','nilai_sewa','periode_pembayaran','status','catatan',
        ]);
        $data['nilai_sewa']  = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['unit_no']     = $data['unit_no'] ?: null;
        $data['created_by']  = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal', 'create', $id, $data['tenant_name']);
        return redirect()->to('/legal/leases/' . $id)->with('success', 'Perjanjian sewa berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $lease = $this->model->find($id);
        if (! $lease) return redirect()->to('/legal/leases')->with('error', 'Data tidak ditemukan.');

        return view('legal/leases/show', [
            'title'     => $lease['tenant_name'],
            'lease'     => $lease,
            'documents' => (new LegalDocumentModel())->getForEntity('lease', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/leases')->with('error', 'Akses ditolak.');
        $lease = $this->model->find($id);
        if (! $lease) return redirect()->to('/legal/leases')->with('error', 'Data tidak ditemukan.');
        return view('legal/leases/form', ['title' => 'Edit Perjanjian Sewa', 'lease' => $lease]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/leases')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','tenant_name','unit_no','mall_id','jenis_sewa',
            'tanggal_mulai','tanggal_berakhir','nilai_sewa','periode_pembayaran','status','catatan',
        ]);
        $data['nilai_sewa'] = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['unit_no']    = $data['unit_no'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal', 'update', $id, $data['tenant_name']);
        return redirect()->to('/legal/leases/' . $id)->with('success', 'Perjanjian sewa diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/leases')->with('error', 'Akses ditolak.');

        $lease = $this->model->find($id);
        if (! $lease) return redirect()->to('/legal/leases')->with('error', 'Data tidak ditemukan.');

        $docModel = new LegalDocumentModel();
        foreach ($docModel->getForEntity('lease', $id) as $doc) {
            $docModel->deleteWithFile($doc['id']);
        }
        $this->model->delete($id);
        ActivityLog::write('legal', 'delete', $id, $lease['tenant_name']);
        return redirect()->to('/legal/leases')->with('success', 'Perjanjian sewa dihapus.');
    }
}
