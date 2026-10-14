<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventRundownModel;
use App\Models\EventContentItemModel;
use App\Models\EventCompletionModel;
use App\Models\EventLocationModel;
use App\Models\EventContentRealisasiModel;
use App\Libraries\ActivityLog;
use App\Libraries\RabSusun;
use App\Models\BudgetPosModel;
use App\Models\RabDokumenModel;
use App\Models\RabLaporanModel;

class EventContent extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('content')) return null;
        return (new EventModel())->find($eventId);
    }

    /** "1.500.000" / "Rp 1.500.000" → 1500000; kosong → null. */
    private static function rupiah($v): ?int
    {
        $v = preg_replace('/[^\d]/', '', (string) $v);
        return $v === '' ? null : (int) $v;
    }

    /** "1,5" / "2" → 1.5 / 2.0; kosong → null. */
    private static function desimal($v): ?float
    {
        $v = trim(str_replace(',', '.', (string) $v));
        return $v === '' || ! is_numeric($v) ? null : round((float) $v, 2);
    }

    /**
     * Field RAB dari form item: qty × freq × harga satuan (total → budget), kelompok,
     * pos (hanya pos dept Events), mal (hanya bila event di dua mal).
     */
    private function fieldRab(array $post, array $event): array
    {
        $posIds = array_map('intval', array_column((new BudgetPosModel())->untukEvent(), 'id'));
        $posId  = (int) ($post['pos_id'] ?? 0);
        $d = [
            'kelompok'     => trim((string) ($post['kelompok'] ?? '')) ?: null,
            'qty'          => self::desimal($post['qty'] ?? ''),
            'satuan'       => trim((string) ($post['satuan'] ?? '')) ?: null,
            'freq'         => self::desimal($post['freq'] ?? ''),
            'satuan_freq'  => trim((string) ($post['satuan_freq'] ?? '')) ?: null,
            'harga_satuan' => self::rupiah($post['harga_satuan'] ?? ''),
            'pos_id'       => in_array($posId, $posIds, true) ? $posId : null,
            'mall'         => ($event['mall'] ?? '') === 'keduanya' && in_array($post['mall'] ?? '', ['ewalk', 'pentacity'], true) ? $post['mall'] : null,
        ];
        $d['budget'] = $d['harga_satuan'] !== null
            ? (int) RabSusun::totalItem($d)
            : (int) (self::rupiah($post['budget'] ?? '') ?? 0);
        return $d;
    }

    /** Nomor RAB aktif yang mengunci angka item ini (null = bebas diubah). */
    private function kunciItem(int $itemId): ?string
    {
        return (new RabDokumenModel())->itemTerkunci()[$itemId] ?? null;
    }

    /** Laporan realisasi bulan beban item ini sudah disahkan? */
    private function realisasiTerkunci(array $item, array $event): bool
    {
        return in_array(RabSusun::bulanItem($item, $event), (new RabLaporanModel())->bulanDisahkan(), true);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $items     = (new EventContentItemModel())->getByEvent($eventId);
        $locModel  = new EventLocationModel();
        $locations = $locModel->getEventLocations($eventId) ?: $locModel->getByMall($event['mall']);
        $realisasi = (new EventContentRealisasiModel())->getGroupedByEvent($eventId);

        $programs       = array_filter($items, fn($i) => ($i['tipe'] ?? 'program') === 'program');
        $biayaItems     = array_filter($items, fn($i) => ($i['tipe'] ?? 'program') === 'biaya');
        $budgetProgram  = array_sum(array_column(array_values($programs), 'budget'));
        $budgetBiaya    = array_sum(array_column(array_values($biayaItems), 'budget'));
        $totalBudget    = $budgetProgram + $budgetBiaya;
        $totalRealisasi = 0;
        foreach ($realisasi as $rGroup) { $totalRealisasi += array_sum(array_column($rGroup, 'nilai')); }
        $pctGlobal      = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : 0;
        $barGlobal      = $pctGlobal >= 100 ? 'danger' : ($pctGlobal >= 75 ? 'warning' : 'success');

        return view('content/index', [
            'user'           => $this->currentUser(),
            'event'          => $event,
            'items'          => $items,
            'programs'       => $programs,
            'biayaItems'     => $biayaItems,
            'budgetProgram'  => $budgetProgram,
            'budgetBiaya'    => $budgetBiaya,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'pctGlobal'      => $pctGlobal,
            'barGlobal'      => $barGlobal,
            'locations'      => $locations,
            'realisasi'      => $realisasi,
            'completion'     => ($completion = (new EventCompletionModel())->getByEvent($eventId)['content'] ?? null),
            'canEdit'        => $this->canEditMenu('content') && ! $completion,
            'posPilihan'     => ($posPilihan = (new BudgetPosModel())->untukEvent()),
            'posNama'        => array_column($posPilihan, 'nama', 'id') + array_column((new BudgetPosModel())->select('id, nama')->findAll(), 'nama', 'id'),
            'kelompokList'   => array_values(array_unique(array_filter(array_column($items, 'kelompok')))),
            'terkunci'       => ($rabDok = new RabDokumenModel())->itemTerkunci(),
            'periodeKunci'   => $rabDok->periodeTerkunci(),
            'bulanDisahkan'  => (new RabLaporanModel())->bulanDisahkan(),
        ]);
    }

    public function rundown(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $rundown = (new EventRundownModel())->getByEvent($eventId);

        $grouped = [];
        foreach ($rundown as $r) {
            $grouped[$r['hari_ke']][] = $r;
        }

        return view('content/rundown', [
            'user'       => $this->currentUser(),
            'event'      => $event,
            'grouped'    => $grouped,
            'completion' => ($completion = (new EventCompletionModel())->getByEvent($eventId)['content'] ?? null),
            'canEdit'    => $this->canEditMenu('content') && ! $completion,
        ]);
    }

    public function printRundown(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $rundown = (new EventRundownModel())->getByEvent($eventId);
        $grouped = [];
        foreach ($rundown as $r) {
            $grouped[$r['hari_ke']][] = $r;
        }

        return view('content/rundown_print', [
            'event'   => $event,
            'grouped' => $grouped,
        ]);
    }

    public function saveContent(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        (new EventModel())->update($eventId, [
            'content' => $this->request->getPost('content'),
        ]);
        ActivityLog::write('update', 'content', (string) $eventId, 'Deskripsi content — ' . ($event['name'] ?? ''));

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Deskripsi berhasil disimpan.');
    }

    public function addItem(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post       = $this->request->getPost();
        $itemModel  = new EventContentItemModel();
        $count      = $itemModel->where('event_id', $eventId)->countAllResults();

        $tipe   = $post['tipe'] === 'biaya' ? 'biaya' : 'program';
        // Tanggal juga untuk biaya penunjang: menentukan bulan beban di RAB (event lintas bulan).
        $itemId = $itemModel->insert($this->fieldRab($post, $event) + [
            'event_id'      => $eventId,
            'nama'          => $post['nama'],
            'tipe'          => $tipe,
            'tanggal'       => ($post['tanggal'] ?? '') ?: null,
            'waktu_mulai'   => $tipe === 'program' ? ($post['waktu_mulai'] ?: null) : null,
            'waktu_selesai' => $tipe === 'program' ? ($post['waktu_selesai'] ?: null) : null,
            'jenis'         => $post['jenis'] ?? null,
            'pic'           => $post['pic'] ?? null,
            'lokasi'        => $tipe === 'program' ? ($post['lokasi'] ?? null) : null,
            'keterangan'    => $post['keterangan'] ?? null,
            'urutan'        => $count + 1,
            'created_by'    => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'content', (string)$itemId, $post['nama'], ['event_id' => $eventId, 'tipe' => $tipe, 'budget' => $post['budget'] ?? 0]);

        // Auto-sync to rundown only for program type
        if ($tipe === 'program' && ! empty($post['tanggal'])) {
            $item = $itemModel->find($itemId);
            (new EventRundownModel())->syncFromContentItem($item, $eventId, strtotime($event['start_date']));
        }

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil ditambahkan.');
    }

    public function editItem(int $eventId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post      = $this->request->getPost();
        $itemModel = new EventContentItemModel();

        $existing = $itemModel->find($id);
        if (! $existing || (int) $existing['event_id'] !== $eventId) return redirect()->to("/events/{$eventId}/content")->with('error', 'Item tidak ditemukan.');
        $tipe     = $existing['tipe'] ?? 'program';

        $contentData = $this->fieldRab($post, $event) + [
            'nama'          => $post['nama'],
            'tanggal'       => ($post['tanggal'] ?? '') ?: null,
            'waktu_mulai'   => $tipe === 'program' ? ($post['waktu_mulai'] ?: null) : null,
            'waktu_selesai' => $tipe === 'program' ? ($post['waktu_selesai'] ?: null) : null,
            'jenis'         => $post['jenis'] ?? null,
            'pic'           => $post['pic'] ?? null,
            'lokasi'        => $tipe === 'program' ? ($post['lokasi'] ?? null) : null,
            'keterangan'    => $post['keterangan'] ?? null,
        ];

        // Item yang tercantum di RAB aktif: angka, pos, mal, kelompok, nama & bulan
        // beban hanya boleh berubah lewat revisi RAB. PIC, jam, lokasi, keterangan bebas.
        if ($nomor = $this->kunciItem($id)) {
            $beda = RabSusun::beda(['items' => [RabSusun::barisItem($existing, $event)]],
                                   ['items' => [RabSusun::barisItem($contentData + $existing, $event)]]);
            if ($beda['tanda'] || $beda['dihapus']) {
                return redirect()->to("/events/{$eventId}/content#item-{$id}")->with('error', 'Item ini tercantum di ' . $nomor
                    . '. Angka, pos, mal, kelompok, nama, dan bulan tidak bisa diubah sebelum RAB direvisi (Budget Departemen → RAB → Revisi).');
            }
        }

        ActivityLog::captureBefore($existing);
        $itemModel->update($id, $contentData);
        ActivityLog::captureAfter($contentData);
        ActivityLog::write('update', 'content', (string)$id, $post['nama'], ['event_id' => $eventId]);

        // Re-sync to rundown only for program type
        $item = $itemModel->find($id);
        if ($tipe === 'program') {
            if (! empty($item['tanggal'])) {
                (new EventRundownModel())->syncFromContentItem($item, $eventId, strtotime($event['start_date']));
            } else {
                (new EventRundownModel())->deleteByContentItem($id);
            }
        }

        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil diperbarui.');
    }

    public function deleteItem(int $eventId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $item = (new EventContentItemModel())->find($id);
        if (! $item || (int) $item['event_id'] !== $eventId) return redirect()->to("/events/{$eventId}/content")->with('error', 'Item tidak ditemukan.');
        if ($nomor = $this->kunciItem($id)) {
            return redirect()->to("/events/{$eventId}/content#item-{$id}")->with('error', 'Item ini tercantum di ' . $nomor . ' — hapus lewat revisi RAB.');
        }
        if ((new EventContentRealisasiModel())->where('content_item_id', $id)->countAllResults() > 0) {
            return redirect()->to("/events/{$eventId}/content#item-{$id}")->with('error', 'Item ini sudah punya realisasi — hapus realisasinya dulu.');
        }

        $db->transStart();
        (new EventRundownModel())->deleteByContentItem($id);
        (new EventContentItemModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/content")->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        ActivityLog::write('delete', 'content', (string)$id, $item['nama'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/content")->with('success', 'Item berhasil dihapus.');
    }

    public function storeRealisasi(int $eventId, int $itemId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        $item  = (new EventContentItemModel())->find($itemId);
        if (! $event || ! $item || (int) $item['event_id'] !== $eventId) return redirect()->to("/events/{$eventId}/content")->with('error', 'Item tidak ditemukan.');
        if ($this->realisasiTerkunci($item, $event)) {
            return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('error', 'Laporan realisasi bulan ini sudah disahkan — realisasi tidak bisa ditambah.');
        }

        $post      = $this->request->getPost();
        $uploadDir = FCPATH . 'uploads/content-realisasi/' . $eventId . '/';
        if (! is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileFoto   = null;
        $fileTerima = null;

        $foto = $this->request->getFile('file_foto');
        if ($foto && $foto->isValid() && ! $foto->hasMoved()) {
            if ($err = $this->validateUpload($foto, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'foto_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($foto);
            $foto->move($uploadDir, $name);
            \App\Libraries\ImageCompressor::compress($uploadDir . '/' . $name);
            $fileFoto = $name;
        }

        $terima = $this->request->getFile('file_terima');
        if ($terima && $terima->isValid() && ! $terima->hasMoved()) {
            if ($err = $this->validateUpload($terima, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'terima_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($terima);
            $terima->move($uploadDir, $name);
            \App\Libraries\ImageCompressor::compress($uploadDir . '/' . $name);
            $fileTerima = $name;
        }

        $nilai = (int) str_replace([',', '.', ' '], '', $post['nilai'] ?? 0);
        (new EventContentRealisasiModel())->insert([
            'event_id'        => $eventId,
            'content_item_id' => $itemId,
            'tanggal'         => $post['tanggal'] ?: null,
            'nilai'           => $nilai,
            'catatan'         => $post['catatan'] ?? null,
            'file_foto'       => $fileFoto,
            'file_terima'     => $fileTerima,
            'created_by'      => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'content_realisasi', (string)$itemId, 'Realisasi Content', ['event_id' => $eventId, 'tanggal' => $post['tanggal'] ?? null, 'nilai' => $nilai]);

        return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $itemId, int $id)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/content")->with('error', 'Akses ditolak.');

        $model = new EventContentRealisasiModel();
        $row   = $model->find($id);
        $item  = (new EventContentItemModel())->find($itemId);
        $event = (new EventModel())->find($eventId);
        if ($row && $item && $event && $this->realisasiTerkunci($item, $event)) {
            return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('error', 'Laporan realisasi bulan ini sudah disahkan — realisasi tidak bisa dihapus.');
        }
        if ($row && (int) $row['event_id'] === $eventId) {
            $dir = FCPATH . 'uploads/content-realisasi/' . $eventId . '/';
            if ($row['file_foto']   && file_exists($dir . $row['file_foto']))   unlink($dir . $row['file_foto']);
            if ($row['file_terima'] && file_exists($dir . $row['file_terima'])) unlink($dir . $row['file_terima']);
            $model->delete($id);
            ActivityLog::write('delete', 'content_realisasi', (string)$id, 'Realisasi Content', ['event_id' => $eventId, 'item_id' => $itemId]);
        }

        return redirect()->to("/events/{$eventId}/content#item-{$itemId}")->with('success', 'Realisasi berhasil dihapus.');
    }

    public function saveRundown(int $eventId)
    {
        if (! $this->canEditMenu('content')) return redirect()->to("/events/{$eventId}/rundown")->with('error', 'Akses ditolak.');

        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $post     = $this->request->getPost();
        $model    = new EventRundownModel();
        $tanggals = $post['tanggal'] ?? [];

        $model->deleteByEvent($eventId);

        $startTs = strtotime($event['start_date']);

        foreach ($tanggals as $i => $tanggal) {
            if (empty($post['sesi'][$i])) continue;

            $hariKe = (int)((strtotime($tanggal) - $startTs) / 86400) + 1;

            $model->insert([
                'event_id'      => $eventId,
                'hari_ke'       => $hariKe,
                'tanggal'       => $tanggal,
                'waktu_mulai'   => $post['waktu_mulai'][$i] ?: null,
                'waktu_selesai' => $post['waktu_selesai'][$i] ?: null,
                'sesi'          => $post['sesi'][$i],
                'deskripsi'     => $post['deskripsi'][$i] ?? null,
                'pic'           => $post['pic'][$i] ?? null,
                'lokasi'        => $post['lokasi'][$i] ?? null,
                'urutan'        => $i + 1,
            ]);
        }

        ActivityLog::write('update', 'content', (string) $eventId, 'Simpan rundown event');
        return redirect()->to("/events/{$eventId}/rundown")->with('success', 'Rundown berhasil disimpan.');
    }
}
