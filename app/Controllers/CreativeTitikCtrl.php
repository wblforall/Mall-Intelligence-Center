<?php

namespace App\Controllers;

use App\Services\CreativeTitikService;

/**
 * Titik Media Cetak dan titik layar Konten Digital: pilih / cabut titik dari master Media Promo dan bukti pasang / tayang per titik.
 *   GET  creative/titik/{standalone|event}/{itemId}/daftar   -> JSON daftar titik + ketersediaan pada periode pasang
 *   POST creative/titik/{standalone|event}/{itemId}/pilih    -> spot_ids[]
 *   POST creative/titik/{standalone|event}/{linkId}/cabut    -> konfirmasi=1 bila titik sudah terpasang
 *   POST creative/titik/{standalone|event}/{linkId}/bukti    -> tanggal_pasang, catatan, berkas (foto)
 *   POST creative/titik/{standalone|event}/{linkId}/bukti-hapus
 *
 * Hak: lihat daftar = hak lihat menu; ubah = hak ubah menu (`creative_main` untuk standalone, `creative` untuk event).
 */
class CreativeTitikCtrl extends BaseController
{
    private const AKSI = ['daftar', 'pilih', 'cabut', 'bukti', 'bukti-hapus'];

    public function aksi(string $scope, int $id, string $aksi)
    {
        $isEvent = $scope === 'event';
        $json = $aksi === 'daftar';
        if (! $isEvent && $scope !== 'standalone') return $this->galat($json, '/creative', 'Lingkup tidak dikenal.');
        if (! in_array($aksi, self::AKSI, true)) return $this->galat($json, '/creative', 'Aksi tidak dikenal.');

        $db = db_connect();
        // id = id item (daftar, pilih) atau id titik (lainnya)
        if (in_array($aksi, ['daftar', 'pilih'], true)) {
            $itemId = $id;
        } else {
            $l = $db->table('creative_cetak_titik')->select('item_id,scope')->where('id', $id)->get()->getRowArray();
            if (! $l || $l['scope'] !== $scope) return $this->galat(false, $isEvent ? '/events' : '/creative', 'Titik tidak ditemukan.');
            $itemId = (int) $l['item_id'];
        }
        $eventId = null;
        if ($isEvent) {
            $it = $db->table('event_creative_items')->select('event_id')->where('id', $itemId)->get()->getRowArray();
            if (! $it) return $this->galat($json, '/events', 'Item tidak ditemukan.');
            $eventId = (int) $it['event_id'];
            $balik = "/events/{$eventId}/creative#item-{$itemId}";
        } else {
            $balik = '/creative#item-' . $itemId . '-s~' . (in_array($aksi, ['bukti', 'bukti-hapus'], true) ? 'realisasi' : 'titik');
        }

        $menu = $isEvent ? 'creative' : 'creative_main';
        if (! $this->canViewMenu($menu)) return $this->galat($json, '/', 'Akses ditolak.');
        if ($aksi !== 'daftar' && ! $this->canEditMenu($menu)) return $this->galat(false, $balik, 'Anda tidak punya hak mengubah titik Creative (Media Cetak / Konten Digital).');

        $svc  = $isEvent ? CreativeTitikService::forEvent($eventId) : CreativeTitikService::standalone();
        $user = $this->currentUser();
        $post = $this->request->getPost();

        switch ($aksi) {
            case 'daftar':
                return $this->response->setJSON($svc->daftar($itemId));
            case 'pilih':
                $h = $svc->pilih($itemId, (array) ($post['spot_ids'] ?? []), $user);
                break;
            case 'cabut':
                $h = $svc->cabut($id, ! empty($post['konfirmasi']), $user);
                break;
            case 'bukti':
                $h = $this->bukti($svc, $id, $user);
                break;
            case 'bukti-hapus':
                $h = $svc->hapusBukti($id, $user);
                break;
            default:
                $h = ['ok' => false, 'message' => 'Aksi tidak dikenal.'];
        }
        return redirect()->to($balik)->with($h['ok'] ? 'success' : 'error', $h['message']);
    }

    private function bukti(CreativeTitikService $svc, int $linkId, array $user): array
    {
        $siap = $svc->siapBukti($linkId);
        if (isset($siap['err'])) return ['ok' => false, 'message' => $siap['err']];

        $nama = null; $asli = null;
        $file = $this->request->getFile('berkas');
        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $file->isValid() || $file->hasMoved()) return ['ok' => false, 'message' => 'File tidak valid.'];
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 10)) return ['ok' => false, 'message' => $err];
            $asli = $file->getClientName();
            $nama = 'pasang_' . $linkId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $dir  = CreativeTitikService::dirBukti($linkId);
            if (! is_dir($dir)) @mkdir($dir, 0755, true);
            try {
                $file->move($dir, $nama);
            } catch (\Throwable $e) {
                log_message('error', 'Gagal memindahkan bukti pasang: ' . $e->getMessage());
                return ['ok' => false, 'message' => 'Gagal menyimpan foto ke server. Periksa izin folder upload.'];
            }
            \App\Libraries\ImageCompressor::compress($dir . $nama);
        }
        $h = $svc->catatBukti($linkId, $nama, $asli, $this->request->getPost('tanggal_pasang'), $this->request->getPost('catatan'), $user);
        if (! $h['ok'] && $nama !== null) {   // jangan tinggalkan foto tanpa baris DB
            $p = CreativeTitikService::dirBukti($linkId) . $nama;
            if (is_file($p)) @unlink($p);
            @rmdir(CreativeTitikService::dirBukti($linkId));
        }
        return $h;
    }

    private function galat(bool $json, string $ke, string $pesan)
    {
        if ($json) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => $pesan]);
        return redirect()->to($ke)->with('error', $pesan);
    }
}
