<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Libraries\ActivityLog;
use App\Models\EventLoyaltyModel;
use App\Models\EventLoyaltyRealisasiModel;
use App\Models\EventLoyaltyHadiahItemModel;
use App\Models\EventLoyaltyHadiahRealisasiModel;
use App\Models\EventLoyaltyVoucherItemModel;
use App\Models\EventLoyaltyVoucherRealisasiModel;
use App\Models\EventBudgetModel;
use App\Models\EventCompletionModel;

class EventLoyaltyCtrl extends BaseController
{
    private function getEvent(int $eventId): ?array
    {
        if (! $this->canViewMenu('loyalty')) return null;
        return (new EventModel())->find($eventId);
    }

    public function index(int $eventId)
    {
        $event = $this->getEvent($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $programs   = (new EventLoyaltyModel())->getByEvent($eventId);
        $realisasi  = (new EventLoyaltyRealisasiModel())->getGroupedByEvent($eventId);
        $programIds = array_column($programs, 'id');

        $hadiahItems = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);
        $allHadiahIds = [];
        foreach ($hadiahItems as $items) { foreach ($items as $item) { $allHadiahIds[] = $item['id']; } }
        $hadiahRealisasi = (new EventLoyaltyHadiahRealisasiModel())->getGroupedByItems($allHadiahIds);

        $voucherItems = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $allVoucherIds = [];
        foreach ($voucherItems as $items) { foreach ($items as $item) { $allVoucherIds[] = $item['id']; } }
        $voucherRealisasi = (new EventLoyaltyVoucherRealisasiModel())->getGroupedByItems($allVoucherIds);

        $totalBudgetProgram = array_sum(array_column($programs, 'budget'));

        return view('loyalty/index', [
            'user'               => $this->currentUser(),
            'event'              => $event,
            'programs'           => $programs,
            'realisasi'          => $realisasi,
            'hadiahItems'        => $hadiahItems,
            'hadiahRealisasi'    => $hadiahRealisasi,
            'voucherItems'       => $voucherItems,
            'voucherRealisasi'   => $voucherRealisasi,
            'totalBudgetProgram' => $totalBudgetProgram,
            'completion'         => ($completion = (new EventCompletionModel())->getByEvent($eventId)['loyalty'] ?? null),
            'canEdit'            => $this->canEditMenu('loyalty') && ! $completion,
        ]);
    }

    // ── Program CRUD ─────────────────────────────────────────────────────────

