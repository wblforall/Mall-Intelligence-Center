<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPsmGudangModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalPsmGudangController extends BaseController
{
    private LegalPsmGudangModel $model;

    public function __construct()
    {
        $this->model = new LegalPsmGudangModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $f = [
            'status' => $this->request->getGet('status'),
            'q'      => $this->request->getGet('q'),
        ];

        return view('legal/psm_gudang/index', [
            'title'   => 'PSM Gudang',
            'rows'    => $this->model->getFiltered($f),
            'filters' => $f,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-gudang')->with('error', 'Akses ditolak.');
        return view('legal/psm_gudang/form', ['title' => 'Tambah PSM Gudang', 'row' => null]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-gudang')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_penyewa','lokasi_gudang','luas_m2','nilai_sewa',
            'periode_pembayaran','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai_sewa']         = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['luas_m2']            = $data['luas_m2'] ?: null;
        $data['periode_pembayaran'] = $data['periode_pembayaran'] ?: null;
        $data['created_by']         = session()->get('user_id');

        $id = $this->model->insert($data);
        ActivityLog::write('legal_psm_gudang', 'create', $id, $data['nama_penyewa'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-gudang/' . $id)->with('success', 'PSM Gudang berhasil disimpan.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-gudang')->with('error', 'Data tidak ditemukan.');

        return view('legal/psm_gudang/show', [
            'title'     => $row['nama_penyewa'],
            'row'       => $row,
            'documents' => (new LegalDocumentModel())->getForEntity('psm_gudang', $id),
            'canEdit'   => $this->canEditMenu('legal'),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-gudang')->with('error', 'Akses ditolak.');
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-gudang')->with('error', 'Data tidak ditemukan.');
        return view('legal/psm_gudang/form', ['title' => 'Edit PSM Gudang', 'row' => $row]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-gudang')->with('error', 'Akses ditolak.');

        $data = $this->request->getPost([
            'nomor_psm','nama_penyewa','lokasi_gudang','luas_m2','nilai_sewa',
            'periode_pembayaran','tanggal_mulai','tanggal_berakhir','status','catatan',
        ]);
        $data['nilai_sewa']         = $data['nilai_sewa'] ? str_replace([',', '.', ' '], '', $data['nilai_sewa']) : null;
        $data['luas_m2']            = $data['luas_m2'] ?: null;
        $data['periode_pembayaran'] = $data['periode_pembayaran'] ?: null;

        $this->model->update($id, $data);
        ActivityLog::write('legal_psm_gudang', 'update', $id, $data['nama_penyewa'] . ' — ' . $data['nomor_psm']);
        return redirect()->to('/legal/psm-gudang/' . $id)->with('success', 'PSM Gudang diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/psm-gudang')->with('error', 'Akses ditolak.');

        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/legal/psm-gudang')->with('error', 'Data tidak ditemukan.');

        $db = \Config\Database::connect();
        $db->transStart();
        $this->model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $docModel = new LegalDocumentModel();
            foreach ($docModel->getForEntity('psm_gudang', $id) as $doc) {
                $docModel->deleteWithFile($doc['id']);
            }
            ActivityLog::write('legal_psm_gudang', 'delete', $id, $row['nama_penyewa'] . ' — ' . $row['nomor_psm']);
            return redirect()->to('/legal/psm-gudang')->with('success', 'PSM Gudang dihapus.');
        }
        return redirect()->to('/legal/psm-gudang')->with('error', 'Gagal menghapus PSM Gudang.');
    }
}
