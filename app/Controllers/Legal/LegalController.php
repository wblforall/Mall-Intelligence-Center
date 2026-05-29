<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalLeaseModel;
use App\Models\LegalPermitModel;
use App\Models\LegalContractModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalController extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $leaseM    = new LegalLeaseModel();
        $permitM   = new LegalPermitModel();
        $contractM = new LegalContractModel();

        // Summary counts
        $summary = [
            'leases' => [
                'active'   => $leaseM->where('status', 'active')->countAllResults(),
                'expiring' => $leaseM->getExpiringCount(30),
            ],
            'permits' => [
                'active'   => $permitM->where('status', 'active')->countAllResults(),
                'expiring' => $permitM->getExpiringCount(30),
            ],
            'contracts' => [
                'active'   => $contractM->where('status', 'active')->countAllResults(),
                'expiring' => $contractM->getExpiringCount(30),
            ],
        ];

        // Unified expiring table (≤30 days from all 3 entities)
        $expiring = $this->getExpiringAll(30);

        return view('legal/index', [
            'title'    => 'Legal',
            'summary'  => $summary,
            'expiring' => $expiring,
        ]);
    }

    private function getExpiringAll(int $days): array
    {
        $cutoff = date('Y-m-d', strtotime("+{$days} days"));
        $today  = date('Y-m-d');
        $rows   = [];

        $db = \Config\Database::connect();

        $leases = $db->table('legal_leases')
            ->select("id, 'lease' as entity_type, CONCAT('Sewa: ', tenant_name) as nama, nomor_kontrak as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $permits = $db->table('legal_permits')
            ->select("id, 'permit' as entity_type, CONCAT('Izin: ', nama_izin) as nama, nomor_izin as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir IS NOT NULL')->where('tanggal_berakhir <=', $cutoff)
            ->where('tanggal_berakhir >=', $today)->where('status', 'active')->get()->getResultArray();

        $contracts = $db->table('legal_contracts')
            ->select("id, 'contract' as entity_type, CONCAT('Vendor: ', nama_vendor) as nama, nomor_kontrak as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $rows = array_merge($leases, $permits, $contracts);
        usort($rows, fn($a, $b) => strcmp($a['tanggal_berakhir'], $b['tanggal_berakhir']));
        return $rows;
    }

    // ── Shared document actions ───────────────────────────────────────────

    public function uploadDocument()
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');

        $type     = $this->request->getPost('entity_type');
        $entityId = (int) $this->request->getPost('entity_id');
        $name     = trim($this->request->getPost('nama_dokumen'));
        $file     = $this->request->getFile('file_dokumen');

        if (! in_array($type, ['lease', 'permit', 'contract']) || ! $entityId) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $ext      = $file->getClientExtension();
        $filename = $type . '_' . $entityId . '_' . time() . '.' . $ext;
        $destDir  = FCPATH . 'uploads/legal/';
        if (! is_dir($destDir)) mkdir($destDir, 0755, true);
        $file->move($destDir, $filename);

        (new LegalDocumentModel())->insert([
            'entity_type'  => $type,
            'entity_id'    => $entityId,
            'nama_dokumen' => $name ?: $file->getClientName(),
            'file_path'    => 'uploads/legal/' . $filename,
            'file_size'    => $file->getSize(),
            'uploaded_by'  => session()->get('user_id'),
            'uploaded_at'  => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('legal', 'upload_doc', $entityId, $name ?: $filename);
        return redirect()->back()->with('success', 'Dokumen berhasil diupload.');
    }

    public function deleteDocument(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');
        (new LegalDocumentModel())->deleteWithFile($id);
        ActivityLog::write('legal', 'delete_doc', $id, 'Dokumen #' . $id);
        return redirect()->back()->with('success', 'Dokumen dihapus.');
    }

    public function downloadDocument(int $id)
    {
        $doc = (new LegalDocumentModel())->find($id);
        if (! $doc) return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');

        $path = FCPATH . $doc['file_path'];
        if (! file_exists($path)) return redirect()->back()->with('error', 'File tidak ditemukan.');

        return $this->response->download($path, null)->setFileName($doc['nama_dokumen']);
    }
}
