<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPsmMallModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalPsmMallController extends BaseController
{
    private LegalPsmMallModel $model;

    public function __construct()
    {
        $this->model = new LegalPsmMallModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'mall_id' => $this->request->getGet('mall_id'),
            'status'  => $this->request->getGet('status'),
            'q'       => $this->request->getGet('q'),
        ];

        return view('legal/psm_mall/index', [
            'title'   => 'PSM Mall',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-mall')->with('error', 'Akses ditolak.');
        return view('legal/psm_mall/form', ['title' => 'Tambah PSM Mall', 'row' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-mall')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_tenant','unit_lokasi','luas_m2','nilai_sewa',
            'periode_pembayaran','mall_id','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai_sewa']         = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['luas_m2']            = $data['luas_m2'] ?: null;
        $data['unit_lokasi']        = $data['unit_lokasi'] ?: null;
        $data['periode_pembayaran'] = $data['periode_pembayaran'] ?: null;
        $data['created_by']         = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_psm_mall', 'create', $id, $data['nama_tenant'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-mall/' . $id)->with('success', 'PSM Mall berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-mall')->with('error', 'Data tidak ditemukan.');

        return view('legal/psm_mall/show', [
            'title'     => $row['nama_tenant'],
            'row'       => $row,
            'documents' => (new LegalDocumentModel())->getForEntity('psm_mall', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-mall')->with('error', 'Akses ditolak.');
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-mall')->with('error', 'Data tidak ditemukan.');
        return view('legal/psm_mall/form', ['title' => 'Edit PSM Mall', 'row' => $row]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-mall')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_tenant','unit_lokasi','luas_m2','nilai_sewa',
            'periode_pembayaran','mall_id','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai_sewa']         = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['luas_m2']            = $data['luas_m2'] ?: null;
        $data['unit_lokasi']        = $data['unit_lokasi'] ?: null;
        $data['periode_pembayaran'] = $data['periode_pembayaran'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_psm_mall', 'update', $id, $data['nama_tenant'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-mall/' . $id)->with('success', 'PSM Mall diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-mall')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-mall')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('psm_mall', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_psm_mall', 'delete', $id, $row['nama_tenant'] . ' — ' . $row['nomor_psm']);
            return redirect()->to('/legal/psm-mall')->with('success', 'PSM Mall dihapus.');
        }
        return redirect()->to('/legal/psm-mall')->with('error', 'Gagal menghapus PSM Mall.');
    }
}
