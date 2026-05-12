<?php

namespace App\Controllers;

use App\Models\CompetencyModel;
use App\Models\CompetencyClusterModel;
use App\Models\CompetencyTargetModel;
use App\Models\CompetencyQuestionModel;
use App\Models\DepartmentModel;
use App\Libraries\ActivityLog;

class PeopleCompetencies extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $compModel   = new CompetencyModel();
        $targetModel = new CompetencyTargetModel();
        $deptId      = (int)($this->request->getGet('dept_id') ?? 0);
        $jabatan     = trim($this->request->getGet('jabatan') ?? '');
        $departments = (new DepartmentModel())->orderBy('name')->findAll();

        $deptTargetMap = [];
        $targetMap     = [];
        $jabatans      = [];
        $overrides     = [];
        $assignedIds          = [];
        $groupedTarget        = ['hard' => [], 'soft' => []];
        $groupedTargetCluster = [];

        $selectedJabatanId = 0;

        if ($deptId) {
            $deptTargetMap = $targetModel->getMapByDept($deptId);
            $jabatans      = db_connect()->table('jabatans')
                                ->where('dept_id', $deptId)
                                ->orderBy('grade')->orderBy('nama')
                                ->get()->getResultArray();
            $overrides     = $targetModel->getOverridingJabatans($deptId);
            $targetMap     = $jabatan
                ? $targetModel->getMapByDeptJabatan($deptId, $jabatan)
                : $deptTargetMap;
            $assignedIds   = $compModel->getAssignedIdsByDept($deptId);

            // Resolve jabatan_id from name
            if ($jabatan) {
                foreach ($jabatans as $j) {
                    if ($j['nama'] === $jabatan) { $selectedJabatanId = (int)$j['id']; break; }
                }
            }

            // If jabatan has its own competency map, show that — otherwise show dept's
            $jabatanAssignedIds = $selectedJabatanId ? $compModel->getAssignedIdsByJabatan($selectedJabatanId) : [];
            if ($selectedJabatanId && !empty($jabatanAssignedIds)) {
                $groupedTarget        = $compModel->getAllGroupedForJabatan($selectedJabatanId);
                $groupedTargetCluster = $compModel->getAllGroupedByClusterForJabatan($selectedJabatanId);
            } else {
                $groupedTarget        = $compModel->getAllGroupedForDept($deptId);
                $groupedTargetCluster = $compModel->getAllGroupedByClusterForDept($deptId);
            }
        }

        return view('people/competencies/index', [
            'user'             => $this->currentUser(),
            'groupedByCluster' => $compModel->getAllGroupedByCluster(),
            'grouped'          => $compModel->getAllGrouped(),
            'all'              => $compModel->orderBy('kategori')->orderBy('nama')->findAll(),
            'clusters'         => (new CompetencyClusterModel())->orderBy('urutan')->orderBy('nama')->findAll(),
            'departments'      => $departments,
            'deptId'           => $deptId,
            'jabatan'          => $jabatan,
            'jabatans'         => $jabatans,
            'selectedJabatanId'    => $selectedJabatanId,
            'jabatanAssignedCount' => count($jabatanAssignedIds ?? []),
            'targetMap'        => $targetMap,
            'deptTargetMap'    => $deptTargetMap,
            'overrides'        => $overrides,
            'assignedIds'         => $assignedIds,
            'groupedTarget'       => $groupedTarget,
            'groupedTargetCluster'=> $groupedTargetCluster,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $model = new CompetencyModel();

        $newId = $model->insert([
            'nama'       => trim($post['nama']),
            'kategori'   => $post['kategori'],
            'cluster_id' => $post['cluster_id'] !== '' ? (int)$post['cluster_id'] : null,
            'deskripsi'  => trim($post['deskripsi'] ?? '') ?: null,
            'level_1'    => trim($post['level_1'] ?? '') ?: null,
            'level_2'    => trim($post['level_2'] ?? '') ?: null,
            'level_3'    => trim($post['level_3'] ?? '') ?: null,
            'level_4'    => trim($post['level_4'] ?? '') ?: null,
            'level_5'    => trim($post['level_5'] ?? '') ?: null,
        ]);

        ActivityLog::write('create', 'competency', (string)$newId, trim($post['nama']));
        return redirect()->to('/people/competencies')->with('success', 'Kompetensi berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        (new CompetencyModel())->update($id, [
            'nama'       => trim($post['nama']),
            'kategori'   => $post['kategori'],
            'cluster_id' => $post['cluster_id'] !== '' ? (int)$post['cluster_id'] : null,
            'deskripsi'  => trim($post['deskripsi'] ?? '') ?: null,
            'level_1'    => trim($post['level_1'] ?? '') ?: null,
            'level_2'    => trim($post['level_2'] ?? '') ?: null,
            'level_3'    => trim($post['level_3'] ?? '') ?: null,
            'level_4'    => trim($post['level_4'] ?? '') ?: null,
            'level_5'    => trim($post['level_5'] ?? '') ?: null,
        ]);

        ActivityLog::write('update', 'competency', (string)$id, trim($post['nama']));
        return redirect()->to('/people/competencies')->with('success', 'Kompetensi diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $comp = (new CompetencyModel())->find($id);
        $db   = db_connect();
        $db->transStart();
        $db->table('competency_targets')->where('competency_id', $id)->delete();
        // competency_questions cascade-deletes via FK → also cascades tna_assessment_items
        (new CompetencyModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'competency', (string)$id, $comp['nama'] ?? '');
        return redirect()->to('/people/competencies')->with('success', 'Kompetensi dihapus.');
    }

    public function questions(int $id)
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $comp = (new CompetencyModel())->find($id);
        if (! $comp) return redirect()->to('/people/competencies')->with('error', 'Kompetensi tidak ditemukan.');

        return view('people/competencies/questions', [
            'user'      => $this->currentUser(),
            'comp'      => $comp,
            'questions' => (new CompetencyQuestionModel())->getByCompetency($id),
        ]);
    }

    public function storeQuestion(int $compId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $model = new CompetencyQuestionModel();
        $id = $model->insert([
            'competency_id' => $compId,
            'pertanyaan'    => trim($post['pertanyaan']),
            'urutan'        => $model->getNextUrutan($compId),
        ]);
        ActivityLog::write('create', 'competency_question', (string)$id, trim($post['pertanyaan']));
        return redirect()->to('/people/competencies/' . $compId . '/questions')->with('success', 'Pertanyaan ditambahkan.');
    }

    public function updateQuestionLevels(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        (new CompetencyQuestionModel())->update($id, [
            'level_1' => trim($post['level_1'] ?? '') ?: null,
            'level_2' => trim($post['level_2'] ?? '') ?: null,
            'level_3' => trim($post['level_3'] ?? '') ?: null,
            'level_4' => trim($post['level_4'] ?? '') ?: null,
            'level_5' => trim($post['level_5'] ?? '') ?: null,
        ]);
        ActivityLog::write('update', 'competency_question_levels', (string)$id, 'Level descriptions updated');
        return redirect()->back()->with('success', 'Deskripsi level disimpan.');
    }

    public function deleteQuestion(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $q = (new CompetencyQuestionModel())->find($id);
        if ($q) {
            // tna_assessment_items cascade-deletes via FK
            (new CompetencyQuestionModel())->delete($id);
            ActivityLog::write('delete', 'competency_question', (string)$id, substr($q['pertanyaan'], 0, 60));
        }
        return redirect()->back()->with('success', 'Pertanyaan dihapus.');
    }

    public function manageAssignments(int $deptId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $dept = (new DepartmentModel())->find($deptId);
        if (! $dept) return redirect()->to('/people/competencies')->with('error', 'Dept tidak ditemukan.');

        $compModel = new CompetencyModel();
        return view('people/competencies/assign', [
            'user'             => $this->currentUser(),
            'dept'             => $dept,
            'groupedByCluster' => $compModel->getAllGroupedByCluster(),
            'assignedIds'      => $compModel->getAssignedIdsByDept($deptId),
        ]);
    }

    public function saveAssignments(int $deptId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $ids = array_map('intval', $this->request->getPost('competency_ids') ?? []);
        (new CompetencyModel())->saveAssignmentsForDept($deptId, $ids);
        ActivityLog::write('update', 'competency_dept_map', (string)$deptId, count($ids) . ' kompetensi di-assign');
        return redirect()->to('/people/competencies?dept_id=' . $deptId)
            ->with('success', count($ids) . ' kompetensi di-assign ke departemen ini.');
    }

    public function manageJabatanAssignments(int $jabatanId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $jabatan = (new \App\Models\JabatanModel())->find($jabatanId);
        if (! $jabatan) return redirect()->to('/people/competencies')->with('error', 'Jabatan tidak ditemukan.');

        $compModel = new CompetencyModel();
        return view('people/competencies/assign_jabatan', [
            'user'             => $this->currentUser(),
            'jabatan'          => $jabatan,
            'groupedByCluster' => $compModel->getAllGroupedByCluster(),
            'assignedIds'      => $compModel->getAssignedIdsByJabatan($jabatanId),
        ]);
    }

    public function saveJabatanAssignments(int $jabatanId)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $ids = array_map('intval', $this->request->getPost('competency_ids') ?? []);
        (new CompetencyModel())->saveAssignmentsForJabatan($jabatanId, $ids);
        ActivityLog::write('update', 'competency_jabatan_map', (string)$jabatanId, count($ids) . ' kompetensi di-assign');
        return redirect()->to('/people/competencies/jabatan/' . $jabatanId . '/assign')
            ->with('success', count($ids) . ' kompetensi di-assign ke jabatan ini.');
    }

    public function importForm()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        return view('people/competencies/import', ['user' => $this->currentUser()]);
    }

    public function importTemplate()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $rows = [
            ['cluster', 'kategori', 'nama', 'deskripsi', 'pertanyaan'],
            ['Technical & Digital', 'hard', 'AI Prompting', 'Kemampuan menggunakan AI generatif untuk produktivitas', 'Apakah karyawan mampu menyusun prompt yang spesifik dan terstruktur?'],
            ['Technical & Digital', 'hard', 'AI Prompting', 'Kemampuan menggunakan AI generatif untuk produktivitas', 'Apakah karyawan menggunakan AI untuk meningkatkan efisiensi kerja sehari-hari?'],
            ['Technical & Digital', 'hard', 'AI Prompting', 'Kemampuan menggunakan AI generatif untuk produktivitas', 'Apakah karyawan mampu mengevaluasi dan memperbaiki hasil output AI?'],
            ['Communication & Interpersonal', 'soft', 'Komunikasi', 'Kemampuan menyampaikan ide secara efektif', 'Apakah karyawan menyampaikan informasi dengan jelas kepada rekan tim?'],
            ['Communication & Interpersonal', 'soft', 'Komunikasi', 'Kemampuan menyampaikan ide secara efektif', 'Apakah karyawan mendengarkan dan merespons masukan dengan baik?'],
            ['Communication & Interpersonal', 'soft', 'Komunikasi', 'Kemampuan menyampaikan ide secara efektif', 'Apakah karyawan mampu menyesuaikan gaya komunikasi dengan lawan bicara?'],
        ];

        $buf = fopen('php://temp', 'r+');
        fwrite($buf, "\xEF\xBB\xBF"); // UTF-8 BOM agar Excel tidak salah baca
        foreach ($rows as $row) fputcsv($buf, $row);
        rewind($buf);
        $content = stream_get_contents($buf);
        fclose($buf);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="template_competency.csv"')
            ->setHeader('Cache-Control', 'no-store, no-cache')
            ->setBody($content);
    }

    public function importParse()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $file = $this->request->getFile('csv_file');
        if (! $file || ! $file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/people/competencies/import')->with('error', 'Upload file CSV yang valid.');
        }

        $handle = fopen($file->getTempName(), 'r');
        $headers = null;
        $grouped = [];

        while (($row = fgetcsv($handle)) !== false) {
            // Strip UTF-8 BOM from first cell
            if ($headers === null) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                $row     = array_map('trim', $row);
                $headers = array_map('strtolower', $row);
                continue;
            }
            if (count($row) < count($headers)) continue;
            $r = array_combine($headers, array_map('trim', $row));

            $kategori   = strtolower($r['kategori'] ?? '');
            $nama       = $r['nama'] ?? '';
            $pertanyaan = $r['pertanyaan'] ?? '';
            $cluster    = trim($r['cluster'] ?? '');

            if (! in_array($kategori, ['hard', 'soft']) || $nama === '' || $pertanyaan === '') continue;

            $key = $kategori . '|' . $nama;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'kategori'  => $kategori,
                    'nama'      => $nama,
                    'deskripsi' => $r['deskripsi'] ?? '',
                    'cluster'   => $cluster,
                    'questions' => [],
                ];
            }
            $grouped[$key]['questions'][] = $pertanyaan;
        }
        fclose($handle);

        if (empty($grouped)) {
            return redirect()->to('/people/competencies/import')->with('error', 'Tidak ada data valid dalam file. Pastikan kolom kategori (hard/soft), nama, dan pertanyaan terisi.');
        }

        // Check which competencies already exist
        $compModel = new CompetencyModel();
        $data = array_values($grouped);
        foreach ($data as &$item) {
            $existing = $compModel->where('nama', $item['nama'])->where('kategori', $item['kategori'])->first();
            $item['exists']  = $existing !== null;
            $item['comp_id'] = $existing['id'] ?? null;
        }
        unset($item);

        session()->set('ci_import_competency', $data);
        return redirect()->to('/people/competencies/import/preview');
    }

    public function importPreview()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $data = session()->get('ci_import_competency');
        if (empty($data)) return redirect()->to('/people/competencies/import');

        return view('people/competencies/import_preview', [
            'user' => $this->currentUser(),
            'data' => $data,
        ]);
    }

    public function importConfirm()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $data = session()->get('ci_import_competency');
        if (empty($data)) return redirect()->to('/people/competencies/import');

        $compModel = new CompetencyModel();
        $qModel    = new CompetencyQuestionModel();
        $newComps  = 0;
        $newQs     = 0;

        // Build cluster name → id cache
        $clusterModel = new CompetencyClusterModel();
        $clusterCache = [];

        foreach ($data as $item) {
            if ($item['exists'] && $item['comp_id']) {
                $compId = $item['comp_id'];
            } else {
                // Resolve cluster_id by name (auto-create if not exists)
                $clusterId = null;
                $cName = trim($item['cluster'] ?? '');
                if ($cName) {
                    if (! isset($clusterCache[$cName])) {
                        $existing = $clusterModel->where('nama', $cName)->first();
                        if ($existing) {
                            $clusterCache[$cName] = $existing['id'];
                        } else {
                            $clusterCache[$cName] = $clusterModel->insert([
                                'nama'   => $cName,
                                'urutan' => $clusterModel->getNextUrutan(),
                            ]);
                        }
                    }
                    $clusterId = $clusterCache[$cName];
                }

                $compId = $compModel->insert([
                    'nama'       => $item['nama'],
                    'kategori'   => $item['kategori'],
                    'cluster_id' => $clusterId,
                    'deskripsi'  => $item['deskripsi'] ?: null,
                ]);
                $newComps++;
            }

            $urutan = $qModel->getNextUrutan((int)$compId);
            foreach ($item['questions'] as $q) {
                $qModel->insert([
                    'competency_id' => $compId,
                    'pertanyaan'    => $q,
                    'urutan'        => $urutan++,
                ]);
                $newQs++;
            }
        }

        session()->remove('ci_import_competency');
        ActivityLog::write('create', 'competency_import', '0', "Import: {$newComps} kompetensi, {$newQs} pertanyaan");
        return redirect()->to('/people/competencies')
            ->with('success', "Import selesai: {$newComps} kompetensi baru, {$newQs} pertanyaan ditambahkan.");
    }

    public function saveTargets()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post    = $this->request->getPost();
        $deptId  = (int)($post['dept_id'] ?? 0);
        $jabatan = trim($post['jabatan'] ?? '');
        if (! $deptId) return redirect()->to('/people/competencies')->with('error', 'Dept tidak valid.');

        $levels      = $post['levels'] ?? [];
        $targetModel = new CompetencyTargetModel();

        if ($jabatan) {
            $targetModel->saveForDeptJabatan($deptId, $jabatan, $levels);
        } else {
            $targetModel->saveForDept($deptId, $levels);
        }

        $label = $jabatan ? "Jabatan: {$jabatan}" : 'Dept default';
        ActivityLog::write('update', 'competency_targets', (string)$deptId, $label);

        $qs = '?dept_id=' . $deptId . ($jabatan ? '&jabatan=' . urlencode($jabatan) : '');
        return redirect()->to('/people/competencies' . $qs)->with('success', 'Target kompetensi disimpan.');
    }
}
