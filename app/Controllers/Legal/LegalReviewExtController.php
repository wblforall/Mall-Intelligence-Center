<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalReviewModel;
use App\Models\LegalReviewVersionModel;
use App\Models\LegalReviewCommentModel;

class LegalReviewExtController extends BaseController
{
    public function show(string $token)
    {
        $model  = new LegalReviewModel();
        $review = $model->findByToken($token);

        if (! $review) {
            return view('legal/ext/inactive', ['title' => 'Link Tidak Aktif']);
        }

        $latest = (new LegalReviewVersionModel())->getLatest($review['id']);
        $thread = (new LegalReviewCommentModel())->getThread($review['id']);

        return view('legal/ext/show', [
            'title'  => 'Review Dokumen: ' . $review['judul'],
            'review' => $review,
            'latest' => $latest,
            'thread' => $thread,
            'token'  => $token,
        ]);
    }

    public function comment(string $token)
    {
        $model  = new LegalReviewModel();
        $review = $model->findByToken($token);

        if (! $review) return redirect()->to('/legal/ext/' . $token);

        $name     = trim($this->request->getPost('ext_name'));
        $komentar = trim($this->request->getPost('komentar'));
        $parentId = $this->request->getPost('parent_id') ?: null;

        if (! $name || ! $komentar) {
            return redirect()->to('/legal/ext/' . $token)->with('error', 'Nama dan komentar wajib diisi.');
        }

        $latest = (new LegalReviewVersionModel())->getLatest($review['id']);

        (new LegalReviewCommentModel())->insert([
            'review_id'  => $review['id'],
            'version_id' => $latest['id'] ?? null,
            'parent_id'  => $parentId,
            'user_id'    => null,
            'ext_name'   => $name,
            'komentar'   => $komentar,
            'tipe'       => 'comment',
        ]);

        $model->update($review['id'], ['updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/legal/ext/' . $token)->with('success', 'Komentar berhasil dikirim.');
    }
}
