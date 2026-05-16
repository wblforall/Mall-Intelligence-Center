<?php

namespace App\Controllers;

use App\Models\TnaPeriodModel;
use App\Models\TnaAssessmentModel;
use App\Models\TnaAssessmentItemModel;
use App\Models\EmployeeModel;
use App\Models\CompetencyModel;
use App\Models\CompetencyTargetModel;
use App\Models\CompetencyQuestionModel;
use App\Models\TrainingCompetencyModel;
use App\Libraries\ActivityLog;
use App\Libraries\EmailNotifier;

class PeopleTna extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        return view('people/tna/index', [
            'user'    => $this->currentUser(),
            'periods' => (new TnaPeriodModel())->getAllWithStats(),
        ]);
    }

    public function storePeriod()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $id = (new TnaPeriodModel())->insert([
            'nama'            => trim($post['nama']),
            'tahun'           => (int)$post['tahun'],
            'tanggal_mulai'   => $post['tanggal_mulai'] ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'catatan'         => trim($post['catatan'] ?? '') ?: null,
            'weight_self'     => max(0, (int)($post['weight_self']   ?? 20)),
            'weight_atasan'   => max(0, (int)($post['weight_atasan'] ?? 50)),
            'weight_rekan'    => max(0, (int)($post['weight_rekan']  ?? 30)),
        ]);
        ActivityLog::write('create', 'tna_period', (string)$id, trim($post['nama']));
        return redirect()->to('/people/tna')->with('success', 'Periode TNA berhasil dibuat.');
    }

    public function updatePeriod(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        (new TnaPeriodModel())->update($id, [
            'nama'            => trim($post['nama']),
            'tahun'           => (int)$post['tahun'],
            'tanggal_mulai'   => $post['tanggal_mulai'] ?: null,
            'tanggal_selesai' => $post['tanggal_selesai'] ?: null,
            'catatan'         => trim($post['catatan'] ?? '') ?: null,
            'weight_self'     => max(0, (int)($post['weight_self']   ?? 20)),
            'weight_atasan'   => max(0, (int)($post['weight_atasan'] ?? 50)),
            'weight_rekan'    => max(0, (int)($post['weight_rekan']  ?? 30)),
        ]);
        ActivityLog::write('update', 'tna_period', (string)$id, trim($post['nama']));
        return redirect()->to('/people/tna')->with('success', 'Periode TNA diperbarui.');
    }

    public function deletePeriod(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period = (new TnaPeriodModel())->find($id);
        $db = db_connect();

        $assessmentIds = array_column(
            $db->table('tna_assessments')->where('period_id', $id)->get()->getResultArray(),
            'id'
        );

        $db->transStart();
        if ($assessmentIds) {
            $db->table('tna_assessment_items')->whereIn('assessment_id', $assessmentIds)->delete();
            $db->table('tna_assessments')->whereIn('id', $assessmentIds)->delete();
        }
        (new TnaPeriodModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'tna_period', (string)$id, $period['nama'] ?? '');
        return redirect()->to('/people/tna')->with('success', 'Periode TNA dihapus.');
    }

    public function toggleClose(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period = (new TnaPeriodModel())->find($id);
        if (! $period) return redirect()->to('/people/tna');

        $newStatus = $period['status'] === 'open' ? 'closed' : 'open';
        (new TnaPeriodModel())->update($id, ['status' => $newStatus]);
        ActivityLog::write('update', 'tna_period', (string)$id, $period['nama'] . ' → ' . $newStatus);
        return redirect()->to('/people/tna/period/' . $id)
            ->with('success', 'Status periode diubah ke ' . $newStatus . '.');
    }

    public function period(int $periodId)
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period = (new TnaPeriodModel())->find($periodId);
        if (! $period) return redirect()->to('/people/tna')->with('error', 'Periode tidak ditemukan.');

        $assModel  = new TnaAssessmentModel();
        $employees = $assModel->getEmployeesByPeriod($periodId);

        $assessorMap = [];
        foreach ($employees as $emp) {
            $assessorMap[$emp['employee_id']] = $assModel->getByPeriodEmployee($periodId, $emp['employee_id']);
        }

        $existingIds  = array_column($employees, 'employee_id');
        $allEmployees = (new EmployeeModel())->getWithDept();
        $available    = array_values(array_filter($allEmployees, fn($e) => $e['status'] === 'aktif' && !in_array($e['id'], $existingIds)));

        // For assessor picker: ALL employees (active + inactive) so atasan chain traversal
        // doesn't break on vacant positions; status field used client-side to filter rekan list
        $empForPicker = array_map(fn($e) => [
            'id'            => (int)$e['id'],
            'nama'          => $e['nama'],
            'jabatan'       => $e['jabatan'] ?? '',
            'dept_id'       => (int)($e['dept_id'] ?? 0),
            'dept_name'     => $e['dept_name'] ?? '',
            'atasan_id'     => $e['atasan_id'] ? (int)$e['atasan_id'] : null,
            'jabatan_grade' => $e['jabatan_grade'] ?? null,
            'status'        => $e['status'] ?? 'aktif',
        ], $allEmployees);

        return view('people/tna/period', [
            'user'        => $this->currentUser(),
            'period'      => $period,
            'employees'   => $employees,
            'assessorMap' => $assessorMap,
            'available'   => $available,
            'empPickerJson' => json_encode(array_values($empForPicker)),
        ]);
    }

    public function addEmployee(int $periodId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period = (new TnaPeriodModel())->find($periodId);
        if (! $period || $period['status'] === 'closed') {
            return redirect()->to('/people/tna/period/' . $periodId)->with('error', 'Periode sudah ditutup.');
        }

        $empId    = (int)$this->request->getPost('employee_id');
        $assModel = new TnaAssessmentModel();

        $existing = $assModel->where('period_id', $periodId)
                             ->where('employee_id', $empId)
                             ->where('assessor_type', 'self')
                             ->first();
        if ($existing) {
            return redirect()->to('/people/tna/period/' . $periodId)->with('error', 'Karyawan sudah ada dalam periode ini.');
        }

        $assModel->insert([
            'period_id'        => $periodId,
            'employee_id'      => $empId,
            'assessor_type'    => 'self',
            'assessor_name'    => null,
            'status'           => 'draft',
            'fill_token'       => self::generateFillToken(),
            'token_expires_at' => null,
        ]);

        $emp = (new EmployeeModel())->find($empId);
        ActivityLog::write('create', 'tna_assessment', (string)$periodId, 'Karyawan: ' . ($emp['nama'] ?? ''));
        return redirect()->to('/people/tna/period/' . $periodId)->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function removeEmployee(int $periodId, int $empId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $db = db_connect();
        $assessmentIds = array_column(
            $db->table('tna_assessments')
               ->where('period_id', $periodId)
               ->where('employee_id', $empId)
               ->get()->getResultArray(),
            'id'
        );

        $db->transStart();
        if ($assessmentIds) {
            $db->table('tna_assessment_items')->whereIn('assessment_id', $assessmentIds)->delete();
            $db->table('tna_assessments')->whereIn('id', $assessmentIds)->delete();
        }
        $db->transComplete();

        ActivityLog::write('delete', 'tna_assessment', (string)$periodId, 'Hapus karyawan id:' . $empId);
        return redirect()->to('/people/tna/period/' . $periodId)->with('success', 'Karyawan dihapus dari periode.');
    }

    public function addAssessor(int $periodId, int $empId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period = (new TnaPeriodModel())->find($periodId);
        if (! $period || $period['status'] === 'closed') {
            return redirect()->to('/people/tna/period/' . $periodId)->with('error', 'Periode sudah ditutup.');
        }

        $post  = $this->request->getPost();
        $type  = $post['assessor_type'] ?? '';
        $names = array_values(array_filter(array_map('trim', (array)($post['assessor_names'] ?? []))));

        if (! in_array($type, ['atasan', 'rekan']) || empty($names)) {
            return redirect()->to('/people/tna/period/' . $periodId)->with('error', 'Data assessor tidak valid.');
        }

        $model = new TnaAssessmentModel();
        $added = 0;
        foreach ($names as $name) {
            $dup = $model->where('period_id', $periodId)->where('employee_id', $empId)
                         ->where('assessor_type', $type)->where('assessor_name', $name)->first();
            if ($dup) continue;
            $model->insert([
                'period_id'        => $periodId,
                'employee_id'      => $empId,
                'assessor_type'    => $type,
                'assessor_name'    => $name,
                'status'           => 'draft',
                'fill_token'       => self::generateFillToken(),
                'token_expires_at' => null,
            ]);
            $added++;
        }

        ActivityLog::write('create', 'tna_assessment', (string)$periodId, 'Assessor ' . $type . ': ' . implode(', ', $names));
        return redirect()->to('/people/tna/period/' . $periodId)
            ->with('success', $added . ' assessor berhasil ditambahkan.');
    }

    public function removeAssessor(int $periodId, int $assessmentId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $assessment = (new TnaAssessmentModel())->find($assessmentId);
        if ($assessment && $assessment['assessor_type'] === 'self') {
            return redirect()->to('/people/tna/period/' . $periodId)->with('error', 'Self-assessment tidak dapat dihapus. Hapus karyawan dari periode jika perlu.');
        }

        $db = db_connect();
        $db->table('tna_assessment_items')->where('assessment_id', $assessmentId)->delete();
        (new TnaAssessmentModel())->delete($assessmentId);

        ActivityLog::write('delete', 'tna_assessment', (string)$assessmentId, 'Hapus assessor');
        return redirect()->to('/people/tna/period/' . $periodId)->with('success', 'Assessor dihapus.');
    }

    public function assess(int $assessmentId)
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $assessment = (new TnaAssessmentModel())->find($assessmentId);
        if (! $assessment) return redirect()->to('/people/tna')->with('error', 'Assessment tidak ditemukan.');

        $period    = (new TnaPeriodModel())->find($assessment['period_id']);
        $employee  = (new EmployeeModel())->findWithDept($assessment['employee_id']);
        $compModel = new CompetencyModel();
        $grouped   = self::resolveGrouped($compModel, $employee);
        $itemMap   = (new TnaAssessmentItemModel())->getMapByAssessment($assessmentId);

        return view('people/tna/assess', [
            'user'         => $this->currentUser(),
            'assessment'   => $assessment,
            'period'       => $period,
            'employee'     => $employee,
            'grouped'      => $grouped,
            'itemMap'      => $itemMap,
            'questionsMap' => (new CompetencyQuestionModel())->getAllGrouped(),
        ]);
    }

    public function submitAssessment(int $assessmentId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $assessment = (new TnaAssessmentModel())->find($assessmentId);
        if (! $assessment) return redirect()->to('/people/tna')->with('error', 'Assessment tidak ditemukan.');

        if ($assessment['status'] === 'submitted') {
            return redirect()->to('/people/tna/period/' . $assessment['period_id'])
                ->with('error', 'Assessment sudah disubmit.');
        }

        $post      = $this->request->getPost();
        $answers   = $post['score'] ?? [];
        $action    = $post['action'] ?? 'draft';
        $itemModel = new TnaAssessmentItemModel();
        $itemModel->saveForAssessment($assessmentId, $answers);

        if ($action === 'submit') {
            (new TnaAssessmentModel())->update($assessmentId, [
                'status'       => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            ActivityLog::write('submit', 'tna_assessment', (string)$assessmentId, $assessment['assessor_type']);
            return redirect()->to('/people/tna/period/' . $assessment['period_id'])
                ->with('success', 'Assessment berhasil disubmit.');
        }

        ActivityLog::write('update', 'tna_assessment', (string)$assessmentId, 'draft saved');
        return redirect()->to('/people/tna/assess/' . $assessmentId)->with('success', 'Draft tersimpan.');
    }

    public function regenerateToken(int $assessmentId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $assessment = (new TnaAssessmentModel())->find($assessmentId);
        if (! $assessment) return redirect()->to('/people/tna')->with('error', 'Assessment tidak ditemukan.');

        $token = self::generateFillToken();
        (new TnaAssessmentModel())->update($assessmentId, ['fill_token' => $token]);
        ActivityLog::write('update', 'tna_assessment', (string)$assessmentId, 'regenerate token');
        return redirect()->to('/people/tna/period/' . $assessment['period_id'])->with('success', 'Link baru berhasil dibuat.');
    }

    // Priority: jabatan-specific map > dept map > all competencies
    private static function resolveGrouped(CompetencyModel $m, ?array $employee): array
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

    public function sendEmail(int $periodId, int $empId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $period = (new TnaPeriodModel())->find($periodId);
        $emp    = (new EmployeeModel())->find($empId);

        if (! $period || ! $emp || empty($emp['email'])) {
            return redirect()->to('/people/tna/period/' . $periodId)
                ->with('error', 'Email karyawan tidak ditemukan.');
        }

        $assessment = (new TnaAssessmentModel())
            ->where('period_id', $periodId)
            ->where('employee_id', $empId)
            ->where('assessor_type', 'self')
            ->first();

        if (! $assessment || empty($assessment['fill_token'])) {
            return redirect()->to('/people/tna/period/' . $periodId)
                ->with('error', 'Token formulir belum tersedia untuk karyawan ini.');
        }

        $url  = base_url('tna/fill/' . $assessment['fill_token']);
        $body = EmailNotifier::tnaFillLink($emp['nama'], $period['nama'], $url);
        EmailNotifier::send($emp['email'], 'Formulir TNA — ' . $period['nama'], $body);

        ActivityLog::write('send_email', 'tna_assessment', (string)$assessment['id'], $emp['nama']);
        return redirect()->to('/people/tna/period/' . $periodId)
            ->with('success', 'Email TNA berhasil dikirim ke ' . $emp['nama'] . '.');
    }

    public function sendEmailAll(int $periodId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $period      = (new TnaPeriodModel())->find($periodId);
        $assModel    = new TnaAssessmentModel();
        $empModel    = new EmployeeModel();
        $employees   = $assModel->getEmployeesByPeriod($periodId);
        $sent = 0; $skipped = 0;

        foreach ($employees as $empRow) {
            $emp = $empModel->find((int)$empRow['employee_id']);
            if (! $emp || empty($emp['email'])) { $skipped++; continue; }

            $assessment = $assModel
                ->where('period_id', $periodId)
                ->where('employee_id', (int)$empRow['employee_id'])
                ->where('assessor_type', 'self')
                ->first();

            if (! $assessment || empty($assessment['fill_token']) || $assessment['status'] === 'submitted') {
                $skipped++;
                continue;
            }

            $url  = base_url('tna/fill/' . $assessment['fill_token']);
            $body = EmailNotifier::tnaFillLink($emp['nama'], $period['nama'], $url);
            if (EmailNotifier::send($emp['email'], 'Formulir TNA — ' . $period['nama'], $body)) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        ActivityLog::write('send_email', 'tna_period', (string)$periodId, "bulk: {$sent} sent, {$skipped} skipped");
        return redirect()->to('/people/tna/period/' . $periodId)
            ->with('success', "Email TNA berhasil dikirim ke {$sent} karyawan." . ($skipped ? " ({$skipped} dilewati)" : ''));
    }

    private static function generateFillToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function result(int $periodId, int $empId)
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $period   = (new TnaPeriodModel())->find($periodId);
        $employee = (new EmployeeModel())->findWithDept($empId);
        if (! $period || ! $employee) return redirect()->to('/people/tna')->with('error', 'Data tidak ditemukan.');

        $targetMap  = $employee['dept_id']
            ? (new CompetencyTargetModel())->getTargetForEmployee((int)$employee['dept_id'], $employee['jabatan'] ?? '')
            : [];
        $rawResults  = (new TnaAssessmentItemModel())->getResultForEmployee($periodId, $empId);
        $assessments = (new TnaAssessmentModel())->getByPeriodEmployee($periodId, $empId);

        // Weights from period (default 20/50/30 if not set)
        $ws = (int)($period['weight_self']   ?? 20);
        $wa = (int)($period['weight_atasan'] ?? 50);
        $wr = (int)($period['weight_rekan']  ?? 30);

        // Build matrix [competency_id => [self, atasan, rekan, overall]]
        $matrix = [];
        foreach ($rawResults as $r) {
            $matrix[$r['competency_id']][$r['assessor_type']] = round((float)$r['avg_level'], 2);
        }

        // Weighted overall per competency
        foreach ($matrix as $cid => &$row) {
            $sum = 0; $totalW = 0;
            if (isset($row['self']))   { $sum += $row['self']   * $ws; $totalW += $ws; }
            if (isset($row['atasan'])) { $sum += $row['atasan'] * $wa; $totalW += $wa; }
            if (isset($row['rekan']))  { $sum += $row['rekan']  * $wr; $totalW += $wr; }
            $row['overall'] = $totalW > 0 ? round($sum / $totalW, 2) : null;
        }
        unset($row);

        // Competencies below target — find training recommendations
        $belowTargetIds = [];
        foreach ($matrix as $cid => $row) {
            $target = (float)($targetMap[$cid] ?? 0);
            if ($target > 0 && $row['overall'] !== null && $row['overall'] < $target) {
                $belowTargetIds[] = $cid;
            }
        }
        $trainingRecommendations = (new TrainingCompetencyModel())->getRecommendedByCompetencies($belowTargetIds);

        return view('people/tna/result', [
            'user'                   => $this->currentUser(),
            'period'                 => $period,
            'employee'               => $employee,
            'grouped'                => self::resolveGrouped(new CompetencyModel(), $employee),
            'targetMap'              => $targetMap,
            'matrix'                 => $matrix,
            'assessments'            => $assessments,
            'trainingRecommendations'=> $trainingRecommendations,
            'belowTargetIds'         => $belowTargetIds,
        ]);
    }
}
