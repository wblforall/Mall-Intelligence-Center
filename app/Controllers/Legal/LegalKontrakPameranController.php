<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalKontrakPameranModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalKontrakPameranController extends BaseController
{
    private LegalKontrakPameranModel $model;

    public function __construct()
    {
        $this->model = new LegalKontrakPameranModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'mall_id' => $this->request->getGet('mall_id'),
            'status'  => $this->request->getGet('status'),
            'q'       => $this->request->getGet('q'),
        ];

        return view('legal/kontrak_pameran/index', [
            'title'   => 'Kontrak Sewa Pameran',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Akses ditolak.');
        return view('legal/kontrak_pameran/form', ['title' => 'Tambah Kontrak Pameran', 'row' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','nama_penyelenggara','nama_event','lokasi_area',
            'mall_id','tanggal_mulai','tanggal_selesai','nilai_sewa','status','catatan',
        ]);
        $data['nilai_sewa']   = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['mall_id']      = $data['mall_id'] ?: null;
        $data['lokasi_area']  = $data['lokasi_area'] ?: null;
        $data['created_by']   = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_kontrak_pameran', 'create', $id, $data['nama_event'] . ' — ' . $data['nomor_kontrak']);
        return redirect()->to('/legal/kontrak-pameran/' . $id)->with('success', 'Kontrak Pameran berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Data tidak ditemukan.');

        return view('legal/kontrak_pameran/show', [
            'title'     => $row['nama_event'],
            'row'       => $row,
            'documents' => (new LegalDocumentModel())->getForEntity('kontrak_pameran', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Akses ditolak.');
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Data tidak ditemukan.');
        return view('legal/kontrak_pameran/form', ['title' => 'Edit Kontrak Pameran', 'row' => $row]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_kontrak','nama_penyelenggara','nama_event','lokasi_area',
            'mall_id','tanggal_mulai','tanggal_selesai','nilai_sewa','status','catatan',
        ]);
        $data['nilai_sewa']  = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['mall_id']     = $data['mall_id'] ?: null;
        $data['lokasi_area'] = $data['lokasi_area'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_kontrak_pameran', 'update', $id, $data['nama_event'] . ' — ' . $data['nomor_kontrak']);
        return redirect()->to('/legal/kontrak-pameran/' . $id)->with('success', 'Kontrak Pameran diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/kontrak-pameran')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('kontrak_pameran', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_kontrak_pameran', 'delete', $id, $row['nama_event'] . ' — ' . $row['nomor_kontrak']);
            return redirect()->to('/legal/kontrak-pameran')->with('success', 'Kontrak Pameran dihapus.');
        }
        return redirect()->to('/legal/kontrak-pameran')->with('error', 'Gagal menghapus Kontrak Pameran.');
    }
}
