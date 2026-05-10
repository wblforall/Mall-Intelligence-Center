<?php

namespace App\Controllers;

use App\Models\CompetencyClusterModel;
use App\Libraries\ActivityLog;

class AdminClusters extends BaseController
{
    public function index()
    {
        return view('admin/clusters/index', [
            'user'     => $this->currentUser(),
            'clusters' => (new CompetencyClusterModel())->getAllWithCount(),
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $model = new CompetencyClusterModel();
        $id = $model->insert([
            'nama'      => trim($post['nama']),
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
            'urutan'    => $post['urutan'] ? (int)$post['urutan'] : $model->getNextUrutan(),
        ]);
        ActivityLog::write('create', 'competency_cluster', (string)$id, trim($post['nama']));
        return redirect()->to('/admin/clusters')->with('success', 'Cluster berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post = $this->request->getPost();
        (new CompetencyClusterModel())->update($id, [
            'nama'      => trim($post['nama']),
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
            'urutan'    => (int)($post['urutan'] ?? 0),
        ]);
        ActivityLog::write('update', 'competency_cluster', (string)$id, trim($post['nama']));
        return redirect()->to('/admin/clusters')->with('success', 'Cluster diperbarui.');
    }

    public function delete(int $id)
    {
        $cluster = (new CompetencyClusterModel())->find($id);
        // FK ON DELETE SET NULL — competencies.cluster_id becomes NULL automatically
        (new CompetencyClusterModel())->delete($id);
        ActivityLog::write('delete', 'competency_cluster', (string)$id, $cluster['nama'] ?? '');
        return redirect()->to('/admin/clusters')->with('success', 'Cluster dihapus. Kompetensi terkait tidak ikut terhapus.');
    }
}
