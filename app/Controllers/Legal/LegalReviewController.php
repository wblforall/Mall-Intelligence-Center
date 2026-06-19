<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalReviewModel;
use App\Models\LegalReviewVersionModel;
use App\Models\LegalReviewCommentModel;
use App\Models\UserModel;
use App\Libraries\ActivityLog;

class LegalReviewController extends BaseController
{
    private LegalReviewModel        $model;
    private LegalReviewVersionModel $vModel;
    private LegalReviewCommentModel $cModel;

    public function __construct()
    {
        $this->model  = new LegalReviewModel();
        $this->vModel = new LegalReviewVersionModel();
        $this->cModel = new LegalReviewCommentModel();
    }

    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $userId = session()->get('user_id');
        $tab    = $this->request->getGet('tab') ?? 'all';

        $f = ['q' => $this->request->getGet('q')];
        if ($tab === 'mine')        $f['created_by']     = $userId;
        if ($tab === 'assigned')    $f['assigned_to']    = $userId;
        if ($tab === 'action')      $f['needs_action_by']= $userId;

        return view('legal/reviews/index', [
            'title'   => 'Review Kontrak',
            'reviews' => $this->model->getFiltered($f),
            'tab'     => $tab,
            'canEdit' => $this->canEditMenu('legal'),
        ]);
    }

    public function new()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/reviews')->with('error', 'Akses ditolak.');
        $users = (new UserModel())->findAll();
        return view('legal/reviews/form', ['title' => 'Buat Review Kontrak', 'review' => null, 'users' => $users]);
    }

    public function create()
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/reviews')->with('error', 'Akses ditolak.');

        $userId = session()->get('user_id');
        $data   = $this->request->getPost(['judul','deskripsi','entity_type','entity_id']);
        $data['entity_id']  = $data['entity_id'] ?: null;
        $data['created_by'] = $userId;
        $data['status']     = 'draft';

        $file = $this->request->getFile('file_dokumen');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->withInput()->with('error', 'Dokumen draft wajib diupload.');
        }

        $id = $this->model->insert($data);

        // Upload versi pertama
        $this->saveVersion($id, $file, $this->request->getPost('catatan_perubahan'), $userId);

        // Assign reviewers
        $reviewerIds = $this->request->getPost('reviewer_ids') ?? [];
        $this->saveAssignees($id, (array)$reviewerIds, $userId);

        ActivityLog::write('legal_review', 'create', $id, $data['judul']);
        return redirect()->to('/legal/reviews/' . $id)->with('success', 'Review kontrak berhasil dibuat.');
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $review = $this->model->getWithDetails($id);
        if (! $review) return redirect()->to('/legal/reviews')->with('error', 'Review tidak ditemukan.');

        $userId    = session()->get('user_id');
        $isCreator = $review['created_by'] == $userId;
        $isReviewer= in_array($userId, array_column($review['assignees'], 'user_id'));
        $latest    = $this->vModel->getLatest($id);
        $thread    = $this->cModel->getThread($id);

        return view('legal/reviews/show', [
            'title'      => $review['judul'],
            'review'     => $review,
            'latest'     => $latest,
            'thread'     => $thread,
            'isCreator'  => $isCreator,
            'isReviewer' => $isReviewer,
            'canEdit'    => $this->canEditMenu('legal'),
            'canApprove' => $this->canApproveLegal(),
            'users'      => (new UserModel())->findAll(),
        ]);
    }

    public function edit(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/reviews')->with('error', 'Akses ditolak.');
        $review = $this->model->find($id);
        if (! $review) return redirect()->to('/legal/reviews')->with('error', 'Review tidak ditemukan.');
        return view('legal/reviews/form', [
            'title'  => 'Edit Review',
            'review' => $review,
            'users'  => (new UserModel())->findAll(),
        ]);
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/reviews')->with('error', 'Akses ditolak.');
        $data = $this->request->getPost(['judul','deskripsi','entity_type','entity_id','ext_party_name']);
        $data['entity_id'] = $data['entity_id'] ?: null;

        $reviewerIds = $this->request->getPost('reviewer_ids') ?? [];
        $db = \Config\Database::connect();
        $db->table('legal_review_assignees')->where('review_id', $id)->delete();
        $this->saveAssignees($id, (array)$reviewerIds, session()->get('user_id'));

        $this->model->update($id, $data);
        ActivityLog::write('legal_review', 'update', $id, $data['judul']);
        return redirect()->to('/legal/reviews/' . $id)->with('success', 'Review diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->to('/legal/reviews')->with('error', 'Akses ditolak.');
        $review = $this->model->find($id);
        if (! $review) return redirect()->to('/legal/reviews')->with('error', 'Review tidak ditemukan.');

        foreach ($this->vModel->where('review_id', $id)->findAll() as $v) {
            $this->vModel->deleteWithFile($v['id']);
        }
        $db = \Config\Database::connect();
        $db->table('legal_review_assignees')->where('review_id', $id)->delete();
        $db->table('legal_review_comments')->where('review_id', $id)->delete();
        $this->model->delete($id);

        ActivityLog::write('legal_review', 'delete', $id, $review['judul']);
        return redirect()->to('/legal/reviews')->with('success', 'Review dihapus.');
    }

    public function uploadVersion(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');

        $review = $this->model->find($id);
        if (! $review || ! in_array($review['status'], ['draft', 'revision'])) {
            return redirect()->back()->with('error', 'Tidak bisa upload versi baru saat ini.');
        }

        $file = $this->request->getFile('file_dokumen');
        if (! $file || ! $file->isValid()) return redirect()->back()->with('error', 'File tidak valid.');

        $userId = session()->get('user_id');
        $this->saveVersion($id, $file, $this->request->getPost('catatan_perubahan'), $userId);
        $this->model->update($id, ['status' => 'in_review']);

        ActivityLog::write('legal_review', 'upload_version', $id, $review['judul']);
        return redirect()->back()->with('success', 'Versi baru diupload. Status berubah ke In Review.');
    }

    public function addComment(int $id)
    {
        $review = $this->model->find($id);
        if (! $review) return redirect()->back()->with('error', 'Review tidak ditemukan.');

        $userId   = session()->get('user_id');
        $komentar = trim($this->request->getPost('komentar'));
        $parentId = $this->request->getPost('parent_id') ?: null;
        $latest   = $this->vModel->getLatest($id);

        if (! $komentar) return redirect()->back()->with('error', 'Komentar tidak boleh kosong.');

        $this->cModel->insert([
            'review_id'  => $id,
            'version_id' => $latest['id'] ?? null,
            'parent_id'  => $parentId,
            'user_id'    => $userId,
            'komentar'   => $komentar,
            'tipe'       => 'comment',
        ]);

        $this->model->update($id, ['updated_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('legal_review', 'comment', $id, $review['judul']);
        return redirect()->back()->with('success', 'Komentar ditambahkan.');
    }

    public function requestRevision(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');

        $review  = $this->model->find($id);
        $userId  = session()->get('user_id');
        $catatan = trim($this->request->getPost('catatan'));
        $latest  = $this->vModel->getLatest($id);

        $this->cModel->insert([
            'review_id'  => $id,
            'version_id' => $latest['id'] ?? null,
            'user_id'    => $userId,
            'komentar'   => $catatan ?: 'Meminta revisi dokumen.',
            'tipe'       => 'request_revision',
        ]);
        $this->model->update($id, ['status' => 'revision']);
        ActivityLog::write('legal_review', 'request_revision', $id, $review['judul']);
        return redirect()->back()->with('success', 'Permintaan revisi dikirim.');
    }

    public function markFinal(int $id)
    {
        if (! $this->canApproveLegal()) return redirect()->back()->with('error', 'Akses ditolak. Butuh izin Approve Legal.');

        $review = $this->model->find($id);
        $userId = session()->get('user_id');
        $latest = $this->vModel->getLatest($id);

        $this->cModel->insert([
            'review_id'  => $id,
            'version_id' => $latest['id'] ?? null,
            'user_id'    => $userId,
            'komentar'   => $this->request->getPost('catatan') ?: 'Dokumen disetujui / difinalisasi.',
            'tipe'       => 'mark_final',
        ]);
        $this->model->update($id, ['status' => 'final']);
        ActivityLog::write('legal_review', 'mark_final', $id, $review['judul']);
        return redirect()->back()->with('success', 'Dokumen ditandai Final.');
    }

    public function markSigned(int $id)
    {
        if (! $this->canApproveLegal()) return redirect()->back()->with('error', 'Akses ditolak. Butuh izin Approve Legal.');
        $review = $this->model->find($id);
        if ($review['status'] !== 'final') return redirect()->back()->with('error', 'Hanya dokumen berstatus Final yang bisa ditandai Signed.');
        $this->model->update($id, ['status' => 'signed']);

        // Nonaktifkan ext link otomatis
        if ($review['ext_link_active']) {
            $this->model->update($id, ['ext_link_active' => 0]);
        }
        ActivityLog::write('legal_review', 'mark_signed', $id, $review['judul']);
        return redirect()->back()->with('success', 'Kontrak ditandai Signed. Link eksternal dinonaktifkan.');
    }

    public function generateLink(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');
        $extPartyName = trim($this->request->getPost('ext_party_name'));
        $this->model->update($id, ['ext_party_name' => $extPartyName ?: null]);
        $token = $this->model->generateExtToken($id);
        ActivityLog::write('legal_review', 'generate_link', $id, 'Token generated');
        return redirect()->back()->with('success', 'Link eksternal dibuat: ' . base_url('legal/ext/' . $token));
    }

    public function toggleLink(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');
        $review = $this->model->find($id);
        $newVal = $review['ext_link_active'] ? 0 : 1;
        $this->model->update($id, ['ext_link_active' => $newVal]);
        ActivityLog::write('legal_review', $newVal ? 'enable_link' : 'disable_link', $id, $review['judul']);
        return redirect()->back()->with('success', $newVal ? 'Link diaktifkan.' : 'Link dinonaktifkan.');
    }

    public function archive(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');
        $review = $this->model->find($id);
        if (! in_array($review['status'], ['final', 'signed'])) {
            return redirect()->back()->with('error', 'Hanya review berstatus Final atau Signed yang bisa diarsipkan.');
        }
        return view('legal/reviews/archive_form', [
            'title'  => 'Arsipkan ke Legal',
            'review' => $review,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function saveVersion(int $reviewId, $file, ?string $catatan, int $userId): void
    {
        $versi    = $this->vModel->getNextVersi($reviewId);
        $ext      = $file->getClientExtension();
        $filename = 'review_' . $reviewId . '_v' . $versi . '_' . time() . '.' . $ext;
        $destDir  = FCPATH . 'uploads/legal_reviews/';
        if (! is_dir($destDir)) mkdir($destDir, 0755, true);
        $file->move($destDir, $filename);

        $this->vModel->insert([
            'review_id'         => $reviewId,
            'versi_ke'          => $versi,
            'file_path'         => 'uploads/legal_reviews/' . $filename,
            'file_size'         => $file->getSize(),
            'catatan_perubahan' => $catatan ?: null,
            'uploaded_by'       => $userId,
            'uploaded_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    private function saveAssignees(int $reviewId, array $userIds, int $assignedBy): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        foreach ($userIds as $uid) {
            $uid = (int)$uid;
            if (! $uid) continue;
            $db->table('legal_review_assignees')->ignore(true)->insert([
                'review_id'   => $reviewId,
                'user_id'     => $uid,
                'assigned_by' => $assignedBy,
                'assigned_at' => $now,
            ]);
        }
    }

    // Serve file dokumen review legal lewat auth (tidak boleh diakses publik langsung).
    public function viewFile(string $name)
    {
        if (! $this->canViewMenu('legal')) return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        $name = basename($name);
        $path = FCPATH . 'uploads/legal_reviews/' . $name;
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$ext] ?? 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
