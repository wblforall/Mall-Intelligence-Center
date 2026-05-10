<?php

namespace App\Controllers;

use App\Models\TnaAssessmentModel;
use App\Models\TnaAssessmentItemModel;
use App\Models\TnaPeriodModel;
use App\Models\EmployeeModel;
use App\Models\CompetencyModel;
use App\Models\CompetencyQuestionModel;
use App\Libraries\ActivityLog;
use CodeIgniter\Controller;

class TnaFill extends Controller
{
    public function show(string $token)
    {
        $assessment = (new TnaAssessmentModel())->where('fill_token', $token)->first();
        if (! $assessment) {
            return $this->notFound();
        }

        if ($assessment['status'] === 'submitted') {
            return view('people/tna/fill_done', [
                'message' => 'Penilaian ini sudah disubmit. Terima kasih!',
            ]);
        }

        $period   = (new TnaPeriodModel())->find($assessment['period_id']);
        if ($period && $period['status'] === 'closed') {
            return view('people/tna/fill_done', [
                'message' => 'Periode TNA sudah ditutup. Penilaian tidak dapat diisi lagi.',
            ]);
        }

        $employee  = (new EmployeeModel())->findWithDept($assessment['employee_id']);
        $compModel = new CompetencyModel();
        $grouped   = $this->resolveGrouped($compModel, $employee);
        $itemMap   = (new TnaAssessmentItemModel())->getMapByAssessment($assessment['id']);

        return view('people/tna/fill', [
            'token'        => $token,
            'assessment'   => $assessment,
            'period'       => $period,
            'employee'     => $employee,
            'grouped'      => $grouped,
            'itemMap'      => $itemMap,
            'questionsMap' => (new CompetencyQuestionModel())->getAllGrouped(),
        ]);
    }

    public function submit(string $token)
    {
        $assessment = (new TnaAssessmentModel())->where('fill_token', $token)->first();
        if (! $assessment) {
            return $this->notFound();
        }

        if ($assessment['status'] === 'submitted') {
            return view('people/tna/fill_done', [
                'message' => 'Penilaian ini sudah disubmit sebelumnya.',
            ]);
        }

        $period = (new TnaPeriodModel())->find($assessment['period_id']);
        if ($period && $period['status'] === 'closed') {
            return view('people/tna/fill_done', [
                'message' => 'Periode TNA sudah ditutup.',
            ]);
        }

        $post    = $this->request->getPost();
        $answers = $post['score'] ?? [];
        $action  = $post['action'] ?? 'draft';

        (new TnaAssessmentItemModel())->saveForAssessment($assessment['id'], $answers);

        if ($action === 'submit') {
            (new TnaAssessmentModel())->update($assessment['id'], [
                'status'       => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            ActivityLog::write('submit', 'tna_assessment', (string)$assessment['id'], 'via token: ' . $assessment['assessor_type']);
            return view('people/tna/fill_done', [
                'message' => 'Penilaian berhasil disubmit. Terima kasih!',
            ]);
        }

        ActivityLog::write('update', 'tna_assessment', (string)$assessment['id'], 'draft via token');
        return redirect()->to(base_url('tna/fill/' . $token))->with('success', 'Draft tersimpan.');
    }

    private function resolveGrouped(CompetencyModel $m, ?array $employee): array
    {
        if ($employee['jabatan_id'] ?? null) {
            $byJabatan = $m->getAllGroupedForJabatan((int)$employee['jabatan_id']);
            if (! empty($byJabatan['hard']) || ! empty($byJabatan['soft'])) {
                return $byJabatan;
            }
        }
        if ($employee['dept_id'] ?? null) {
            $byDept = $m->getAllGroupedForDept((int)$employee['dept_id']);
            if (! empty($byDept['hard']) || ! empty($byDept['soft'])) {
                return $byDept;
            }
        }
        return $m->getAllGrouped();
    }

    private function notFound()
    {
        return view('people/tna/fill_done', [
            'message' => 'Link penilaian tidak valid atau sudah kedaluwarsa.',
        ]);
    }
}
