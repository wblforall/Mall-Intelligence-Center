<?php

namespace App\Controllers\Legal;

use App\Controllers\BaseController;
use App\Models\LegalPermitModel;
use App\Models\LegalSpkModel;
use App\Models\LegalPksModel;
use App\Models\LegalPsmMallModel;
use App\Models\LegalPsmDeveloperModel;
use App\Models\LegalPsmGudangModel;
use App\Models\LegalKontrakPameranModel;
use App\Models\LegalDocumentModel;
use App\Libraries\ActivityLog;

class LegalController extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('legal')) return redirect()->to('/events');

        $permitM    = new LegalPermitModel();
        $spkM       = new LegalSpkModel();
        $pksM       = new LegalPksModel();
        $psmMallM   = new LegalPsmMallModel();
        $psmDevM    = new LegalPsmDeveloperModel();
        $psmGudangM = new LegalPsmGudangModel();
        $pameranM   = new LegalKontrakPameranModel();

        $summary = [
            'permits'       => ['active' => $permitM->where('status', 'active')->countAllResults(),     'expiring' => $permitM->getExpiringCount(30)],
            'spk'           => ['active' => $spkM->where('status', 'aktif')->countAllResults(),         'expiring' => $spkM->getExpiringCount(30)],
            'pks'           => ['active' => $pksM->where('status', 'active')->countAllResults(),        'expiring' => $pksM->getExpiringCount(30)],
            'psm_mall'      => ['active' => $psmMallM->where('status', 'active')->countAllResults(),    'expiring' => $psmMallM->getExpiringCount(30)],
            'psm_developer' => ['active' => $psmDevM->where('status', 'active')->countAllResults(),     'expiring' => $psmDevM->getExpiringCount(30)],
            'psm_gudang'    => ['active' => $psmGudangM->where('status', 'active')->countAllResults(),  'expiring' => $psmGudangM->getExpiringCount(30)],
            'pameran'       => ['active' => $pameranM->where('status', 'aktif')->countAllResults(),     'expiring' => $pameranM->getExpiringCount(30)],
        ];

        return view('legal/index', [
            'title'    => 'Legal',
            'summary'  => $summary,
            'expiring' => $this->getExpiringAll(30),
        ]);
    }

    private function getExpiringAll(int $days): array
    {
        $cutoff = date('Y-m-d', strtotime("+{$days} days"));
        $today  = date('Y-m-d');
        $db     = \Config\Database::connect();

        $permits = $db->table('legal_permits')
            ->select("id, 'permit' as entity_type, CONCAT('Izin: ', nama_izin) as nama, nomor_izin as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir IS NOT NULL')->where('tanggal_berakhir <=', $cutoff)
            ->where('tanggal_berakhir >=', $today)->where('status', 'active')->get()->getResultArray();

        $pks = $db->table('legal_pks')
            ->select("id, 'pks' as entity_type, CONCAT('PKS: ', pihak_kedua) as nama, nomor_pks as nomor, NULL as mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $psmMall = $db->table('legal_psm_mall')
            ->select("id, 'psm_mall' as entity_type, CONCAT('PSM Mall: ', nama_tenant) as nama, nomor_psm as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $psmDev = $db->table('legal_psm_developer')
            ->select("id, 'psm_developer' as entity_type, CONCAT('PSM Dev: ', nama_developer) as nama, nomor_psm as nomor, mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $psmGudang = $db->table('legal_psm_gudang')
            ->select("id, 'psm_gudang' as entity_type, CONCAT('Gudang: ', nama_penyewa) as nama, nomor_psm as nomor, NULL as mall_id, tanggal_berakhir, status")
            ->where('tanggal_berakhir <=', $cutoff)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();

        $spk = $db->table('legal_spk')
            ->select("id, 'spk' as entity_type, CONCAT('SPK: ', nama_vendor) as nama, nomor_spk as nomor, NULL as mall_id, tanggal_selesai as tanggal_berakhir, status")
            ->where('tanggal_selesai <=', $cutoff)->where('tanggal_selesai >=', $today)
            ->whereIn('status', ['draft', 'aktif'])->get()->getResultArray();

        $pameran = $db->table('legal_kontrak_pameran')
            ->select("id, 'kontrak_pameran' as entity_type, CONCAT('Pameran: ', nama_event) as nama, nomor_kontrak as nomor, mall_id, tanggal_selesai as tanggal_berakhir, status")
            ->where('tanggal_selesai <=', $cutoff)->where('tanggal_selesai >=', $today)
            ->whereIn('status', ['draft', 'aktif'])->get()->getResultArray();

        $rows = array_merge($permits, $pks, $psmMall, $psmDev, $psmGudang, $spk, $pameran);
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

        $validTypes = ['permit', 'spk', 'pks', 'psm_mall', 'psm_developer', 'psm_gudang', 'kontrak_pameran'];
        if (! in_array($type, $validTypes) || ! $entityId) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        if ($err = $this->validateUpload($file, self::MIME_DOC, 20)) {
            return redirect()->back()->with('error', $err);
        }

        $ext      = $this->safeExt($file);
        $filename = $type . '_' . $entityId . '_' . time() . '.' . $ext;
        $destDir  = FCPATH . 'uploads/legal/';
        if (! is_dir($destDir)) mkdir($destDir, 0755, true);
        $file->move($destDir, $filename);
        \App\Libraries\ImageCompressor::compress($destDir . '/' . $filename);

        (new LegalDocumentModel())->insert([
            'entity_type'  => $type,
            'entity_id'    => $entityId,
            'nama_dokumen' => $name ?: $file->getClientName(),
            'file_path'    => 'uploads/legal/' . $filename,
            'file_size'    => $file->getSize(),
            'uploaded_by'  => session()->get('user_id'),
            'uploaded_at'  => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('create', 'legal', (string) $entityId, 'Upload dokumen: ' . ($name ?: $filename));
        return redirect()->back()->with('success', 'Dokumen berhasil diupload.');
    }

    public function deleteDocument(int $id)
    {
        if (! $this->canEditMenu('legal')) return redirect()->back()->with('error', 'Akses ditolak.');
        (new LegalDocumentModel())->deleteWithFile($id);
        ActivityLog::write('delete', 'legal', (string) $id, 'Hapus dokumen #' . $id);
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
