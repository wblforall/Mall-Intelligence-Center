<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPksModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalPksController extends BaseController
{
    private LegalPksModel $model;

    public function __construct()
    {
        $this->model = new LegalPksModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'status' => $this->request->getGet('status'),
            'q'      => $this->request->getGet('q'),
        ];

        return view('legal/pks/index', [
            'title'   => 'Perjanjian Kerja Sama',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/pks')->with('error', 'Akses ditolak.');
        return view('legal/pks/form', ['title' => 'Tambah PKS', 'row' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/pks')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_pks','pihak_kedua','ruang_lingkup','nilai',
            'tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai']      = $data['nilai'] ? str_replace([',', '.', ' '], '', $data['nilai']) : null;
        $data['created_by'] = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_pks', 'create', $id, $data['pihak_kedua'] . ' — ' . $data['nomor_pks']);
        return redirect()->to('/legal/pks/' . $id)->with('success', 'PKS berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/pks')->with('error', 'Data tidak ditemukan.');

        return view('legal/pks/show', [
            'title'     => $row['nomor_pks'],
            'row'       => $row,
            'documents' => (new LegalDocumentModel())->getForEntity('pks', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/pks')->with('error', 'Akses ditolak.');
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/pks')->with('error', 'Data tidak ditemukan.');
        return view('legal/pks/form', ['title' => 'Edit PKS', 'row' => $row]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/pks')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_pks','pihak_kedua','ruang_lingkup','nilai',
            'tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai'] = $data['nilai'] ? str_replace([',', '.', ' '], '', $data['nilai']) : null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_pks', 'update', $id, $data['pihak_kedua'] . ' — ' . $data['nomor_pks']);
        return redirect()->to('/legal/pks/' . $id)->with('success', 'PKS diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/pks')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/pks')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('pks', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_pks', 'delete', $id, $row['pihak_kedua'] . ' — ' . $row['nomor_pks']);
            return redirect()->to('/legal/pks')->with('success', 'PKS dihapus.');
        }
        return redirect()->to('/legal/pks')->with('error', 'Gagal menghapus PKS.');
    }
}