    public function storeProgram(int $eventId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $pid = (new EventLoyaltyModel())->insert([
            'event_id'     => $eventId,
            'nama_program' => $post['nama_program'],
            'mekanisme'    => $post['mekanisme'] ?? null,
            'catatan'      => $post['catatan'] ?? null,
            'budget'       => 0,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty', (string)$pid, $post['nama_program'], ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty")->with('success', 'Program loyalty berhasil ditambahkan.');
    }

    public function updateProgram(int $eventId, int $id)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        (new EventLoyaltyModel())->update($id, [
            'nama_program'   => $post['nama_program'],
            'mekanisme'      => $post['mekanisme'] ?? null,
            'target_peserta'      => ($post['target_peserta'] ?? null) ?: null,
            'target_member_aktif' => ($post['target_member_aktif'] ?? null) ?: null,
            'catatan'        => $post['catatan'] ?? null,
        ]);
        ActivityLog::write('update', 'loyalty', (string)$id, $post['nama_program'], ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$id}")->with('success', 'Program berhasil diperbarui.');
    }

    public function deleteProgram(int $eventId, int $id)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');

        $db   = db_connect();
        $prog = (new EventLoyaltyModel())->find($id);

        $db->transStart();

        (new EventLoyaltyRealisasiModel())->where('program_id', $id)->delete();

        $hadiahItems = (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->findAll();
        foreach ($hadiahItems as $item) {
            (new EventLoyaltyHadiahRealisasiModel())->where('item_id', $item['id'])->delete();
        }
        (new EventLoyaltyHadiahItemModel())->where('program_id', $id)->delete();

        $voucherItems = (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->findAll();
        foreach ($voucherItems as $item) {
            (new EventLoyaltyVoucherRealisasiModel())->where('item_id', $item['id'])->delete();
        }
        (new EventLoyaltyVoucherItemModel())->where('program_id', $id)->delete();

        (new EventLoyaltyModel())->delete($id);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Gagal menghapus program. Silakan coba lagi.');
        }

        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('delete', 'loyalty', (string)$id, $prog['nama_program'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/loyalty")->with('success', 'Program berhasil dihapus.');
    }

    // ── Member realisasi ──────────────────────────────────────────────────────

    public function storeRealisasi(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        $jumlah = (int)($post['jumlah'] ?? 0);
        (new EventLoyaltyRealisasiModel())->insert([
            'program_id'   => $programId,
            'event_id'     => $eventId,
            'tanggal'      => $post['tanggal'],
            'jumlah'       => $jumlah,
            'member_aktif' => (int)($post['member_aktif'] ?? 0),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'loyalty_realisasi', (string)$programId, 'Realisasi Member', ['event_id' => $eventId, 'tanggal' => $post['tanggal'], 'jumlah' => $jumlah]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi member disimpan.');
    }

    public function deleteRealisasi(int $eventId, int $programId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyRealisasiModel())->delete($rid);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── e-Voucher items ───────────────────────────────────────────────────────

    public function storeVoucherItem(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        $penyerapan = ($post['target_penyerapan'] ?? '') !== '' ? (float)$post['target_penyerapan'] : null;
        (new EventLoyaltyVoucherItemModel())->insert([
            'program_id'        => $programId,
            'nama_voucher'      => $post['nama_voucher'],
            'nilai_voucher'     => $clean($post['nilai_voucher']),
            'total_diterbitkan' => (int)($post['total_diterbitkan'] ?? 0),
            'target_penyerapan' => $penyerapan,
            'catatan'           => $post['catatan'] ?? null,
            'created_by'        => $this->currentUser()['id'],
        ]);
        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('create', 'loyalty_voucher', (string)$programId, $post['nama_voucher'], ['event_id' => $eventId, 'nilai' => $post['nilai_voucher'] ?? 0, 'qty' => $post['total_diterbitkan'] ?? 0]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Voucher ditambahkan.');
    }

    public function deleteVoucherItem(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyVoucherRealisasiModel())->where('item_id', $itemId)->delete();
        (new EventLoyaltyVoucherItemModel())->delete($itemId);
        $this->syncLoyaltyBudget($eventId);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Voucher dihapus.');
    }

    public function storeVoucherRealisasi(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        (new EventLoyaltyVoucherRealisasiModel())->insert([
            'program_id' => $programId,
            'item_id'    => $itemId,
            'tanggal'    => $post['tanggal'],
            'tersebar'   => (int)($post['tersebar'] ?? 0),
            'terpakai'   => (int)($post['terpakai'] ?? 0),
            'catatan'    => $post['catatan'] ?? null,
            'created_by' => $this->currentUser()['id'],
        ]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi voucher disimpan.');
    }

    public function deleteVoucherRealisasi(int $eventId, int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyVoucherRealisasiModel())->delete($rid);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── Hadiah barang items ───────────────────────────────────────────────────

    public function storeHadiahItem(int $eventId, int $programId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post  = $this->request->getPost();
        $clean = fn($v) => (int)str_replace([',', '.', ' '], '', $v ?? 0);
        (new EventLoyaltyHadiahItemModel())->insert([
            'program_id'   => $programId,
            'nama_hadiah'  => $post['nama_hadiah'],
            'stok'         => (int)($post['stok'] ?? 0),
            'nilai_satuan' => $clean($post['nilai_satuan']),
            'catatan'      => $post['catatan'] ?? null,
            'created_by'   => $this->currentUser()['id'],
        ]);
        $this->syncLoyaltyBudget($eventId);
        ActivityLog::write('create', 'loyalty_hadiah', (string)$programId, $post['nama_hadiah'], ['event_id' => $eventId, 'stok' => $post['stok'] ?? 0, 'nilai_satuan' => $post['nilai_satuan'] ?? 0]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Item hadiah ditambahkan.');
    }

    public function deleteHadiahItem(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyHadiahRealisasiModel())->where('item_id', $itemId)->delete();
        (new EventLoyaltyHadiahItemModel())->delete($itemId);
        $this->syncLoyaltyBudget($eventId);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Item hadiah dihapus.');
    }

    public function storeHadiahRealisasi(int $eventId, int $programId, int $itemId)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        if (empty($post['tanggal'])) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Tanggal wajib diisi.');
        (new EventLoyaltyHadiahRealisasiModel())->insert([
            'program_id'       => $programId,
            'item_id'          => $itemId,
            'tanggal'          => $post['tanggal'],
            'jumlah_dibagikan' => (int)($post['jumlah_dibagikan'] ?? 0),
            'catatan'          => $post['catatan'] ?? null,
            'created_by'       => $this->currentUser()['id'],
        ]);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Realisasi hadiah disimpan.');
    }

    public function deleteHadiahRealisasi(int $eventId, int $programId, int $itemId, int $rid)
    {
        if (! $this->canEditMenu('loyalty')) return redirect()->to("/events/{$eventId}/loyalty")->with('error', 'Akses ditolak.');
        (new EventLoyaltyHadiahRealisasiModel())->delete($rid);
        return redirect()->to("/events/{$eventId}/loyalty#program-{$programId}")->with('success', 'Entri realisasi dihapus.');
    }

    // ── Budget sync ───────────────────────────────────────────────────────────

    private function syncLoyaltyBudget(int $eventId): void
    {
        $programs   = (new EventLoyaltyModel())->getByEvent($eventId);
        $programIds = array_column($programs, 'id');

        $voucherItems = (new EventLoyaltyVoucherItemModel())->getByPrograms($programIds);
        $hadiahItems  = (new EventLoyaltyHadiahItemModel())->getByPrograms($programIds);

        $loyaltyModel = new EventLoyaltyModel();
        $total = 0;
        foreach ($programs as $prog) {
            $pid        = $prog['id'];
            $progBudget = 0;
            foreach ($voucherItems[$pid] ?? [] as $vi) {
                $progBudget += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher'];
            }
            foreach ($hadiahItems[$pid] ?? [] as $hi) {
                $progBudget += (int)$hi['stok'] * (int)$hi['nilai_satuan'];
            }
            $loyaltyModel->update($pid, ['budget' => $progBudget]);
            $total += $progBudget;
        }

        $deptId = (int)session()->get('dept_id');
        if (! $deptId) return;

        $model = new EventBudgetModel();
        $model->where('event_id', $eventId)->where('department_id', $deptId)->delete();
        if ($total > 0) {
            $model->insert([
                'event_id'      => $eventId,
                'department_id' => $deptId,
                'kategori'      => 'Program Loyalty',
                'keterangan'    => 'Total dari ' . count($programs) . ' program',
                'jumlah'        => $total,
                'created_by'    => $this->currentUser()['id'],
            ]);
        }
    }
}
