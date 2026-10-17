<?php

namespace App\Controllers;

use App\Services\CreativeTurunanService;

/**
 * Aksi turunan Design / Motion Graphic. Satu pintu untuk dua lingkup:
 *   POST creative/turunan/{standalone|event}/{id}/{aksi}
 * `id` = id item induk untuk aksi "buat", selain itu id turunan.
 *
 * Hak:
 *  - standalone: ubah = hak ubah menu `creative_main`; setujui / tolak / buka = canApproveCreative()
 *  - event     : ubah = hak ubah menu `creative`;      setujui / tolak / buka = canApproveCreative(true)
 *  Lihat = siapa pun yang boleh membuka halamannya.
 */
class CreativeTurunanCtrl extends BaseController
{
    private const AKSI_UBAH   = ['buat', 'ubah', 'hapus', 'unggah', 'hapus-berkas', 'ajukan'];
    private const AKSI_SETUJU = ['setujui', 'tolak', 'buka'];

    public function aksi(string $scope, int $id, string $aksi)
    {
        $isEvent = $scope === 'event';
        if (! $isEvent && $scope !== 'standalone') return redirect()->to('/creative')->with('error', 'Lingkup tidak dikenal.');
        if (! in_array($aksi, array_merge(self::AKSI_UBAH, self::AKSI_SETUJU), true)) {
            return redirect()->to('/creative')->with('error', 'Aksi tidak dikenal.');
        }

        // Cari item induk (untuk tujuan kembali dan id event)
        $db = db_connect();
        if ($aksi === 'buat') {
            $itemId = $id;
        } else {
            $t = $db->table('creative_turunan')->select('item_id,scope')->where('id', $id)->get()->getRowArray();
            if (! $t || $t['scope'] !== $scope) return redirect()->to('/creative')->with('error', 'Turunan tidak ditemukan.');
            $itemId = (int) $t['item_id'];
        }
        $eventId = null;
        if ($isEvent) {
            $it = $db->table('event_creative_items')->select('event_id')->where('id', $itemId)->get()->getRowArray();
            if (! $it) return redirect()->to('/events')->with('error', 'Item tidak ditemukan.');
            $eventId = (int) $it['event_id'];
            $balik = "/events/{$eventId}/creative#item-{$itemId}";
        } else {
            $balik = '/creative#item-' . $itemId . '-s';
        }

        // Hak akses
        $menu = $isEvent ? 'creative' : 'creative_main';
        if (! $this->canViewMenu($menu)) return redirect()->to('/')->with('error', 'Akses ditolak.');
        if (in_array($aksi, self::AKSI_UBAH, true) && ! $this->canEditMenu($menu)) {
            return redirect()->to($balik)->with('error', 'Anda tidak punya hak mengubah turunan.');
        }
        if (in_array($aksi, self::AKSI_SETUJU, true) && ! $this->canApproveCreative($isEvent)) {
            return redirect()->to($balik)->with('error', 'Hanya lead Creative atau admin yang dapat menyetujui, menolak, atau membuka turunan.');
        }

        $svc  = $isEvent ? CreativeTurunanService::forEvent($eventId) : CreativeTurunanService::standalone();
        $user = $this->currentUser();
        $post = $this->request->getPost();

        switch ($aksi) {
            case 'buat':         $h = $svc->buat($id, $post, $user); break;
            case 'ubah':         $h = $svc->ubah($id, $post, $user); break;
            case 'hapus':        $h = $svc->hapus($id, $user); break;
            case 'ajukan':       $h = $svc->ajukan($id, $user); break;
            case 'setujui':      $h = $svc->setujui($id, $user); break;
            case 'tolak':        $h = $svc->tolak($id, (string) ($post['alasan'] ?? ''), $user); break;
            case 'buka':         $h = $svc->buka($id, (string) ($post['catatan'] ?? ''), $user); break;
            case 'hapus-berkas': $h = $svc->hapusBerkas($id, (int) ($post['file_id'] ?? 0), $user); break;
            case 'unggah':       $h = $this->unggah($svc, $id, $user); break;
            default:             $h = ['ok' => false, 'message' => 'Aksi tidak dikenal.'];
        }

        return redirect()->to($balik . ($isEvent ? '' : '~turunan'))->with($h['ok'] ? 'success' : 'error', $h['message']);
    }

    private function unggah(CreativeTurunanService $svc, int $id, array $user): array
    {
        $siap = $svc->siapUnggah($id);
        if (isset($siap['err'])) return ['ok' => false, 'message' => $siap['err']];

        $file = $this->request->getFile('berkas');
        if (! $file || ! $file->isValid() || $file->hasMoved()) return ['ok' => false, 'message' => 'File tidak valid.'];
        if ($err = $this->validateUpload($file, array_merge(self::MIME_IMAGE, self::MIME_DOC), 20)) return ['ok' => false, 'message' => $err];

        $asli = $file->getClientName();
        $tipe = (string) $file->getMimeType();
        $ukuran = (int) $file->getSize();
        $name = 'turunan_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
        $dir  = FCPATH . 'uploads/creative-turunan/' . $id . '/';
        if (! is_dir($dir)) @mkdir($dir, 0755, true);

        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memindahkan berkas turunan: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Gagal menyimpan file ke server. Periksa izin folder upload.'];
        }
        \App\Libraries\ImageCompressor::compress($dir . $name);

        $svc->catatBerkas($id, $name, $asli, $tipe, $ukuran, $user);
        return ['ok' => true, 'message' => 'Berkas diunggah.'];
    }
}
