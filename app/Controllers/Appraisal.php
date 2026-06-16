<?php

namespace App\Controllers;

use App\Models\AppraisalTemplateModel;
use App\Models\AppraisalPeriodModel;
use App\Models\AppraisalTemplateAuthorModel;
use App\Models\AppraisalDivisionDeputyModel;
use App\Libraries\ActivityLog;

class Appraisal extends BaseController
{
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }

    public function index()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $periodModel   = new AppraisalPeriodModel();

        $tplStats = [
            'total'     => $templateModel->countAllResults(false),
            'approved'  => $templateModel->where('status', 'approved')->countAllResults(false),
            'submitted' => $templateModel->where('status', 'submitted')->countAllResults(),
        ];
        $periods = $periodModel->orderBy('created_at', 'DESC')->findAll();

        return view('appraisal/index', [
            'user'     => $this->currentUser(),
            'tplStats' => $tplStats,
            'periods'  => $periods,
        ]);
    }

    // ── Penunjukan penyusun template: Dept Head per dept + Deputy per divisi ─
    public function authors()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $db = db_connect();
        $depts = $db->table('departments d')
            ->select('d.id, d.name, d.division_id, dv.nama AS division_nama')
            ->join('divisions dv', 'dv.id = d.division_id', 'left')
            ->orderBy('dv.nama')->orderBy('d.name')->get()->getResultArray();
        $divisions = $db->table('divisions')->select('id, nama')->orderBy('nama')->get()->getResultArray();

        // Kandidat: karyawan yang punya akun login
        $candidates = $db->table('employees e')
            ->select('e.user_id, e.nama, j.nama AS jabatan_nama, dp.name AS dept_name')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->join('departments dp', 'dp.id = e.dept_id', 'left')
            ->where('e.user_id IS NOT NULL')->where('e.status', 'aktif')
            ->orderBy('e.nama')->get()->getResultArray();

        return view('appraisal/authors', [
            'user'        => $this->currentUser(),
            'depts'       => $depts,
            'divisions'   => $divisions,
            'candidates'  => $candidates,
            'authorMap'   => (new AppraisalTemplateAuthorModel())->map(),
            'deputyMap'   => (new AppraisalDivisionDeputyModel())->map(),
        ]);
    }

    public function saveAuthors()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $authorModel = new AppraisalTemplateAuthorModel();
        $deputyModel = new AppraisalDivisionDeputyModel();

        foreach (($this->request->getPost('dept') ?? []) as $deptId => $userId) {
            $authorModel->setAuthor((int) $deptId, $userId !== '' ? (int) $userId : null);
        }
        foreach (($this->request->getPost('deputy') ?? []) as $divId => $userId) {
            $deputyModel->setDeputy((int) $divId, $userId !== '' ? (int) $userId : null);
        }
        ActivityLog::write('update', 'appraisal_authors', null, 'Penunjukan penyusun template');
        return redirect()->to('appraisal/authors')->with('success', 'Penunjukan penyusun template disimpan.');
    }
}
