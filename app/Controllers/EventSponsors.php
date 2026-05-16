<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventSponsorModel;
use App\Models\EventSponsorItemModel;
use App\Models\EventSponsorRealisasiModel;
use App\Models\EventCompletionModel;
use App\Libraries\ActivityLog;

class EventSponsors extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('sponsors')) return null;
        return (new EventModel())->find($eventId);
    }

    private function saveItems(int $sponsorId, array $post): int
    {
        $desks      = $post['deskripsi_barang'] ?? [];
        $qtys       = $post['qty'] ?? [];
        $nilaiItems = $post['nilai_item'] ?? [];
        $itemModel  = new EventSponsorItemModel();
        $total      = 0;

        foreach ($desks as $i => $desk) {
            $n = (int) str_replace([',', '.', ' '], '', $nilaiItems[$i] ?? 0);
            $q = max(0, (int)($qtys[$i] ?? 0));
            if (! $desk && ! $q && ! $n) continue;
            $itemModel->insert([
                'sponsor_id'       => $sponsorId,
                'deskripsi_barang' => $desk ?: null,
                'qty'              => $q ?: null,
                'nilai'            => $n,
            ]);
            $total += $n * ($q > 0 ? $q : 1);
        }
        return $total;
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $sponsors   = (new EventSponsorModel())->getByEvent($eventId);
        $sponsorIds = array_column($sponsors, 'id');
        $allItems   = (new EventSponsorItemModel())->getBySponsorIds($sponsorIds);

        $itemsBySponsors = [];
        foreach ($allItems as $item) {
            $itemsBySponsors[$item['sponsor_id']][] = $item;
        }

        $realisasi = (new EventSponsorRealisasiModel())->getGroupedByEvent($eventId);

        $totalCash      = array_sum(array_column(array_filter($sponsors, fn($s) => $s['jenis'] === 'cash'), 'nilai'));
        $totalBarang    = array_sum(array_column(array_filter($sponsors, fn($s) => $s['jenis'] === 'barang'), 'nilai'));
        $totalRealisasi = 0;
        foreach ($realisasi as $rGroup) { $totalRealisasi += array_sum(array_column($rGroup, 'nilai')); }
        $totalNilai     = $totalCash + $totalBarang;
        $pctGlobal      = $totalNilai > 0 ? min(100, round($totalRealisasi / $totalNilai * 100)) : 0;
        $barGlobal      = $pctGlobal >= 100 ? 'danger' : ($pctGlobal >= 75 ? 'warning' : 'success');

        return view('sponsors/index', [
            'user'            => $this->currentUser(),
            'event'           => $event,
            'sponsors'        => $sponsors,
            'itemsBySponsors' => $itemsBySponsors,
            'realisasi'       => $realisasi,
            'totalCash'       => $totalCash,
            'totalBarang'     => $totalBarang,
            'totalRealisasi'  => $totalRealisasi,
            'totalNilai'      => $totalNilai,
            'pctGlobal'       => $pctGlobal,
            'barGlobal'       => $barGlobal,
            'completion'      => ($completion = (new EventCompletionModel())->getByEvent($eventId)['sponsors'] ?? null),
            'canEdit'         => $this->canEditMenu('sponsors') && ! $completion,
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('sponsors')) return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $isBarang = $post['jenis'] === 'barang';

        $sponsorModel = new EventSponsorModel();
        $sponsorId    = $sponsorModel->insert([
            'event_id'     => $eventId,
            'nama_sponsor' => $post['nama_sponsor'],
            'jenis'        => $post['jenis'],
            'nilai'        => $isBarang ? 0 : (int) str_replace([',', '.', ' '], '', $post['nilai'] ?? 0),
            'detail'       => $post['detail'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);

        if ($isBarang) {
            $total = $this->saveItems($sponsorId, $post);
            $sponsorModel->update($sponsorId, ['nilai' => $total]);
        }
        ActivityLog::write('create', 'sponsor', (string)$sponsorId, $post['nama_sponsor'], ['event_id' => $eventId, 'jenis' => $post['jenis']]);

        return redirect()->to("/events/{$eventId}/sponsors")->with('success', 'Sponsor berhasil ditambahkan.');
    }

    public function update(int $eventId, int $id)
    {
        if (! $this->canEditMenu('sponsors')) return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Akses ditolak.');

        $post     = $this->request->getPost();
        $isBarang = $post['jenis'] === 'barang';

        $sponsorModel = new EventSponsorModel();
        $sponsorModel->update($id, [
            'nama_sponsor' => $post['nama_sponsor'],
            'jenis'        => $post['jenis'],
            'nilai'        => $isBarang ? 0 : (int) str_replace([',', '.', ' '], '', $post['nilai'] ?? 0),
            'detail'       => $post['detail'] ?? null,
        ]);

        if ($isBarang) {
            (new EventSponsorItemModel())->deleteBySponsor($id);
            $total = $this->saveItems($id, $post);
            $sponsorModel->update($id, ['nilai' => $total]);
        }
        ActivityLog::write('update', 'sponsor', (string)$id, $post['nama_sponsor'], ['event_id' => $eventId, 'jenis' => $post['jenis']]);

        return redirect()->to("/events/{$eventId}/sponsors")->with('success', 'Sponsor berhasil diperbarui.');
    }

    public function delete(int $eventId, int $id)
    {
        if (! $this->canEditMenu('sponsors')) return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Akses ditolak.');

        $db             = db_connect();
        $realisasiModel = new EventSponsorRealisasiModel();
        $rows           = $realisasiModel->where('sponsor_id', $id)->findAll();
        $sp             = (new EventSponsorModel())->find($id);

        $db->transStart();
        $realisasiModel->where('sponsor_id', $id)->delete();
        (new EventSponsorItemModel())->deleteBySponsor($id);
        (new EventSponsorModel())->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Gagal menghapus sponsor. Silakan coba lagi.');
        }

        $dir = FCPATH . 'uploads/sponsor-realisasi/' . $eventId . '/';
        foreach ($rows as $r) {
            if ($r['file_foto']   && file_exists($dir . $r['file_foto']))   unlink($dir . $r['file_foto']);
            if ($r['file_terima'] && file_exists($dir . $r['file_terima'])) unlink($dir . $r['file_terima']);
        }

        ActivityLog::write('delete', 'sponsor', (string)$id, $sp['nama_sponsor'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/sponsors")->with('success', 'Sponsor berhasil dihapus.');
    }

    public function storeRealisasi(int $eventId, int $sponsorId)
    {
        if (! $this->canEditMenu('sponsors')) return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Akses ditolak.');

        $post      = $this->request->getPost();
        $uploadDir = FCPATH . 'uploads/sponsor-realisasi/' . $eventId . '/';
        if (! is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileFoto   = null;
        $fileTerima = null;

        $foto = $this->request->getFile('file_foto');
        if ($foto && $foto->isValid() && ! $foto->hasMoved()) {
            if ($err = $this->validateUpload($foto, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'foto_' . $sponsorId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($foto);
            $foto->move($uploadDir, $name);
            $fileFoto = $name;
        }

        $terima = $this->request->getFile('file_terima');
        if ($terima && $terima->isValid() && ! $terima->hasMoved()) {
            if ($err = $this->validateUpload($terima, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $name = 'terima_' . $sponsorId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($terima);
            $terima->move($uploadDir, $name);
            $fileTerima = $name;
        }

        $nilai = (int) str_replace([',', '.', ' '], '', $post['nilai'] ?? 0);
        (new EventSponsorRealisasiModel())->insert([
            'event_id'   => $eventId,
            'sponsor_id' => $sponsorId,
            'tanggal'    => $post['tanggal'] ?: null,
            'nilai'      => $nilai,
            'catatan'    => $post['catatan'] ?? null,
            'file_foto'  => $fileFoto,
            'file_terima'=> $fileTerima,
            'created_by' => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'sponsor_realisasi', (string)$sponsorId, 'Realisasi Sponsor', ['event_id' => $eventId, 'tanggal' => $post['tanggal'] ?? null, 'nilai' => $nilai]);

        return redirect()->to("/events/{$eventId}/sponsors#sponsor-{$sponsorId}")->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $eventId, int $sponsorId, int $id)
    {
        if (! $this->canEditMenu('sponsors')) return redirect()->to("/events/{$eventId}/sponsors")->with('error', 'Akses ditolak.');

        $model = new EventSponsorRealisasiModel();
        $row   = $model->find($id);
        if ($row) {
            $dir = FCPATH . 'uploads/sponsor-realisasi/' . $eventId . '/';
            if ($row['file_foto']   && file_exists($dir . $row['file_foto']))   unlink($dir . $row['file_foto']);
            if ($row['file_terima'] && file_exists($dir . $row['file_terima'])) unlink($dir . $row['file_terima']);
            $model->delete($id);
            ActivityLog::write('delete', 'sponsor_realisasi', (string)$id, 'Realisasi Sponsor', ['event_id' => $eventId, 'sponsor_id' => $sponsorId]);
        }

        return redirect()->to("/events/{$eventId}/sponsors#sponsor-{$sponsorId}")->with('success', 'Realisasi berhasil dihapus.');
    }
}
