<?php

namespace App\Controllers;

use App\Models\AppraisalFormModel;
use App\Models\AppraisalFormKpiModel;
use App\Models\AppraisalFormCompetencyModel;
use App\Libraries\AppraisalConfig;
use App\Libraries\AppraisalChain;
use App\Libraries\ActivityLog;

class AppraisalForm extends BaseController
{
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }
    private function uid(): int { return (int) $this->currentUser()['id']; }

    // ── Penilaian Saya (inbox penilai/reviewer) ─────────────────────────
    public function saya()
    {
        $formModel = new AppraisalFormModel();
        $inbox = $formModel->inboxFor($this->uid());

        // apakah user ditunjuk menyusun template (dept head/deputy) atau HR
        $isAuthor = $this->isHr() || (new \App\Libraries\AppraisalAuthority())->isAssignedAuthor($this->uid());
        $myEmp = (new AppraisalChain())->employeeByUser($this->uid());

        // riwayat: form yang pernah dia tangani tapi sudah diteruskan
        $done = $formModel->db->table('appraisal_forms f')
            ->select('f.*, e.nama AS employee_nama, j.nama AS jabatan_nama, p.nama AS periode_nama')
            ->join('employees e', 'e.id = f.employee_id', 'left')
            ->join('jabatans j', 'j.id = f.jabatan_id', 'left')
            ->join('appraisal_periods p', 'p.id = f.period_id', 'left')
            ->where('f.penilai_id', $myEmp['id'] ?? 0)
            ->where('f.current_user_id !=', $this->uid())
            ->orderBy('p.nama')->limit(50)->get()->getResultArray();

        return view('appraisal/saya', [
            'user'      => $this->currentUser(),
            'inbox'     => $inbox,
            'done'      => $done,
            'isManager' => $isAuthor,
            'isHr'      => $this->isHr(),
        ]);
    }

    // ── Detail form ──────────────────────────────────────────────────────
    public function show(int $id)
    {
        $formModel = new AppraisalFormModel();
        $form = $formModel->getDetail($id);
        if (! $form) return redirect()->to('/')->with('error', 'Form tidak ditemukan.');
        if (! $this->canView($form)) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $kpis  = (new AppraisalFormKpiModel())->getByForm($id);
        $comps = (new AppraisalFormCompetencyModel())->getByForm($id);

        $isCurrent = ((int) $form['current_user_id'] === $this->uid());
        $mode = $this->actionMode($form);   // 'input' | 'review' | 'hr' | null

        return view('appraisal/form_show', [
            'user'       => $this->currentUser(),
            'form'       => $form,
            'kpis'       => $kpis,
            'comps'      => $comps,
            'areas'      => AppraisalConfig::AREAS,
            'units'      => AppraisalConfig::UNITS,
            'skala'      => AppraisalConfig::SKALA,
            'mode'       => $mode,
            'canEditKpi' => in_array($mode, ['input', 'review']),
            'canEditComp'=> in_array($mode, ['input', 'review', 'hr']),
            'needNote'   => in_array($mode, ['review', 'hr']),  // override → catatan wajib jika berubah
            'canForward' => in_array($mode, ['input', 'review']),
            'canFinalize'=> $mode === 'hr',
            'isHr'       => $this->isHr(),
            'isEmployee' => isset($form['employee_user_id']) && (int) $form['employee_user_id'] === $this->uid(),
        ]);
    }

    /** Mode aksi user saat ini terhadap form: input/review/hr/null. */
    private function actionMode(array $form): ?string
    {
        $isCurrent = ((int) $form['current_user_id'] === $this->uid());
        if ($form['status'] === 'input' && $isCurrent)     return 'input';
        if ($form['status'] === 'in_review' && $isCurrent) return 'review';
        if ($form['status'] === 'hr_review' && $this->isHr()) return 'hr';
        return null;
    }

    private function canView(array $form): bool
    {
        if ($this->isHr()) return true;
        if ((int) $form['current_user_id'] === $this->uid()) return true;
        if (isset($form['employee_user_id']) && (int) $form['employee_user_id'] === $this->uid()) return true;
        if ((int) ($form['penilai_id'] ?? 0) && $this->myEmployeeId() === (int) $form['penilai_id']) return true;
        // dalam rantai atasan karyawan?
        foreach ((new AppraisalChain())->atasanChain((int) $form['employee_id']) as $a) {
            if ((int) ($a['user_id'] ?? 0) === $this->uid()) return true;
        }
        return false;
    }

    private function myEmployeeId(): int
    {
        $e = (new AppraisalChain())->employeeByUser($this->uid());
        return (int) ($e['id'] ?? 0);
    }

    // ── Simpan skor (input penilai / override reviewer / HR kompetensi) ──
    public function saveScore(int $id)
    {
        $formModel = new AppraisalFormModel();
        $form = $formModel->find($id);
        if (! $form) return redirect()->to('/')->with('error', 'Form tidak ditemukan.');

        $mode = $this->actionMode($form);
        if ($mode === null) return redirect()->to('appraisal/forms/' . $id)->with('error', 'Anda tidak berwenang mengubah form ini saat ini.');

        $kpiModel  = new AppraisalFormKpiModel();
        $compModel = new AppraisalFormCompetencyModel();
        $kpis  = $kpiModel->getByForm($id);
        $comps = $compModel->getByForm($id);

        $postKpi  = $this->request->getPost('kpi') ?? [];
        $postComp = $this->request->getPost('comp') ?? [];
        $catatan  = trim($this->request->getPost('catatan') ?? '');

        $before = []; $after = [];
        $kpiUpdates = []; $compUpdates = [];

        // Hitung dulu rencana perubahan (BELUM menulis) — agar validasi catatan tidak menyisakan partial save.
        // KPI hanya boleh diubah saat input/review (BUKAN HR)
        if (in_array($mode, ['input', 'review'])) {
            foreach ($kpis as $k) {
                $kid = $k['id'];
                $newSkor = isset($postKpi[$kid]['skor']) && $postKpi[$kid]['skor'] !== '' ? (float) $postKpi[$kid]['skor'] : null;
                $newReal = isset($postKpi[$kid]['realisasi']) && $postKpi[$kid]['realisasi'] !== '' ? (float) $postKpi[$kid]['realisasi'] : null;
                if ($newSkor !== null) $newSkor = max(0, min(100, $newSkor));
                $oldSkor = $k['skor'] !== null ? (float) $k['skor'] : null;
                if ($oldSkor !== $newSkor) { $before['KPI#' . $kid . ' skor'] = $oldSkor; $after['KPI#' . $kid . ' skor'] = $newSkor; }
                $kpiUpdates[$kid] = ['skor' => $newSkor, 'realisasi' => $newReal];
            }
        }

        // Kompetensi (1-5) boleh diubah di semua mode
        foreach ($comps as $c) {
            $cid = $c['id'];
            $newNilai = isset($postComp[$cid]['nilai']) && $postComp[$cid]['nilai'] !== '' ? (int) $postComp[$cid]['nilai'] : null;
            if ($newNilai !== null) $newNilai = max(1, min(5, $newNilai));
            $oldNilai = $c['nilai'] !== null ? (int) $c['nilai'] : null;
            if ($oldNilai !== $newNilai) { $before['Komp#' . $cid] = $oldNilai; $after['Komp#' . $cid] = $newNilai; }
            $compUpdates[$cid] = ['nilai' => $newNilai];
        }

        // Override (review/hr) yang mengubah nilai → catatan wajib (validasi sebelum menulis)
        if ($mode !== 'input' && ! empty($after) && $catatan === '') {
            return redirect()->to('appraisal/forms/' . $id)->with('error', 'Perubahan nilai (override) wajib disertai catatan.');
        }

        // Terapkan
        foreach ($kpiUpdates as $kid => $u)  $kpiModel->update($kid, $u);
        foreach ($compUpdates as $cid => $u) $compModel->update($cid, $u);

        $this->recompute($id);

        if (! empty($after)) {
            ActivityLog::captureBefore($before);
            ActivityLog::captureAfter($after);
        }
        $label = $mode === 'input' ? 'Input penilaian' : ($mode === 'hr' ? 'Override HR (kompetensi)' : 'Override reviewer');
        ActivityLog::write('update', 'appraisal_form', (string) $id, $label, ['mode' => $mode, 'catatan' => $catatan ?: null]);

        return redirect()->to('appraisal/forms/' . $id)->with('success', 'Penilaian disimpan.');
    }

    /** Hitung ulang skor_kpi, skor_kompetensi, nilai_akhir dari baris form. */
    private function recompute(int $id): void
    {
        $form  = (new AppraisalFormModel())->find($id);
        $kpis  = (new AppraisalFormKpiModel())->getByForm($id);
        $comps = (new AppraisalFormCompetencyModel())->getByForm($id);

        $skorKpi  = AppraisalConfig::skorKpi($kpis);
        $skorKomp = AppraisalConfig::skorKompetensi(array_column($comps, 'nilai'));
        $nilai    = AppraisalConfig::nilaiAkhir($skorKpi, $skorKomp, (float) $form['bobot_kpi'], (float) $form['bobot_kompetensi']);

        (new AppraisalFormModel())->update($id, [
            'skor_kpi'        => $skorKpi,
            'skor_kompetensi' => $skorKomp,
            'nilai_akhir'     => $nilai,
        ]);
    }

    // ── Teruskan ke atas (override-and-forward) ─────────────────────────
    public function forward(int $id)
    {
        $formModel = new AppraisalFormModel();
        $form = $formModel->find($id);
        if (! $form) return redirect()->to('/')->with('error', 'Form tidak ditemukan.');

        $mode = $this->actionMode($form);
        if (! in_array($mode, ['input', 'review'])) {
            return redirect()->to('appraisal/forms/' . $id)->with('error', 'Tidak dapat meneruskan form ini.');
        }

        $kpis  = (new AppraisalFormKpiModel())->getByForm($id);
        $comps = (new AppraisalFormCompetencyModel())->getByForm($id);
        if (! AppraisalConfig::kpiComplete($kpis) || ! AppraisalConfig::kompetensiComplete($comps)) {
            return redirect()->to('appraisal/forms/' . $id)->with('error', 'Lengkapi semua skor KPI dan nilai kompetensi sebelum meneruskan.');
        }
        $this->recompute($id);

        $next = (new AppraisalChain())->nextActorAfter($this->uid(), (int) $form['employee_id']);
        if ($next) {
            $formModel->update($id, ['status' => 'in_review', 'current_user_id' => (int) $next['user_id']]);
            $msg = 'Diteruskan ke ' . ($next['nama'] ?? 'atasan berikutnya') . '.';
        } else {
            $formModel->update($id, ['status' => 'hr_review', 'current_user_id' => null]);
            $msg = 'Diteruskan ke HR untuk pengecekan akhir.';
        }
        ActivityLog::write('update', 'appraisal_form', (string) $id, 'Teruskan penilaian', ['ke' => $next['nama'] ?? 'HR']);
        return redirect()->to('appraisal/saya')->with('success', $msg);
    }

    // ── HR finalize ──────────────────────────────────────────────────────
    public function finalize(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Hanya HR yang dapat finalisasi.');
        $formModel = new AppraisalFormModel();
        $form = $formModel->find($id);
        if (! $form || $form['status'] !== 'hr_review') return redirect()->to('appraisal/forms/' . $id)->with('error', 'Form belum di tahap HR.');

        $comps = (new AppraisalFormCompetencyModel())->getByForm($id);
        if (! AppraisalConfig::kompetensiComplete($comps)) {
            return redirect()->to('appraisal/forms/' . $id)->with('error', 'Lengkapi nilai kompetensi sebelum finalisasi.');
        }
        $this->recompute($id);
        $formModel->update($id, [
            'status'        => 'finalized',
            'current_user_id' => null,
            'finalized_by'  => $this->uid(),
            'finalized_at'  => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::write('update', 'appraisal_form', (string) $id, 'Finalisasi penilaian');
        return redirect()->to('appraisal/forms/' . $id)->with('success', 'Penilaian difinalisasi.');
    }

    // ── Pendapat karyawan (acknowledgment) ──────────────────────────────
    public function savePendapat(int $id)
    {
        $formModel = new AppraisalFormModel();
        $form = $formModel->getDetail($id);
        if (! $form) return redirect()->to('/')->with('error', 'Form tidak ditemukan.');

        $isEmployee = isset($form['employee_user_id']) && (int) $form['employee_user_id'] === $this->uid();
        if (! $isEmployee && ! $this->isHr()) return redirect()->to('appraisal/forms/' . $id)->with('error', 'Akses ditolak.');

        $formModel->update($id, ['pendapat_karyawan' => trim($this->request->getPost('pendapat_karyawan') ?? '') ?: null]);
        ActivityLog::write('update', 'appraisal_form', (string) $id, 'Pendapat karyawan');
        return redirect()->to('appraisal/forms/' . $id)->with('success', 'Pendapat disimpan.');
    }

    // ── Cetak ────────────────────────────────────────────────────────────
    public function printForm(int $id)
    {
        $formModel = new AppraisalFormModel();
        $form = $formModel->getDetail($id);
        if (! $form) return redirect()->to('/')->with('error', 'Form tidak ditemukan.');
        if (! $this->canView($form)) return redirect()->to('/')->with('error', 'Akses ditolak.');

        return view('appraisal/form_print', [
            'form'  => $form,
            'kpis'  => (new AppraisalFormKpiModel())->getByForm($id),
            'comps' => (new AppraisalFormCompetencyModel())->getByForm($id),
            'areas' => AppraisalConfig::AREAS,
            'units' => AppraisalConfig::UNITS,
            'skala' => AppraisalConfig::SKALA,
        ]);
    }
}
