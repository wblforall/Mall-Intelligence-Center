<?php

namespace App\Controllers;

use App\Models\AppraisalPeriodModel;
use App\Models\AppraisalTemplateModel;
use App\Models\AppraisalTemplateKpiModel;
use App\Models\AppraisalTemplateCompetencyModel;
use App\Models\AppraisalFormModel;
use App\Models\AppraisalFormKpiModel;
use App\Models\AppraisalFormCompetencyModel;
use App\Libraries\AppraisalChain;
use App\Libraries\AppraisalConfig;
use App\Libraries\ActivityLog;

class AppraisalPeriod extends BaseController
{
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }

    // ── Buka periode + generate form per karyawan (snapshot) ─────────────
    public function create()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $nama = trim($this->request->getPost('nama') ?? '');
        if ($nama === '') return redirect()->to('appraisal')->with('error', 'Nama periode wajib diisi.');

        $mulai   = $this->request->getPost('tanggal_mulai') ?: null;
        $selesai = $this->request->getPost('tanggal_selesai') ?: null;
        $tipe    = $this->request->getPost('tipe') === 'khusus' ? 'khusus' : 'reguler';

        $periodModel = new AppraisalPeriodModel();
        $periodId = $periodModel->insert([
            'nama'            => $nama,
            'tipe'            => $tipe,
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $selesai,
            'tahun'           => $selesai ? (int) date('Y', strtotime($selesai)) : (int) date('Y'),
            'status'          => 'open',
            'created_by'      => $this->currentUser()['id'],
        ]);

        // Periode khusus: form ditambahkan manual per karyawan. Reguler: generate semua.
        if ($tipe === 'khusus') {
            ActivityLog::write('create', 'appraisal_period', (string) $periodId, $nama, ['tipe' => 'khusus']);
            return redirect()->to('appraisal/periods/' . $periodId)
                ->with('success', 'Periode khusus dibuka. Tambahkan karyawan yang akan dinilai satu per satu.');
        }

        $generated = $this->generateForms((int) $periodId);
        ActivityLog::write('create', 'appraisal_period', (string) $periodId, $nama, ['form_dibuat' => $generated]);
        return redirect()->to('appraisal/periods/' . $periodId)
            ->with('success', "Periode dibuka. {$generated} form penilaian dibuat dari template yang disetujui.");
    }

    // ── Tambah penilaian untuk satu karyawan (periode khusus) ────────────
    public function addEmployee(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $period = (new AppraisalPeriodModel())->find($id);
        if (! $period) return redirect()->to('appraisal')->with('error', 'Periode tidak ditemukan.');
        if ($period['status'] !== 'open') return redirect()->to('appraisal/periods/' . $id)->with('error', 'Periode sudah ditutup.');

        $employeeId = (int) $this->request->getPost('employee_id');
        if (! $employeeId) return redirect()->to('appraisal/periods/' . $id)->with('error', 'Karyawan wajib dipilih.');

        $db  = db_connect();
        $emp = $db->table('employees')->select('id, user_id, atasan_id, jabatan_id, status, nama')->where('id', $employeeId)->get()->getRowArray();
        if (! $emp) return redirect()->to('appraisal/periods/' . $id)->with('error', 'Karyawan tidak ditemukan.');
        if ($db->table('appraisal_forms')->where('period_id', $id)->where('employee_id', $employeeId)->countAllResults()) {
            return redirect()->to('appraisal/periods/' . $id)->with('error', 'Karyawan ini sudah ada di periode.');
        }

        $tpl = (new AppraisalTemplateModel())->where('jabatan_id', $emp['jabatan_id'])->where('status', 'approved')->first();
        if (! $tpl) return redirect()->to('appraisal/periods/' . $id)->with('error', 'Belum ada template KPI disetujui untuk jabatan karyawan ini.');

        $formId = $this->buildForm((int) $id, $emp, $tpl);
        ActivityLog::write('create', 'appraisal_form', (string) $formId, $emp['nama'], ['periode' => $period['nama']]);
        return redirect()->to('appraisal/periods/' . $id)->with('success', 'Penilaian untuk ' . esc($emp['nama']) . ' ditambahkan.');
    }

    /** Buat satu form penilaian (form + kpi + kompetensi) dari template. */
    private function buildForm(int $periodId, array $emp, array $tpl): int
    {
        $kpis  = (new AppraisalTemplateKpiModel())->getByTemplate((int) $tpl['id']);
        $comps = (new AppraisalTemplateCompetencyModel())->getByTemplate((int) $tpl['id']);

        $penilai = (new AppraisalChain())->firstActor((int) $emp['id']);
        if ($penilai) {
            $status = 'input'; $currentUser = (int) $penilai['user_id']; $penilaiId = (int) $penilai['id'];
        } else {
            $status = 'hr_review'; $currentUser = null; $penilaiId = null;
        }

        $formModel = new AppraisalFormModel();
        $formId = $formModel->insert([
            'period_id'        => $periodId,
            'employee_id'      => (int) $emp['id'],
            'jabatan_id'       => (int) $emp['jabatan_id'],
            'template_id'      => (int) $tpl['id'],
            'bobot_kpi'        => $tpl['bobot_kpi'],
            'bobot_kompetensi' => $tpl['bobot_kompetensi'],
            'status'           => $status,
            'current_user_id'  => $currentUser,
            'penilai_id'       => $penilaiId,
        ]);

        $formKpiModel = new AppraisalFormKpiModel();
        foreach ($kpis as $k) {
            $formKpiModel->insert(['form_id' => $formId, 'area' => $k['area'], 'indikator' => $k['indikator'], 'unit' => $k['unit'], 'bobot' => $k['bobot'], 'target' => $k['target'], 'urutan' => $k['urutan']]);
        }
        $formCompModel = new AppraisalFormCompetencyModel();
        foreach ($comps as $c) {
            $formCompModel->insert(['form_id' => $formId, 'nama_aspek' => $c['nama_aspek'], 'deskripsi' => $c['deskripsi'], 'urutan' => $c['urutan']]);
        }
        return (int) $formId;
    }

    /** Buat form untuk semua karyawan yang jabatannya punya template disetujui. */
    private function generateForms(int $periodId): int
    {
        $db = db_connect();
        $templateModel = new AppraisalTemplateModel();
        $kpiModel      = new AppraisalTemplateKpiModel();
        $compModel     = new AppraisalTemplateCompetencyModel();
        $formModel     = new AppraisalFormModel();
        $formKpiModel  = new AppraisalFormKpiModel();
        $formCompModel = new AppraisalFormCompetencyModel();
        $chain         = new AppraisalChain();

        // template approved per jabatan
        $approved = $templateModel->where('status', 'approved')->findAll();
        if (! $approved) return 0;
        $tplByJab = array_column($approved, null, 'jabatan_id');

        $count = 0;
        foreach (array_keys($tplByJab) as $jabatanId) {
            $tpl  = $tplByJab[$jabatanId];
            $kpis = $kpiModel->getByTemplate((int) $tpl['id']);
            $comps = $compModel->getByTemplate((int) $tpl['id']);

            $employees = $db->table('employees')
                ->select('id, user_id, atasan_id')
                ->where('jabatan_id', $jabatanId)
                ->where('status', 'aktif')
                ->get()->getResultArray();

            foreach ($employees as $emp) {
                // skip jika sudah ada (unique period+employee)
                if ($formModel->where('period_id', $periodId)->where('employee_id', $emp['id'])->countAllResults()) continue;

                $penilai = $chain->firstActor((int) $emp['id']); // atasan langsung berakun login
                if ($penilai) {
                    $status = 'input';
                    $currentUser = (int) $penilai['user_id'];
                    $penilaiId   = (int) $penilai['id'];
                } else {
                    // tidak ada atasan berakun → langsung ke HR
                    $status = 'hr_review';
                    $currentUser = null;
                    $penilaiId   = null;
                }

                $formId = $formModel->insert([
                    'period_id'        => $periodId,
                    'employee_id'      => (int) $emp['id'],
                    'jabatan_id'       => (int) $jabatanId,
                    'template_id'      => (int) $tpl['id'],
                    'bobot_kpi'        => $tpl['bobot_kpi'],
                    'bobot_kompetensi' => $tpl['bobot_kompetensi'],
                    'status'           => $status,
                    'current_user_id'  => $currentUser,
                    'penilai_id'       => $penilaiId,
                ]);

                foreach ($kpis as $k) {
                    $formKpiModel->insert([
                        'form_id'   => $formId,
                        'area'      => $k['area'],
                        'indikator' => $k['indikator'],
                        'unit'      => $k['unit'],
                        'bobot'     => $k['bobot'],
                        'target'    => $k['target'],
                        'urutan'    => $k['urutan'],
                    ]);
                }
                foreach ($comps as $c) {
                    $formCompModel->insert([
                        'form_id'    => $formId,
                        'nama_aspek' => $c['nama_aspek'],
                        'deskripsi'  => $c['deskripsi'],
                        'urutan'     => $c['urutan'],
                    ]);
                }
                $count++;
            }
        }
        return $count;
    }

    // ── Detail periode (daftar form) ─────────────────────────────────────
    public function show(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $periodModel = new AppraisalPeriodModel();
        $period = $periodModel->find($id);
        if (! $period) return redirect()->to('appraisal')->with('error', 'Periode tidak ditemukan.');

        $forms = (new AppraisalFormModel())->listByPeriod($id);

        // nama current holder
        $userIds = array_filter(array_unique(array_column($forms, 'current_user_id')));
        $userNames = [];
        if ($userIds) {
            foreach (db_connect()->table('users')->select('id, name')->whereIn('id', $userIds)->get()->getResultArray() as $u) {
                $userNames[$u['id']] = $u['name'];
            }
        }

        // Periode khusus & masih open → daftar karyawan yang bisa ditambahkan
        $candidates = [];
        if (($period['tipe'] ?? 'reguler') === 'khusus' && $period['status'] === 'open') {
            $existing = array_filter(array_column($forms, 'employee_id'));
            $rows = db_connect()->table('employees e')
                ->select('e.id, e.nama, j.nama AS jabatan_nama, d.name AS dept_name')
                ->join('appraisal_templates t', 't.jabatan_id = e.jabatan_id AND t.status = "approved"', 'inner', false)
                ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
                ->join('departments d', 'd.id = e.dept_id', 'left')
                ->where('e.status', 'aktif')
                ->orderBy('d.name')->orderBy('e.nama')->get()->getResultArray();
            $candidates = array_values(array_filter($rows, fn($r) => ! in_array($r['id'], $existing)));
        }

        return view('appraisal/period_show', [
            'user'       => $this->currentUser(),
            'period'     => $period,
            'forms'      => $forms,
            'userNames'  => $userNames,
            'candidates' => $candidates,
        ]);
    }

    public function close(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $periodModel = new AppraisalPeriodModel();
        $period = $periodModel->find($id);
        if (! $period) return redirect()->to('appraisal')->with('error', 'Periode tidak ditemukan.');
        $periodModel->update($id, ['status' => 'closed']);
        ActivityLog::write('update', 'appraisal_period', (string) $id, 'Tutup periode — ' . $period['nama']);
        return redirect()->to('appraisal/periods/' . $id)->with('success', 'Periode ditutup.');
    }
}
