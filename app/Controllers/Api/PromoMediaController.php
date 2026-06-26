<?php

namespace App\Controllers\Api;

use App\Models\PromoMediaUsageModel;
use App\Libraries\ActivityLog;

class PromoMediaController extends BaseApiController
{
    public function approvals()
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_promo_media')) return $this->forbidden();

        $status = $this->request->getGet('status') ?? 'pending';

        $rows = $this->db->table('promo_media_usage u')
            ->select('u.id, u.nama_materi, u.deskripsi_materi, u.dept, u.tanggal_mulai, u.tanggal_selesai, u.status, u.catatan_pemohon, u.sumber, u.is_berbayar, u.submitted_at, s.nama_spot, s.lokasi')
            ->join('promo_media_spots s', 's.id = u.spot_id', 'left')
            ->where('u.status', $status)
            ->orderBy('u.submitted_at', 'DESC')
            ->get()->getResultArray();

        return $this->success($rows);
    }

    public function approve(int $id)
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_promo_media')) return $this->forbidden();

        $body = $this->request->getJSON(true) ?? [];
        $note = trim($body['note'] ?? '');

        $model = new PromoMediaUsageModel();
        $usage = $model->find($id);
        if (! $usage) return $this->error('Data tidak ditemukan.', 404);
        if ($usage['status'] !== 'pending') return $this->error('Hanya pengajuan berstatus pending yang bisa disetujui.');

        $model->update($id, [
            'status'            => 'approved',
            'catatan_approver'  => $note,
            'approved_by'       => $this->apiUser['id'],
            'approved_at'       => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('approve', 'promo_media_usage', (string)$id, 'Approved via mobile by ' . $this->apiUser['name']);

        return $this->success(null, 'Pengajuan disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_promo_media')) return $this->forbidden();

        $body   = $this->request->getJSON(true) ?? [];
        $reason = trim($body['note'] ?? '');

        if (! $reason) return $this->error('Alasan penolakan wajib diisi.');

        $model = new PromoMediaUsageModel();
        $usage = $model->find($id);
        if (! $usage) return $this->error('Data tidak ditemukan.', 404);
        if ($usage['status'] !== 'pending') return $this->error('Hanya pengajuan berstatus pending yang bisa ditolak.');

        $model->update($id, [
            'status'            => 'rejected',
            'catatan_approver'  => $reason,
            'rejection_reason'  => $reason,
            'approved_by'       => $this->apiUser['id'],
            'approved_at'       => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('reject', 'promo_media_usage', (string)$id, 'Rejected via mobile by ' . $this->apiUser['name']);

        return $this->success(null, 'Pengajuan ditolak.');
    }
}
