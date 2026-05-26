<?php

namespace App\Controllers;

use App\Models\IdpPlanModel;
use App\Models\IdpItemModel;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use App\Models\TnaPeriodModel;
use App\Models\TnaAssessmentItemModel;
use App\Models\CompetencyModel;
use App\Models\CompetencyTargetModel;
use App\Models\TrainingCompetencyModel;
use App\Libraries\ActivityLog;
use App\Libraries\EmailNotifier;

class PeopleIdp extends BaseController
{
    private function guard(bool $edit = false): bool
    {
        return $edit ? $this->canEditMenu('people_dev') : $this->canViewMenu('people_dev');
    }

    public function index()
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $filters = [
            'status'      => $this->request->getGet('status') ?? '',
            'dept_id'     => $this->request->getGet('dept_id')     ? (int)$this->request->getGet('dept_id')     : '',
            'employee_id' => $this->request->getGet('employee_id') ? (int)$this->request->getGet('employee_id') : '',
            'tahun'       => $this->request->getGet('tahun')       ? (int)$this->request->getGet('tahun')       : '',
        ];

        return view('people/idp/index', [
            'user'        => $this->currentUser(),
            'plans'       => (new IdpPlanModel())->getAllWithEmployee($filters),
            'stats'       => (new IdpPlanModel())->getDashboardStats(),
            'employees'   => (new EmployeeModel())->getWithDept(),
            'departments' => (new DepartmentModel())->findAll(),
            'tnaPeriods'  => (new TnaPeriodModel())->orderBy('tahun', 'DESC')->findAll(),
            'filters'     => $filters,
            'canEdit'     => $this->canEditMenu('people_dev'),
        ]);
    }

    public function store()
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post  = $this->request->getPost();
        $token = bin2hex(random_bytes(32));

        $planId = (new IdpPlanModel())->insert([
            'employee_id'        => (int)$post['employee_id'],
            'tna_period_id'      => ! empty($post['tna_period_id']) ? (int)$post['tna_period_id'] : null,
            'periode_label'      => trim($post['periode_label']),
            'tahun'              => (int)$post['tahun'],
            'tujuan_karir'       => trim($post['tujuan_karir'] ?? '') ?: null,
            'catatan'            => trim($post['catatan'] ?? '') ?: null,
            'status'             => 'draft',
            'token_atasan'       => $token,
            'persetujuan_atasan' => 'pending',
            'created_by_user_id' => session()->get('user_id'),
        ]);

        $this->saveItems((int)$planId, $post);
        ActivityLog::write('create', 'idp_plan', (string)$planId, trim($post['periode_label']));

        $plan = (new IdpPlanModel())->getWithEmployee((int)$planId);
        if ($plan && ! empty($plan['atasan_email'])) {
            $url  = base_url('idp/approval/' . $token);
            $body = EmailNotifier::idpApprovalAtasan($plan, $url);
            EmailNotifier::send($plan['atasan_email'], 'Persetujuan IDP — ' . $plan['periode_label'], $body);
        }

        return redirect()->to('/people/idp/' . $planId)->with('success', 'IDP berhasil dibuat.');
    }

    public function show(int $id)
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new IdpPlanModel())->getWithEmployee($id);
        if (! $plan) return redirect()->to('/people/idp')->with('error', 'IDP tidak ditemukan.');

        $items = (new IdpItemModel())->getByIdp($id);

        // Training recommendations for each item that has a competency_id
        $compIds = array_filter(array_column($items, 'competency_id'));
        $trainingRecs = ! empty($compIds)
            ? (new TrainingCompetencyModel())->getRecommendedByCompetencies(array_values($compIds))
            : [];

        return view('people/idp/detail', [
            'user'         => $this->currentUser(),
            'plan'         => $plan,
            'items'        => $items,
            'trainingRecs' => $trainingRecs,
            'competencies' => (new CompetencyModel())->findAll(),
            'canEdit'      => $this->canEditMenu('people_dev'),
        ]);
    }

    public function update(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $model    = new IdpPlanModel();
        ActivityLog::captureBefore($model->find($id));

        $model->update($id, [
            'tna_period_id' => ! empty($post['tna_period_id']) ? (int)$post['tna_period_id'] : null,
            'periode_label' => trim($post['periode_label']),
            'tahun'         => (int)$post['tahun'],
            'tujuan_karir'  => trim($post['tujuan_karir'] ?? '') ?: null,
            'catatan'       => trim($post['catatan'] ?? '') ?: null,
            'status'        => $post['status'],
        ]);
        ActivityLog::captureAfter($model->find($id));
        ActivityLog::write('update', 'idp_plan', (string)$id, trim($post['periode_label']));

        return redirect()->to('/people/idp/' . $id)->with('success', 'IDP diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new IdpPlanModel())->find($id);
        $db   = db_connect();

        $db->transStart();
        $db->table('idp_items')->where('idp_id', $id)->delete();
        (new IdpPlanModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'idp_plan', (string)$id, $plan['periode_label'] ?? '');
        return redirect()->to('/people/idp')->with('success', 'IDP dihapus.');
    }

    public function generateToken(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $token = bin2hex(random_bytes(32));
        (new IdpPlanModel())->update($id, ['token_atasan' => $token]);

        ActivityLog::write('update', 'idp_plan', (string)$id, 'regenerate token atasan');
        return redirect()->to('/people/idp/' . $id)->with('success', 'Link atasan berhasil dibuat ulang.');
    }

    public function printIdp(int $id)
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new IdpPlanModel())->getWithEmployee($id);
        if (! $plan) return redirect()->to('/people/idp')->with('error', 'IDP tidak ditemukan.');

        return view('people/idp/print', [
            'plan'  => $plan,
            'items' => (new IdpItemModel())->getByIdp($id),
        ]);
    }

    public function importFromTna(int $periodId, int $empId)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $period   = (new TnaPeriodModel())->find($periodId);
        $employee = (new EmployeeModel())->findWithDept($empId);
        if (! $period || ! $employee) return redirect()->to('/people/idp')->with('error', 'Data TNA tidak ditemukan.');

        $targetMap  = $employee['dept_id']
            ? (new CompetencyTargetModel())->getTargetForEmployee((int)$employee['dept_id'], $employee['jabatan'] ?? '')
            : [];
        $rawResults = (new TnaAssessmentItemModel())->getResultForEmployee($periodId, $empId);

        $ws = (int)($period['weight_self']   ?? 20);
        $wa = (int)($period['weight_atasan'] ?? 50);
        $wr = (int)($period['weight_rekan']  ?? 30);

        $matrix = [];
        foreach ($rawResults as $r) {
            $matrix[$r['competency_id']][$r['assessor_type']] = round((float)$r['avg_level'], 2);
        }
        foreach ($matrix as $cid => &$row) {
            $sum = 0; $totalW = 0;
            if (isset($row['self']))   { $sum += $row['self']   * $ws; $totalW += $ws; }
            if (isset($row['atasan'])) { $sum += $row['atasan'] * $wa; $totalW += $wa; }
            if (isset($row['rekan']))  { $sum += $row['rekan']  * $wr; $totalW += $wr; }
            $row['overall'] = $totalW > 0 ? round($sum / $totalW, 2) : null;
        }
        unset($row);

        $compModel = new CompetencyModel();
        $grouped   = $this->resolveGrouped($compModel, $employee);
        $allComps  = array_merge($grouped['hard'] ?? [], $grouped['soft'] ?? []);

        // Only competencies with gap > 0
        $gapItems = [];
        foreach ($allComps as $comp) {
            $cid    = $comp['id'];
            $target = (float)($targetMap[$cid] ?? 0);
            $score  = $matrix[$cid]['overall'] ?? null;
            if ($target > 0 && $score !== null && $score < $target) {
                $gapItems[] = [
                    'competency_id'  => $cid,
                    'competency_nama'=> $comp['nama'],
                    'level_saat_ini' => $score,
                    'level_target'   => $target,
                ];
            }
        }

        return view('people/idp/index', [
            'user'          => $this->currentUser(),
            'plans'         => (new IdpPlanModel())->getAllWithEmployee([]),
            'stats'         => (new IdpPlanModel())->getDashboardStats(),
            'employees'     => (new EmployeeModel())->getWithDept(),
            'departments'   => (new DepartmentModel())->findAll(),
            'tnaPeriods'    => (new TnaPeriodModel())->orderBy('tahun', 'DESC')->findAll(),
            'filters'       => [],
            'canEdit'       => $this->canEditMenu('people_dev'),
            'prefill'       => [
                'employee_id'    => $empId,
                'tna_period_id'  => $periodId,
                'periode_label'  => $period['nama'] ?? '',
                'tahun'          => $period['tahun'] ?? date('Y'),
                'gapItems'       => $gapItems,
            ],
            'openModal'     => 'create',
        ]);
    }

    // ── Item CRUD ──────────────────────────────────────────────

    public function storeItem(int $idpId)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new IdpItemModel())->insert([
            'idp_id'          => $idpId,
            'competency_id'   => ! empty($post['competency_id']) ? (int)$post['competency_id'] : null,
            'judul'           => trim($post['judul']),
            'level_saat_ini'  => ! empty($post['level_saat_ini']) ? (int)$post['level_saat_ini'] : null,
            'level_target'    => ! empty($post['level_target'])   ? (int)$post['level_target']   : null,
            'langkah_aksi'    => trim($post['langkah_aksi'] ?? '') ?: null,
            'sumber_daya'     => trim($post['sumber_daya']  ?? '') ?: null,
            'deadline'        => ! empty($post['deadline']) ? $post['deadline'] : null,
            'status'          => 'belum_mulai',
            'urutan'          => (int)($post['urutan'] ?? 0),
        ]);

        ActivityLog::write('create', 'idp_item', (string)$idpId, trim($post['judul']));
        return redirect()->to('/people/idp/' . $idpId)->with('success', 'Item ditambahkan.');
    }

    public function updateItem(int $idpId, int $itemId)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post  = $this->request->getPost();
        $model = new IdpItemModel();
        ActivityLog::captureBefore($model->find($itemId));

        $model->update($itemId, [
            'competency_id'   => ! empty($post['competency_id']) ? (int)$post['competency_id'] : null,
            'judul'           => trim($post['judul']),
            'level_saat_ini'  => ! empty($post['level_saat_ini']) ? (int)$post['level_saat_ini'] : null,
            'level_target'    => ! empty($post['level_target'])   ? (int)$post['level_target']   : null,
            'langkah_aksi'    => trim($post['langkah_aksi'] ?? '') ?: null,
            'sumber_daya'     => trim($post['sumber_daya']  ?? '') ?: null,
            'deadline'        => ! empty($post['deadline']) ? $post['deadline'] : null,
            'status'          => $post['status'] ?? 'belum_mulai',
            'catatan_progres' => trim($post['catatan_progres'] ?? '') ?: null,
            'urutan'          => (int)($post['urutan'] ?? 0),
        ]);
        ActivityLog::captureAfter($model->find($itemId));
        ActivityLog::write('update', 'idp_item', (string)$itemId, trim($post['judul']));

        return redirect()->to('/people/idp/' . $idpId)->with('success', 'Item diperbarui.');
    }

    public function deleteItem(int $idpId, int $itemId)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $item = (new IdpItemModel())->find($itemId);
        (new IdpItemModel())->delete($itemId);
        ActivityLog::write('delete', 'idp_item', (string)$itemId, $item['judul'] ?? '');

        return redirect()->to('/people/idp/' . $idpId)->with('success', 'Item dihapus.');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function saveItems(int $idpId, array $post): void
    {
        $juduls      = $post['item_judul']        ?? [];
        $compIds     = $post['item_competency_id'] ?? [];
        $levelSkrgs  = $post['item_level_saat_ini']?? [];
        $levelTargets= $post['item_level_target']  ?? [];
        $langkahs    = $post['item_langkah_aksi']  ?? [];
        $sumbers     = $post['item_sumber_daya']   ?? [];
        $deadlines   = $post['item_deadline']      ?? [];

        $model = new IdpItemModel();
        foreach ($juduls as $i => $judul) {
            $judul = trim($judul);
            if ($judul === '') continue;
            $model->insert([
                'idp_id'         => $idpId,
                'competency_id'  => ! empty($compIds[$i])      ? (int)$compIds[$i]      : null,
                'judul'          => $judul,
                'level_saat_ini' => ! empty($levelSkrgs[$i])   ? (int)$levelSkrgs[$i]   : null,
                'level_target'   => ! empty($levelTargets[$i]) ? (int)$levelTargets[$i] : null,
                'langkah_aksi'   => trim($langkahs[$i] ?? '') ?: null,
                'sumber_daya'    => trim($sumbers[$i]  ?? '') ?: null,
                'deadline'       => ! empty($deadlines[$i]) ? $deadlines[$i] : null,
                'status'         => 'belum_mulai',
                'urutan'         => $i,
            ]);
        }
    }

    private function resolveGrouped(CompetencyModel $m, ?array $employee): array
    {
        if ($employee['jabatan_id'] ?? null) {
            $byJabatan = $m->getAllGroupedForJabatan((int)$employee['jabatan_id']);
            if (! empty($byJabatan['hard']) || ! empty($byJabatan['soft'])) return $byJabatan;
        }
        if ($employee['dept_id'] ?? null) {
            $byDept = $m->getAllGroupedForDept((int)$employee['dept_id']);
            if (! empty($byDept['hard']) || ! empty($byDept['soft'])) return $byDept;
        }
        return $m->getAllGrouped();
    }
}
