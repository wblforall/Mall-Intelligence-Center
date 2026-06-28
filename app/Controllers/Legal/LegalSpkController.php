<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalSpkModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalSpkController extends BaseController
{
    private LegalSpkModel $model;

    public function __construct()
    {
        $this->model = new LegalSpkModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'status' => $this->request->getGet('status'),
            'q'      => $this->request->getGet('q'),
        ];

        return view('legal/spk/index', [
            'title'   => 'Review SPK',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/spk')->with('error', 'Akses ditolak.');

        $users = $this->db->table('users')->where('active', 1)->orderBy('name')->get()->getResultArray();
        return view('legal/spk/form', ['title' => 'Tambah SPK', 'row' => null, 'users' => $users]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/spk')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_spk','nama_vendor','deskripsi_pekerjaan','nilai_spk',
            'tanggal_terbit','tanggal_selesai','pic_user_id','status','catatan',
        ]);
        $data['nilai_spk']   = $data['nilai_spk'] ? str_replace([',', '.', ' '], '', $data['nilai_spk']) : null;
        $data['pic_user_id'] = $data['pic_user_id'] ?: null;
        $data['created_by']  = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_spk', 'create', $id, $data['nama_vendor'] . ' — ' . $data['nomor_spk']);
        return redirect()->to('/legal/spk/' . $id)->with('success', 'SPK berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/spk')->with('error', 'Data tidak ditemukan.');

        $pic = null;
        if ($row['pic_user_id']) {
            $pic = $this->db->table('users')->where('id', $row['pic_user_id'])->get()->getRowArray();
        }

        return view('legal/spk/show', [
            'title'     => $row['nomor_spk'],
            'row'       => $row,
            'pic'       => $pic,
            'documents' => (new LegalDocumentModel())->getForEntity('spk', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/spk')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/spk')->with('error', 'Data tidak ditemukan.');

        $users = $this->db->table('users')->where('active', 1)->orderBy('name')->get()->getResultArray();
        return view('legal/spk/form', ['title' => 'Edit SPK', 'row' => $row, 'users' => $users]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/spk')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_spk','nama_vendor','deskripsi_pekerjaan','nilai_spk',
            'tanggal_terbit','tanggal_selesai','pic_user_id','status','catatan',
        ]);
        $data['nilai_spk']   = $data['nilai_spk'] ? str_replace([',', '.', ' '], '', $data['nilai_spk']) : null;
        $data['pic_user_id'] = $data['pic_user_id'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_spk', 'update', $id, $data['nama_vendor'] . ' — ' . $data['nomor_spk']);
        return redirect()->to('/legal/spk/' . $id)->with('success', 'SPK diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/spk')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/spk')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('spk', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_spk', 'delete', $id, $row['nama_vendor'] . ' — ' . $row['nomor_spk']);
            return redirect()->to('/legal/spk')->with('success', 'SPK dihapus.');
        }
        return redirect()->to('/legal/spk')->with('error', 'Gagal menghapus SPK.');
    }
}
