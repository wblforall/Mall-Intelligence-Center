<?php

namespace App\Controllers;

use App\Models\EeiDimensionModel;
use App\Models\EeiQuestionModel;
use App\Models\EeiPeriodModel;
use App\Models\EeiResponseModel;
use App\Models\EmployeeModel;
use App\Libraries\ActivityLog;

class PeopleEei extends BaseController
{
    // Results dashboard (admin / HR)
    public function index()
    {
        $periodModel = new EeiPeriodModel();
        $periods     = $periodModel->orderBy('start_date', 'DESC')->findAll();

        $periodId = (int)($this->request->getGet('period_id') ?? 0);
        if (! $periodId && ! empty($periods)) {
            $active   = array_filter($periods, fn($p) => $p['is_active']);
            $periodId = $active ? (int)current($active)['id'] : (int)$periods[0]['id'];
        }

        $respModel    = new EeiResponseModel();
        $totalEmp     = (new EmployeeModel())->where('status', 'aktif')->countAllResults();

        return view('people/eei/index', [
            'user'          => $this->currentUser(),
            'periods'       => $periods,
            'periodId'      => $periodId,
            'dimScores'       => $periodId ? $respModel->getScoreByDimension($periodId) : [],
            'deptScores'      => $periodId ? $respModel->getScoreByDept($periodId) : [],
            'levelScores'     => $periodId ? $respModel->getScoreByLevel($periodId) : [],
            'deptLevelScores' => $periodId ? $respModel->getScoreByDeptAndLevel($periodId) : [],
            'overall'         => $periodId ? $respModel->getOverallScore($periodId) : 0.0,
            'participation'   => $periodId ? $respModel->getParticipation($periodId, $totalEmp) : ['completed' => 0, 'total' => $totalEmp, 'percentage' => 0],
        ]);
    }

    // Admin: manage dimensions, questions, periods
    public function manage()
    {
        return view('people/eei/manage', [
            'user'       => $this->currentUser(),
            'dimensions' => (new EeiDimensionModel())->getWithQuestions(),
            'periods'    => (new EeiPeriodModel())->orderBy('start_date', 'DESC')->findAll(),
        ]);
    }

    // Employee survey
    public function survey()
    {
        $user   = $this->currentUser();
        $period = (new EeiPeriodModel())->getActivePeriod();

        if (! $period) {
            return view('people/eei/survey', [
                'user'       => $user,
                'period'     => null,
                'dimensions' => [],
                'completed'  => false,
                'deptId'     => 0,
                'departments'=> [],
            ]);
        }

        $userId    = (int)$user['id'];
        $respModel = new EeiResponseModel();
        $completed = $respModel->hasCompleted((int)$period['id'], $userId);

        $deptId = (int)(session()->get('dept_id') ?? 0);

        $depts = $deptId ? [] : (new \App\Models\DepartmentModel())->orderBy('name')->findAll();

        return view('people/eei/survey', [
            'user'        => $user,
            'period'      => $period,
            'dimensions'  => (new EeiDimensionModel())->getWithQuestions(),
            'completed'   => $completed,
            'deptId'      => $deptId,
            'departments' => $depts,
            'divisions'   => $deptId ? [] : (new \App\Models\DivisionModel())->orderBy('nama')->findAll(),
        ]);
    }

