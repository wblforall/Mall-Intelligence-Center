<?php

namespace App\Controllers;

use App\Models\AppraisalTemplateModel;
use App\Models\AppraisalTemplateKpiModel;
use App\Models\AppraisalTemplateCompetencyModel;
use App\Models\JabatanModel;
use App\Libraries\AppraisalConfig;
use App\Libraries\AppraisalAuthority;
use App\Libraries\ActivityLog;

class AppraisalTemplate extends BaseController
{
    private ?AppraisalAuthority $_auth = null;

    // ── Akses ────────────────────────────────────────────────────────────
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }
    private function uid(): int { return (int) $this->currentUser()['id']; }
    private function authority(): AppraisalAuthority { return $this->_auth ??= new AppraisalAuthority(); }

    /** Boleh akses modul template = HR, atau ditunjuk sebagai dept head/deputy. */
    private function canManage(): bool
    {
        return $this->isHr() || $this->authority()->isAssignedAuthor($this->uid());
    }

    private function jabatanRow(int $id): ?array
    {
        return db_connect()->table('jabatans')->select('id, grade, dept_id')->where('id', $id)->get()->getRowArray();
    }

    /** Boleh menyusun template untuk satu jabatan? */
    private function canAuthorJab(int $jabatanId): bool
    {
        $j = $this->jabatanRow($jabatanId);
        return $j ? $this->authority()->canAuthor($this->uid(), $j, $this->isHr()) : false;
    }

    // ── List ─────────────────────────────────────────────────────────────
    public function index()
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $kpiModel      = new AppraisalTemplateKpiModel();
        $templates     = $templateModel->listWithJabatan();

        $isHr = $this->isHr();
        if (! $isHr) {
            $templates = array_values(array_filter($templates, fn($t) =>
                $this->authority()->canAuthor($this->uid(), ['grade' => $t['grade'], 'dept_id' => $t['jabatan_dept_id']], false)));
        }
        foreach ($templates as &$t) {
            $t['total_bobot'] = $kpiModel->totalBobot((int) $t['id']);
            $t['kpi_count']   = $kpiModel->where('template_id', $t['id'])->countAllResults();
        }
        unset($t);

        // Jabatan yang belum punya template & yang BOLEH disusun user ini (untuk tombol buat baru)
        $jabs = db_connect()->table('jabatans j')
            ->select('j.id, j.nama, j.grade, d.name AS dept_name, j.dept_id')
            ->join('departments d', 'd.id = j.dept_id', 'left')
            ->orderBy('d.name')->orderBy('j.grade')->get()->getResultArray();
        $hasTemplate = array_column($templates, null, 'jabatan_id');
        $jabsAvailable = array_values(array_filter($jabs, fn($j) =>
            ! isset($hasTemplate[$j['id']]) && $this->authority()->canAuthor($this->uid(), $j, $isHr)));

        return view('appraisal/templates/index', [
            'user'          => $this->currentUser(),
            'templates'     => $templates,
            'jabsAvailable' => $jabsAvailable,
            'isHr'          => $isHr,
        ]);
    }

    // ── Buat template untuk satu jabatan ─────────────────────────────────
    public function create()
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $jabatanId = (int) $this->request->getPost('jabatan_id');
        if (! $jabatanId) return redirect()->back()->with('error', 'Jabatan wajib dipilih.');

        if (! $this->canAuthorJab($jabatanId)) {
            return redirect()->back()->with('error', 'Anda tidak berwenang menyusun template untuk jabatan ini.');
        }

        $templateModel = new AppraisalTemplateModel();
        if ($templateModel->getForJabatan($jabatanId)) {
            return redirect()->back()->with('error', 'Template untuk jabatan ini sudah ada.');
        }

        $jab = (new JabatanModel())->find($jabatanId);
        $id = $templateModel->insert([
            'jabatan_id'       => $jabatanId,
            'nama'             => 'KPI ' . ($jab['nama'] ?? ''),
            'status'           => 'draft',
            'bobot_kpi'        => AppraisalConfig::BOBOT_KPI,
            'bobot_kompetensi' => AppraisalConfig::BOBOT_KOMPETENSI,
            'created_by'       => $this->currentUser()['id'],
        ]);

        // Seed 5 aspek kompetensi default
        $compModel = new AppraisalTemplateCompetencyModel();
        $urut = 1;
        foreach (AppraisalConfig::DEFAULT_KOMPETENSI as $c) {
            $compModel->insert(['template_id' => $id, 'nama_aspek' => $c['nama_aspek'], 'deskripsi' => $c['deskripsi'], 'urutan' => $urut++]);
        }

        ActivityLog::write('create', 'appraisal_template', (string) $id, $jab['nama'] ?? '');
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template dibuat. Silakan susun item KPI.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $tpl = $templateModel->find($id);
        if (! $tpl) return redirect()->to('appraisal/templates')->with('error', 'Template tidak ditemukan.');

        if (! $this->canAuthorJab((int) $tpl['jabatan_id'])) {
            return redirect()->to('appraisal/templates')->with('error', 'Akses ditolak.');
        }

        $jab = (new JabatanModel())->db->table('jabatans j')
            ->select('j.nama, d.name AS dept_name')
            ->join('departments d', 'd.id = j.dept_id', 'left')
            ->where('j.id', $tpl['jabatan_id'])->get()->getRowArray();

        $kpiModel  = new AppraisalTemplateKpiModel();
        $compModel = new AppraisalTemplateCompetencyModel();

        // Approved → terkunci untuk manager; HR bisa buka kembali.
        $locked = ($tpl['status'] === 'approved' && ! $this->isHr())
               || ($tpl['status'] === 'submitted' && ! $this->isHr());

        return view('appraisal/templates/edit', [
            'user'        => $this->currentUser(),
            'tpl'         => $tpl,
            'jab'         => $jab,
            'kpis'        => $kpiModel->getByTemplate($id),
            'comps'       => $compModel->getByTemplate($id),
            'totalBobot'  => $kpiModel->totalBobot($id),
            'areas'       => AppraisalConfig::AREAS,
            'units'       => AppraisalConfig::UNITS,
            'isHr'        => $this->isHr(),
            'locked'      => $locked,
        ]);
    }

    // ── Simpan item KPI (replace semua, editor tunggal) ──────────────────
    public function saveKpi(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $kpiModel = new AppraisalTemplateKpiModel();
        $db = db_connect();
        $db->transStart();
        $kpiModel->where('template_id', $id)->delete();

        $rows = $this->request->getPost('kpi') ?? [];
        $urut = 1;
        foreach ($rows as $r) {
            $indikator = trim($r['indikator'] ?? '');
            if ($indikator === '') continue;
            $kpiModel->insert([
                'template_id' => $id,
                'area'        => in_array($r['area'] ?? '', array_keys(AppraisalConfig::AREAS)) ? $r['area'] : 'pencapaian_target',
                'indikator'   => $indikator,
                'unit'        => in_array($r['unit'] ?? '', array_keys(AppraisalConfig::UNITS)) ? $r['unit'] : 'persen',
                'bobot'       => (float) ($r['bobot'] ?? 0),
                'target'      => ($r['target'] ?? '') === '' ? null : (float) $r['target'],
                'urutan'      => $urut++,
            ]);
        }
        $db->transComplete();

        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Item KPI', ['total_bobot' => $kpiModel->totalBobot($id)]);
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Item KPI disimpan.');
    }

    // ── Simpan aspek kompetensi ──────────────────────────────────────────
    public function saveCompetency(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $compModel = new AppraisalTemplateCompetencyModel();
        $db = db_connect();
        $db->transStart();
        $compModel->where('template_id', $id)->delete();

        $rows = $this->request->getPost('comp') ?? [];
        $urut = 1;
        foreach ($rows as $r) {
            $nama = trim($r['nama_aspek'] ?? '');
            if ($nama === '') continue;
            $compModel->insert([
                'template_id' => $id,
                'nama_aspek'  => $nama,
                'deskripsi'   => trim($r['deskripsi'] ?? '') ?: null,
                'urutan'      => $urut++,
            ]);
        }
        $db->transComplete();

        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Aspek Kompetensi');
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Aspek kompetensi disimpan.');
    }

    // ── Submit ke HR ─────────────────────────────────────────────────────
    public function submit(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $kpiModel = new AppraisalTemplateKpiModel();
        $total = $kpiModel->totalBobot($id);
        if (abs($total - 100) > 0.01) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', "Total bobot KPI harus 100 (sekarang {$total}).");
        }
        if ($kpiModel->where('template_id', $id)->countAllResults() === 0) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Belum ada item KPI.');
        }

        (new AppraisalTemplateModel())->update($id, ['status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Submit ke HR');
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template diajukan ke HR untuk persetujuan.');
    }

    // ── HR: approve / reject ─────────────────────────────────────────────
    public function approve(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Hanya HR yang dapat menyetujui.');
        $m = new AppraisalTemplateModel();
        $tpl = $m->find($id);
        if (! $tpl || $tpl['status'] !== 'submitted') return redirect()->back()->with('error', 'Template belum diajukan.');

        $total = (new AppraisalTemplateKpiModel())->totalBobot($id);
        if (abs($total - 100) > 0.01) return redirect()->back()->with('error', "Total bobot KPI harus 100 (sekarang {$total}).");

        $m->update($id, ['status' => 'approved', 'approved_by' => $this->currentUser()['id'], 'approved_at' => date('Y-m-d H:i:s'), 'catatan_hr' => null]);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Approve template');
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Hanya HR yang dapat menolak.');
        $m = new AppraisalTemplateModel();
        $tpl = $m->find($id);
        if (! $tpl || $tpl['status'] !== 'submitted') return redirect()->back()->with('error', 'Template belum diajukan.');

        $catatan = trim($this->request->getPost('catatan_hr') ?? '');
        $m->update($id, ['status' => 'draft', 'catatan_hr' => $catatan ?: 'Dikembalikan untuk revisi.']);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Reject template', ['catatan' => $catatan]);
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template dikembalikan ke penyusun.');
    }

    public function delete(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;
        (new AppraisalTemplateModel())->delete($id); // cascade kpi & competency
        ActivityLog::write('delete', 'appraisal_template', (string) $id, $tpl['nama'] ?? '');
        return redirect()->to('appraisal/templates')->with('success', 'Template dihapus.');
    }

    /** Guard umum untuk aksi edit: kembalikan template row atau RedirectResponse. */
    private function guardEditable(int $id)
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $tpl = (new AppraisalTemplateModel())->find($id);
        if (! $tpl) return redirect()->to('appraisal/templates')->with('error', 'Template tidak ditemukan.');

        if (! $this->canAuthorJab((int) $tpl['jabatan_id'])) {
            return redirect()->to('appraisal/templates')->with('error', 'Akses ditolak.');
        }
        // approved/submitted hanya HR yang boleh ubah (mengembalikan ke draft dulu untuk approved)
        if (in_array($tpl['status'], ['submitted', 'approved']) && ! $this->isHr()) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Template sedang dikunci (menunggu/terkunci HR).');
        }
        if ($tpl['status'] === 'approved' && $this->isHr()) {
            // HR membuka kembali approved → kembali ke draft saat mulai edit
            (new AppraisalTemplateModel())->update($id, ['status' => 'draft']);
            $tpl['status'] = 'draft';
        }
        return $tpl;
    }
}
