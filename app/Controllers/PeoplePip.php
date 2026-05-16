<?php

namespace App\Controllers;

use App\Models\PipPlanModel;
use App\Models\PipItemModel;
use App\Models\PipReviewModel;
use App\Models\PipAspekMasterModel;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use App\Libraries\ActivityLog;

class PeoplePip extends BaseController
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
            'dept_id'     => $this->request->getGet('dept_id') ? (int)$this->request->getGet('dept_id') : '',
            'employee_id' => $this->request->getGet('employee_id') ? (int)$this->request->getGet('employee_id') : '',
        ];

        $plans = (new PipPlanModel())->getAllWithEmployee($filters);

        $today = date('Y-m-d');
        $reviewAlerts = array_filter($plans, function($p) use ($today) {
            if (! in_array($p['status'], ['aktif', 'diperpanjang'])) return false;
            $next = PipPlanModel::nextReviewDate($p);
            return $next <= date('Y-m-d', strtotime('+2 days'));
        });

        return view('people/pip/index', [
            'user'         => $this->currentUser(),
            'plans'        => $plans,
            'reviewAlerts' => array_values($reviewAlerts),
            'stats'        => (new PipPlanModel())->getDashboardStats(),
            'employees'    => (new EmployeeModel())->getWithDept(),
            'departments'  => (new DepartmentModel())->findAll(),
            'aspekMaster'  => (new PipAspekMasterModel())->getAktif(),
            'filters'      => $filters,
            'canEdit'      => $this->canEditMenu('people_dev'),
        ]);
    }

    public function store()
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post   = $this->request->getPost();
        $planId = (new PipPlanModel())->insert([
            'employee_id'          => (int)$post['employee_id'],
            'created_by_user_id'   => session()->get('user_id'),
            'judul'                => trim($post['judul']),
            'alasan'               => trim($post['alasan'] ?? '') ?: null,
            'level_sp'             => $post['level_sp'] ?? 'none',
            'dukungan'             => trim($post['dukungan'] ?? '') ?: null,
            'konsekuensi'          => trim($post['konsekuensi'] ?? '') ?: null,
            'persetujuan_atasan'   => 'pending',
            'persetujuan_karyawan' => 'pending',
            'tanggal_mulai'        => $post['tanggal_mulai'],
            'tanggal_selesai'      => $post['tanggal_selesai'],
            'frekuensi_review'     => $post['frekuensi_review'] ?? 'mingguan',
            'status'               => 'menunggu_persetujuan',
        ]);

        $this->saveItems((int)$planId, $post);
        ActivityLog::write('create', 'pip_plan', (string)$planId, trim($post['judul']));
        return redirect()->to('/people/pip/' . $planId)->with('success', 'PIP berhasil dibuat.');
    }

    public function show(int $id)
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new PipPlanModel())->getWithEmployee($id);
        if (! $plan) return redirect()->to('/people/pip')->with('error', 'PIP tidak ditemukan.');

        $reviews = (new PipReviewModel())->getByPip($id);
        $lastReviewDate = ! empty($reviews) ? $reviews[0]['tanggal_review'] : null;
        $plan['last_review_date'] = $lastReviewDate;

        return view('people/pip/detail', [
            'user'        => $this->currentUser(),
            'plan'        => $plan,
            'items'       => (new PipItemModel())->getByPip($id),
            'reviews'     => $reviews,
            'aspekMaster' => (new PipAspekMasterModel())->getAktif(),
            'canEdit'     => $this->canEditMenu('people_dev'),
        ]);
    }

    public function update(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new PipPlanModel())->update($id, [
            'judul'                => trim($post['judul']),
            'alasan'               => trim($post['alasan'] ?? '') ?: null,
            'level_sp'             => $post['level_sp'] ?? 'none',
            'dukungan'             => trim($post['dukungan'] ?? '') ?: null,
            'konsekuensi'          => trim($post['konsekuensi'] ?? '') ?: null,
            'persetujuan_atasan'        => $post['persetujuan_atasan'] ?? 'pending',
            'catatan_penolakan_atasan'  => trim($post['catatan_penolakan_atasan'] ?? '') ?: null,
            'persetujuan_karyawan'      => $post['persetujuan_karyawan'] ?? 'pending',
            'catatan_penolakan'         => trim($post['catatan_penolakan'] ?? '') ?: null,
            'tanggal_mulai'        => $post['tanggal_mulai'],
            'tanggal_selesai'      => $post['tanggal_selesai'],
            'frekuensi_review'     => $post['frekuensi_review'] ?? 'mingguan',
            'status'               => $post['status'],
            'catatan_penutup'      => trim($post['catatan_penutup'] ?? '') ?: null,
        ]);

        $db = db_connect();
        $db->table('pip_items')->where('pip_id', $id)->delete();
        $this->saveItems($id, $post);

        ActivityLog::write('update', 'pip_plan', (string)$id, trim($post['judul']));
        return redirect()->to('/people/pip/' . $id)->with('success', 'PIP diperbarui.');
    }

    public function approve(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new PipPlanModel())->find($id);
        if (! $plan || $plan['status'] !== 'menunggu_persetujuan') {
            return redirect()->to('/people/pip/' . $id)->with('error', 'PIP tidak dalam status menunggu persetujuan.');
        }

        (new PipPlanModel())->update($id, [
            'status'               => 'aktif',
            'approved_by_user_id'  => session()->get('user_id'),
        ]);
        ActivityLog::write('update', 'pip_plan', (string)$id, 'approve: ' . $plan['judul']);
        return redirect()->to('/people/pip/' . $id)->with('success', 'PIP disetujui dan sekarang aktif.');
    }

    public function delete(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new PipPlanModel())->find($id);
        $db   = db_connect();

        $db->transStart();
        $db->table('pip_reviews')->where('pip_id', $id)->delete();
        $db->table('pip_items')->where('pip_id', $id)->delete();
        (new PipPlanModel())->delete($id);
        $db->transComplete();

        ActivityLog::write('delete', 'pip_plan', (string)$id, $plan['judul'] ?? '');
        return redirect()->to('/people/pip')->with('success', 'PIP dihapus.');
    }

    public function storeReview(int $id)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new PipReviewModel())->insert([
            'pip_id'         => $id,
            'tanggal_review' => $post['tanggal_review'],
            'reviewer_name'  => trim($post['reviewer_name']),
            'progres'        => $post['progres'],
            'catatan'        => trim($post['catatan'] ?? '') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('create', 'pip_review', (string)$id, trim($post['reviewer_name']));
        return redirect()->to('/people/pip/' . $id)->with('success', 'Review berhasil ditambahkan.');
    }

    public function deleteReview(int $id, int $reviewId)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        (new PipReviewModel())->delete($reviewId);
        ActivityLog::write('delete', 'pip_review', (string)$reviewId, 'pip_id:' . $id);
        return redirect()->to('/people/pip/' . $id)->with('success', 'Review dihapus.');
    }

    public function printPip(int $id)
    {
        if (! $this->guard()) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $plan = (new PipPlanModel())->getWithEmployee($id);
        if (! $plan) return redirect()->to('/people/pip')->with('error', 'PIP tidak ditemukan.');

        return view('people/pip/print', [
            'plan'    => $plan,
            'items'   => (new PipItemModel())->getByPip($id),
            'reviews' => (new PipReviewModel())->getByPip($id),
        ]);
    }

    public function generateToken(int $id, string $pihak)
    {
        if (! $this->guard(true)) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        if (! in_array($pihak, ['atasan','karyawan'])) return redirect()->to('/people/pip/' . $id);

        $field = 'token_' . $pihak;
        $token = bin2hex(random_bytes(32));
        (new PipPlanModel())->update($id, [$field => $token]);

        ActivityLog::write('update', 'pip_plan', (string)$id, 'generate token ' . $pihak);
        return redirect()->to('/people/pip/' . $id)->with('success', 'Link ' . $pihak . ' berhasil dibuat.');
    }

    private function saveItems(int $pipId, array $post): void
    {
        $aspeks   = $post['aspek']    ?? [];
        $masalahs = $post['masalah']  ?? [];
        $targets  = $post['target']   ?? [];
        $metriks  = $post['metrik']   ?? [];
        $deadlines= $post['deadline'] ?? [];

        $model = new PipItemModel();
        foreach ($aspeks as $i => $aspek) {
            $aspek = trim($aspek);
            if ($aspek === '') continue;
            $model->insert([
                'pip_id'   => $pipId,
                'aspek'    => $aspek,
                'masalah'  => trim($masalahs[$i] ?? '') ?: null,
                'target'   => trim($targets[$i]  ?? '') ?: null,
                'metrik'   => trim($metriks[$i]  ?? '') ?: null,
                'deadline' => $deadlines[$i] ?? null ?: null,
            ]);
        }
    }
}
