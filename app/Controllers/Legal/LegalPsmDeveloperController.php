<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPsmDeveloperModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalPsmDeveloperController extends BaseController
{
    private LegalPsmDeveloperModel $model;

    public function __construct()
    {
        $this->model = new LegalPsmDeveloperModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'mall_id' => $this->request->getGet('mall_id'),
            'status'  => $this->request->getGet('status'),
            'q'       => $this->request->getGet('q'),
        ];

        return view('legal/psm_developer/index', [
            'title'   => 'PSM Developer',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-developer')->with('error', 'Akses ditolak.');
        return view('legal/psm_developer/form', ['title' => 'Tambah PSM Developer', 'row' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-developer')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_developer','objek_perjanjian','nilai',
            'mall_id','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai']      = $data['nilai'] ? str_replace([',', '.', ' '], '', $data['nilai']) : null;
        $data['mall_id']    = $data['mall_id'] ?: null;
        $data['created_by'] = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_psm_developer', 'create', $id, $data['nama_developer'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-developer/' . $id)->with('success', 'PSM Developer berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-developer')->with('error', 'Data tidak ditemukan.');

        return view('legal/psm_developer/show', [
            'title'     => $row['nama_developer'],
            'row'       => $row,
            'documents' => (new LegalDocumentModel())->getForEntity('psm_developer', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-developer')->with('error', 'Akses ditolak.');
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-developer')->with('error', 'Data tidak ditemukan.');
        return view('legal/psm_developer/form', ['title' => 'Edit PSM Developer', 'row' => $row]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-developer')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_developer','objek_perjanjian','nilai',
            'mall_id','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai']   = $data['nilai'] ? str_replace([',', '.', ' '], '', $data['nilai']) : null;
        $data['mall_id'] = $data['mall_id'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_psm_developer', 'update', $id, $data['nama_developer'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-developer/' . $id)->with('success', 'PSM Developer diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-developer')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-developer')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('psm_developer', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_psm_developer', 'delete', $id, $row['nama_developer'] . ' — ' . $row['nomor_psm']);
            return redirect()->to('/legal/psm-developer')->with('success', 'PSM Developer dihapus.');
        }
        return redirect()->to('/legal/psm-developer')->with('error', 'Gagal menghapus PSM Developer.');
    }
}