    public function submit()
    {
        $post   = $this->request->getPost();
        $user   = $this->currentUser();
        $userId = (int)$user['id'];

        $period = (new EeiPeriodModel())->find((int)($post['period_id'] ?? 0));
        if (! $period || ! $period['is_active']) {
            return redirect()->to('/people/eei/survey')->with('error', 'Periode survey tidak aktif.');
        }

        $respModel = new EeiResponseModel();
        if ($respModel->hasCompleted((int)$period['id'], $userId)) {
            return redirect()->to('/people/eei/survey')->with('error', 'Anda sudah mengisi survey ini.');
        }

        $deptId = (int)(session()->get('dept_id') ?? $post['dept_id'] ?? 0);
        if (! $deptId) {
            return redirect()->back()->with('error', 'Departemen tidak ditemukan. Hubungi admin.');
        }

        $scores       = $post['scores'] ?? [];
        $jabatanLevel = trim($post['jabatan_level'] ?? '') ?: null;

        if (empty($scores)) {
            return redirect()->back()->with('error', 'Tidak ada jawaban yang dikirim.');
        }

        $respModel->saveForPeriodDept((int)$period['id'], $deptId, $userId, $scores, $jabatanLevel, 'u_' . $userId);
        ActivityLog::write('create', 'eei_response', (string)$period['id'], 'Survey EEI submitted');

        return redirect()->to('/people/eei/survey')->with('success', 'Terima kasih! Jawaban Anda telah tersimpan secara anonim.');
    }

    // ── Public survey (no login required) ───────────────────────────────────

    public function publicSurvey(string $token)
    {
        $period = (new EeiPeriodModel())->where('survey_token', $token)->first();

        $data = [
            'token'       => $token,
            'period'      => $period,
            'dimensions'  => [],
            'completed'   => false,
            'departments' => [],
            'divisions'   => [],
            'error'       => null,
        ];

        if (! $period) {
            $data['error'] = 'Link survey tidak valid atau sudah kadaluarsa.';
            return view('people/eei/public_survey', $data);
        }

        $cookieKey     = 'eei_sub_' . $period['id'];
        $submissionKey = $this->request->getCookie($cookieKey);
        $respModel     = new EeiResponseModel();

        if ($submissionKey && $respModel->hasCompletedByKey((int)$period['id'], $submissionKey)) {
            $data['completed'] = true;
            return view('people/eei/public_survey', $data);
        }

        $data['dimensions']  = (new EeiDimensionModel())->getWithQuestions();
        $data['departments'] = (new \App\Models\DepartmentModel())->orderBy('name')->findAll();
        $data['divisions']   = (new \App\Models\DivisionModel())->orderBy('nama')->findAll();
        return view('people/eei/public_survey', $data);
    }

    public function publicSubmit(string $token)
    {
        $period = (new EeiPeriodModel())->where('survey_token', $token)->first();

        if (! $period || ! $period['is_active']) {
            return redirect()->to(base_url('eei/' . $token))->with('pub_error', 'Periode survey tidak aktif.');
        }

        $cookieKey     = 'eei_sub_' . $period['id'];
        $submissionKey = $this->request->getCookie($cookieKey);

        if (! $submissionKey) {
            $submissionKey = bin2hex(random_bytes(20));
        }

        $respModel = new EeiResponseModel();
        if ($respModel->hasCompletedByKey((int)$period['id'], $submissionKey)) {
            return redirect()->to(base_url('eei/' . $token))->with('pub_error', 'Anda sudah mengisi survey ini.');
        }

        $post   = $this->request->getPost();
        $deptId = (int)($post['dept_id'] ?? 0);
        if (! $deptId) {
            return redirect()->to(base_url('eei/' . $token))->with('pub_error', 'Departemen harus dipilih.');
        }

        $scores = $post['scores'] ?? [];
        if (empty($scores)) {
            return redirect()->to(base_url('eei/' . $token))->with('pub_error', 'Tidak ada jawaban yang dikirim.');
        }

        $jabatanLevel = trim($post['jabatan_level'] ?? '') ?: null;
        $respModel->saveForPeriodDept((int)$period['id'], $deptId, null, $scores, $jabatanLevel, $submissionKey);

        // Set cookie 1 year to prevent double submit
        $this->response->setCookie($cookieKey, $submissionKey, 31536000);

        return redirect()->to(base_url('eei/' . $token))->with('pub_success', 'Terima kasih! Jawaban Anda telah tersimpan secara anonim.');
    }

    // ── Period CRUD ──────────────────────────────────────────────────────────

