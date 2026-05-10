<?php

namespace App\Controllers;

use App\Models\TrainingProgramModel;
use App\Models\TrainingParticipantModel;
use App\Models\TrainingCompetencyModel;
use App\Models\TrainingBudgetModel;
use App\Models\DepartmentModel;
use App\Models\EmployeeModel;
use App\Models\CompetencyModel;
use App\Libraries\ActivityLog;

class PeopleTraining extends BaseController
{
    public function index()
    {
        $tahun  = $this->request->getGet('tahun') ? (int)$this->request->getGet('tahun') : null;
        $status = $this->request->getGet('status') ?: null;

        $programs = (new TrainingProgramModel())->getAllWithStats($tahun, $status);

        $years = db_connect()->query(
            "SELECT DISTINCT YEAR(tanggal_mulai) AS y FROM training_programs WHERE tanggal_mulai IS NOT NULL ORDER BY y DESC"
        )->getResultArray();

        return view('people/training/index', [
            'user'         => $this->currentUser(),
            'programs'     => $programs,
            'years'        => array_column($years, 'y'),
            'filterTahun'  => $tahun,
            'filterStatus' => $status,
            'competencies' => (new CompetencyModel())->getAllGrouped(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $id   = (new TrainingProgramModel())->insert([
            'nama'              => trim($post['nama']),
            'tipe'              => $post['tipe'],
            'vendor'            => trim($post['vendor'] ?? '') ?: null,
            'tanggal_mulai'     => $post['tanggal_mulai'] ?: null,
            'tanggal_selesai'   => $post['tanggal_selesai'] ?: null,
            'lokasi'            => trim($post['lokasi'] ?? '') ?: null,
            'biaya_per_peserta' => $post['biaya_per_peserta'] !== '' ? (float)str_replace(',', '', $post['biaya_per_peserta']) : null,
            'kuota'             => $post['kuota'] !== '' ? (int)$post['kuota'] : null,
            'status'            => $post['status'],
            'deskripsi'         => trim($post['deskripsi'] ?? '') ?: null,
            'catatan'           => trim($post['catatan'] ?? '') ?: null,
        ]);

        if (! empty($post['competency_ids'])) {
            (new TrainingCompetencyModel())->saveForProgram($id, $post['competency_ids']);
        }

        ActivityLog::write('create', 'training_program', (string)$id, trim($post['nama']));
        return redirect()->to('/people/training/' . $id)->with('success', 'Program training berhasil dibuat.');
    }

    public function show(int $id)
    {
        $program = (new TrainingProgramModel())->find($id);
        if (! $program) return redirect()->to('/people/training')->with('error', 'Program tidak ditemukan.');

        $participants = (new TrainingParticipantModel())->getByProgram($id);
        $compIds      = (new TrainingCompetencyModel())->getCompetencyIdsByProgram($id);
        $allComps     = (new CompetencyModel())->getAllGrouped();

        // Linked competency names
        $linkedComps = [];
        foreach (['hard', 'soft'] as $cat) {
            foreach ($allComps[$cat] as $c) {
                if (in_array($c['id'], $compIds)) $linkedComps[] = $c;
            }
        }

        // Available employees not yet in program
        $enrolledIds     = array_column($participants, 'employee_id');
        $allEmployees    = (new EmployeeModel())->where('status', 'aktif')->orderBy('nama')->findAll();
        $availableEmps   = array_values(array_filter($allEmployees, fn($e) => !in_array($e['id'], $enrolledIds)));

        // Stats
        $hadir      = count(array_filter($participants, fn($p) => $p['status_kehadiran'] === 'hadir'));
        $tdkHadir   = count(array_filter($participants, fn($p) => $p['status_kehadiran'] === 'tidak_hadir'));
        $withPost   = array_filter($participants, fn($p) => $p['post_test'] !== null);
        $withPre    = array_filter($participants, fn($p) => $p['pre_test'] !== null);
        $avgPost    = count($withPost) ? round(array_sum(array_column($withPost, 'post_test')) / count($withPost), 1) : null;
        $avgPre     = count($withPre)  ? round(array_sum(array_column($withPre, 'pre_test'))  / count($withPre), 1)  : null;

        return view('people/training/show', [
            'user'          => $this->currentUser(),
            'program'       => $program,
            'participants'  => $participants,
            'linkedComps'   => $linkedComps,
            'allComps'      => $allComps,
            'compIds'       => $compIds,
            'availableEmps' => $availableEmps,
            'hadir'         => $hadir,
            'tdkHadir'      => $tdkHadir,
            'avgPost'       => $avgPost,
            'avgPre'        => $avgPre,
        ]);
    }

    public function update(int $id)
    {
        $post = $this->request->getPost();
        (new TrainingProgramModel())->update($id, [
            'nama'              => trim($post['nama']),
            'tipe'              => $post['tipe'],
            'vendor'            => trim($post['vendor'] ?? '') ?: null,
            'tanggal_mulai'     => $post['tanggal_mulai'] ?: null,
            'tanggal_selesai'   => $post['tanggal_selesai'] ?: null,
            'lokasi'            => trim($post['lokasi'] ?? '') ?: null,
            'biaya_per_peserta' => $post['biaya_per_peserta'] !== '' ? (float)str_replace(',', '', $post['biaya_per_peserta']) : null,
            'kuota'             => $post['kuota'] !== '' ? (int)$post['kuota'] : null,
            'status'            => $post['status'],
            'deskripsi'         => trim($post['deskripsi'] ?? '') ?: null,
            'catatan'           => trim($post['catatan'] ?? '') ?: null,
        ]);

        (new TrainingCompetencyModel())->saveForProgram($id, $post['competency_ids'] ?? []);

        ActivityLog::write('update', 'training_program', (string)$id, trim($post['nama']));
        return redirect()->to('/people/training/' . $id)->with('success', 'Program training diperbarui.');
    }

    public function delete(int $id)
    {
        $program = (new TrainingProgramModel())->find($id);
        $db      = db_connect();

        $db->transStart();
        $db->table('training_competencies')->where('program_id', $id)->delete();
        $db->table('training_participants')->where('program_id', $id)->delete();
        (new TrainingProgramModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'training_program', (string)$id, $program['nama'] ?? '');
        return redirect()->to('/people/training')->with('success', 'Program training dihapus.');
    }

    public function addParticipant(int $id)
    {
        $empId = (int)$this->request->getPost('employee_id');

        $existing = (new TrainingParticipantModel())
            ->where('program_id', $id)->where('employee_id', $empId)->first();
        if ($existing) {
            return redirect()->to('/people/training/' . $id)->with('error', 'Karyawan sudah terdaftar.');
        }

        (new TrainingParticipantModel())->insert([
            'program_id'       => $id,
            'employee_id'      => $empId,
            'status_kehadiran' => 'registered',
        ]);

        $emp = (new EmployeeModel())->find($empId);
        ActivityLog::write('create', 'training_participant', (string)$id, $emp['nama'] ?? '');
        return redirect()->to('/people/training/' . $id)->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function removeParticipant(int $id, int $participantId)
    {
        (new TrainingParticipantModel())->delete($participantId);
        ActivityLog::write('delete', 'training_participant', (string)$participantId, 'program:' . $id);
        return redirect()->to('/people/training/' . $id)->with('success', 'Peserta dihapus.');
    }

    public function updateParticipant(int $id, int $participantId)
    {
        $post = $this->request->getPost();
        (new TrainingParticipantModel())->update($participantId, [
            'status_kehadiran' => $post['status_kehadiran'],
            'pre_test'         => $post['pre_test']  !== '' ? (float)$post['pre_test']  : null,
            'post_test'        => $post['post_test'] !== '' ? (float)$post['post_test'] : null,
            'catatan'          => trim($post['catatan'] ?? '') ?: null,
        ]);
        ActivityLog::write('update', 'training_participant', (string)$participantId, 'update kehadiran/nilai');
        return redirect()->to('/people/training/' . $id)->with('success', 'Data peserta diperbarui.');
    }

    public function budget()
    {
        $tahun       = $this->request->getGet('tahun') ? (int)$this->request->getGet('tahun') : (int)date('Y');
        $budgetModel = new TrainingBudgetModel();
        $progModel   = new TrainingProgramModel();

        $departments = (new DepartmentModel())->orderBy('name')->findAll();
        $budgetMap   = $budgetModel->getMapByYear($tahun);
        $realisasiMap = $progModel->getRealisasiByDeptYear($tahun);
        $years       = $budgetModel->getAvailableYears();

        return view('people/training/budget', [
            'user'        => $this->currentUser(),
            'tahun'       => $tahun,
            'years'       => $years,
            'departments' => $departments,
            'budgetMap'   => $budgetMap,
            'realisasiMap'=> $realisasiMap,
        ]);
    }

    public function saveBudget()
    {
        $post        = $this->request->getPost();
        $tahun       = (int)$post['tahun'];
        $budgets     = $post['budgets'] ?? [];
        $budgetModel = new TrainingBudgetModel();

        foreach ($budgets as $deptId => $data) {
            $anggaran = trim($data['anggaran'] ?? '');
            if ($anggaran === '') continue;
            $anggaran = (float)str_replace(['.', ','], ['', '.'], $anggaran);
            if ($anggaran < 0) continue;
            $budgetModel->saveBudget(
                (int)$deptId,
                $tahun,
                $anggaran,
                trim($data['catatan'] ?? '') ?: null
            );
        }

        ActivityLog::write('update', 'training_budget', (string)$tahun, 'Budget Training ' . $tahun);
        return redirect()->to('/people/training/budget?tahun=' . $tahun)->with('success', 'Budget training disimpan.');
    }

    public function budgetDetail(int $deptId)
    {
        $tahun    = $this->request->getGet('tahun') ? (int)$this->request->getGet('tahun') : (int)date('Y');
        $dept     = (new DepartmentModel())->find($deptId);
        $programs = (new TrainingProgramModel())->getProgramsByDeptYear($deptId, $tahun);
        $budget   = (new TrainingBudgetModel())->where('dept_id', $deptId)->where('tahun', $tahun)->first();

        return $this->response->setJSON([
            'dept_name' => $dept['name'] ?? '',
            'anggaran'  => $budget ? $budget['anggaran'] : null,
            'programs'  => $programs,
        ]);
    }
}