    public function storePeriod()
    {
        $post  = $this->request->getPost();
        $model = new EeiPeriodModel();
        $id    = $model->insert([
            'nama'         => trim($post['nama']),
            'start_date'   => $post['start_date'],
            'end_date'     => $post['end_date'],
            'is_active'    => 0,
            'survey_token' => $model->generateToken(),
        ]);
        ActivityLog::write('create', 'eei_period', (string)$id, trim($post['nama']));
        return redirect()->to('/people/eei/manage')->with('success', 'Periode ditambahkan.');
    }

    public function updatePeriod(int $id)
    {
        $post = $this->request->getPost();
        (new EeiPeriodModel())->update($id, [
            'nama'       => trim($post['nama']),
            'start_date' => $post['start_date'],
            'end_date'   => $post['end_date'],
        ]);
        ActivityLog::write('update', 'eei_period', (string)$id, trim($post['nama']));
        return redirect()->to('/people/eei/manage')->with('success', 'Periode diperbarui.');
    }

    public function deletePeriod(int $id)
    {
        $p = (new EeiPeriodModel())->find($id);
        (new EeiPeriodModel())->delete($id);
        ActivityLog::write('delete', 'eei_period', (string)$id, $p['nama'] ?? '');
        return redirect()->to('/people/eei/manage')->with('success', 'Periode dihapus.');
    }

    public function activatePeriod(int $id)
    {
        (new EeiPeriodModel())->activate($id);
        ActivityLog::write('update', 'eei_period_activate', (string)$id, 'activated');
        return redirect()->to('/people/eei/manage')->with('success', 'Periode diaktifkan.');
    }

    // ── Dimension CRUD ───────────────────────────────────────────────────────

    public function storeDimension()
    {
        $post  = $this->request->getPost();
        $model = new EeiDimensionModel();
        $id    = $model->insert([
            'nama'      => trim($post['nama']),
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
            'urutan'    => $model->getNextUrutan(),
        ]);
        ActivityLog::write('create', 'eei_dimension', (string)$id, trim($post['nama']));
        return redirect()->to('/people/eei/manage')->with('success', 'Dimensi ditambahkan.');
    }

    public function updateDimension(int $id)
    {
        $post = $this->request->getPost();
        (new EeiDimensionModel())->update($id, [
            'nama'      => trim($post['nama']),
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
        ]);
        ActivityLog::write('update', 'eei_dimension', (string)$id, trim($post['nama']));
        return redirect()->to('/people/eei/manage')->with('success', 'Dimensi diperbarui.');
    }

    public function deleteDimension(int $id)
    {
        $dim = (new EeiDimensionModel())->find($id);
        (new EeiDimensionModel())->delete($id);
        ActivityLog::write('delete', 'eei_dimension', (string)$id, $dim['nama'] ?? '');
        return redirect()->to('/people/eei/manage')->with('success', 'Dimensi dihapus.');
    }

    // ── Question CRUD ────────────────────────────────────────────────────────

    public function storeQuestion(int $dimId)
    {
        $post  = $this->request->getPost();
        $model = new EeiQuestionModel();
        $id    = $model->insert([
            'dimension_id' => $dimId,
            'pertanyaan'   => trim($post['pertanyaan']),
            'urutan'       => $model->getNextUrutan($dimId),
            'is_reversed'  => (int)($post['is_reversed'] ?? 0),
        ]);
        ActivityLog::write('create', 'eei_question', (string)$id, substr(trim($post['pertanyaan']), 0, 60));
        return redirect()->to('/people/eei/manage')->with('success', 'Pertanyaan ditambahkan.');
    }

    public function deleteQuestion(int $id)
    {
        $q = (new EeiQuestionModel())->find($id);
        (new EeiQuestionModel())->delete($id);
        ActivityLog::write('delete', 'eei_question', (string)$id, substr($q['pertanyaan'] ?? '', 0, 60));
        return redirect()->to('/people/eei/manage')->with('success', 'Pertanyaan dihapus.');
    }
}
