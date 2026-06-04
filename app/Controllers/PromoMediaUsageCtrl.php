<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\PromoMediaSpotModel;
use App\Models\PromoMediaUsageModel;

class PromoMediaUsageCtrl extends BaseController
{
    private function checkView()
    {
        if (! $this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        return null;
    }

    public function myUsage()
    {
        if ($r = $this->checkView()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usageModel->markDoneExpired();

        $depts = db_connect()->table('departments')->select('id, name')->orderBy('name')->get()->getResultArray();

        return view('creative/media_promo/my_usage', [
            'user'   => $this->currentUser(),
            'usages' => $usageModel->getByCreator($this->currentUser()['id']),
            'depts'  => array_column($depts, 'name'),
        ]);
    }

    public function store()
    {
        if ($r = $this->checkView()) return $r;

        $post       = $this->request->getPost();
        $spotModel  = new PromoMediaSpotModel();
        $usageModel = new PromoMediaUsageModel();
        $userId     = $this->currentUser()['id'];

        $mode       = $post['req_mode'] ?? 'cetak';
        $tglMulai   = $post['tanggal_mulai'];
        $tglSelesai = $post['tanggal_selesai'];

        if ($tglMulai > $tglSelesai) {
            return redirect()->to('/creative/media-promo')->with('error', 'Tanggal mulai tidak boleh lebih dari tanggal selesai.');
        }

        $batchId    = substr(bin2hex(random_bytes(8)), 0, 16);
        $commonData = [
            'batch_id'         => $batchId,
            'dept'             => trim($post['dept']),
            'requested_by'     => trim($post['requested_by'] ?? '') ?: null,
            'nama_materi'      => $post['nama_materi'],
            'deskripsi_materi' => ($post['deskripsi_materi'] ?? '') ?: null,
            'tanggal_mulai'    => $tglMulai,
            'tanggal_selesai'  => $tglSelesai,
            'status'           => 'draft',
            'catatan_pemohon'  => ($post['catatan_pemohon'] ?? '') ?: null,
            'sumber'           => in_array($post['sumber'] ?? '', ['internal','tenant','external']) ? $post['sumber'] : 'internal',
            'is_berbayar'      => isset($post['is_berbayar']) ? 1 : 0,
            'created_by'       => $userId,
        ];

        if ($mode === 'digital') {
            $slotSelections = $post['slot_selections'] ?? [];
            if (empty($slotSelections)) {
                return redirect()->to('/creative/media-promo')->with('error', 'Pilih minimal 1 slot digital.');
            }
            $created  = [];
            $conflict = [];
            foreach ($slotSelections as $rawSpotId => $slotNums) {
                $spotId = (int)$rawSpotId;
                $spot   = $spotModel->find($spotId);
                if (! $spot || $spot['tipe'] !== 'digital') continue;
                foreach ((array)$slotNums as $slotNum) {
                    $slotNum = (int)$slotNum;
                    if ($slotNum < 1 || $slotNum > $spot['total_slots']) continue;
                    if ($usageModel->hasConflictDigital($spotId, $slotNum, $tglMulai, $tglSelesai)) {
                        $conflict[] = $spot['kode'] . ' Slot ' . $slotNum;
                        continue;
                    }
                    $id = $usageModel->insert(array_merge($commonData, ['spot_id' => $spotId, 'slot_number' => $slotNum]));
                    ActivityLog::write('create', 'promo_media_usage', (string)$id, $post['nama_materi']);
                    $created[] = $spot['kode'] . ' Slot ' . $slotNum;
                }
            }
            if (empty($created)) {
                return redirect()->to('/creative/media-promo')->with('error', 'Semua slot yang dipilih sudah terpakai: ' . implode(', ', $conflict));
            }
            $msg = 'Request dibuat untuk: ' . implode(', ', $created) . '.';
            if ($conflict) $msg .= ' Konflik (dilewati): ' . implode(', ', $conflict) . '.';
            return redirect()->to('/creative/media-promo/my')->with('success', $msg);
        }

        // cetak: multi-spot
        $spotIds = array_filter(array_map('intval', (array)($post['spot_ids'] ?? [])));
        if (empty($spotIds)) {
            return redirect()->to('/creative/media-promo')->with('error', 'Pilih minimal 1 titik media cetak.');
        }

        $created  = [];
        $conflict = [];
        foreach ($spotIds as $spotId) {
            $spot = $spotModel->find($spotId);
            if (! $spot) continue;
            if ($usageModel->hasConflictCetak($spotId, $tglMulai, $tglSelesai)) {
                $conflict[] = $spot['kode'];
                continue;
            }
            $id = $usageModel->insert(array_merge($commonData, ['spot_id' => $spotId, 'slot_number' => null]));
            ActivityLog::write('create', 'promo_media_usage', (string)$id, $post['nama_materi']);
            $created[] = $spot['kode'];
        }

        if (empty($created)) {
            return redirect()->to('/creative/media-promo')->with('error', 'Semua titik yang dipilih sudah terpakai di periode tersebut: ' . implode(', ', $conflict));
        }

        $msg = 'Request dibuat untuk: ' . implode(', ', $created) . '.';
        if ($conflict) $msg .= ' Konflik (dilewati): ' . implode(', ', $conflict) . '.';
        return redirect()->to('/creative/media-promo/my')->with('success', $msg);
    }

    public function update(int $id)
    {
        if ($r = $this->checkView()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usage      = $usageModel->find($id);

        if (! $usage || $usage['created_by'] != $this->currentUser()['id']) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Request tidak ditemukan.');
        }
        if (! in_array($usage['status'], ['draft', 'rejected'])) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Hanya draft atau rejected yang bisa diedit.');
        }

        $post       = $this->request->getPost();
        $spotModel  = new PromoMediaSpotModel();
        $spotId     = (int)($post['spot_id'] ?? $usage['spot_id']);
        $tglMulai   = $post['tanggal_mulai'];
        $tglSelesai = $post['tanggal_selesai'];

        if ($tglMulai > $tglSelesai) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Tanggal mulai tidak boleh lebih dari tanggal selesai.');
        }

        $spot = $spotModel->find($spotId);

        if ($spot['tipe'] === 'digital') {
            $slotNumber = (int)($post['slot_number'] ?? $usage['slot_number']);
            if ($usageModel->hasConflictDigital($spotId, $slotNumber, $tglMulai, $tglSelesai, $id)) {
                return redirect()->to('/creative/media-promo/my')->with('error', "Slot {$slotNumber} sudah dipakai di periode tersebut.");
            }
        } else {
            $slotNumber = null;
            if ($usageModel->hasConflictCetak($spotId, $tglMulai, $tglSelesai, $id)) {
                return redirect()->to('/creative/media-promo/my')->with('error', "Titik {$spot['kode']} sudah dipakai di periode tersebut.");
            }
        }

        ActivityLog::captureBefore($usage);
        $usageUpdateData = [
            'spot_id'          => $spotId,
            'slot_number'      => $slotNumber,
            'dept'             => trim($post['dept']),
            'requested_by'     => trim($post['requested_by'] ?? $usage['requested_by'] ?? '') ?: null,
            'nama_materi'      => $post['nama_materi'],
            'deskripsi_materi' => ($post['deskripsi_materi'] ?? '') ?: null,
            'tanggal_mulai'    => $tglMulai,
            'tanggal_selesai'  => $tglSelesai,
            'status'           => 'draft',
            'catatan_pemohon'  => ($post['catatan_pemohon'] ?? '') ?: null,
            'sumber'           => in_array($post['sumber'] ?? '', ['internal','tenant','external']) ? $post['sumber'] : 'internal',
            'is_berbayar'      => isset($post['is_berbayar']) ? 1 : 0,
            'rejection_reason' => null,
        ];
        $usageModel->update($id, $usageUpdateData);
        ActivityLog::captureAfter($usageUpdateData);

        ActivityLog::write('update', 'promo_media_usage', (string)$id, $post['nama_materi']);
        return redirect()->to('/creative/media-promo/my')->with('success', 'Request diupdate.');
    }

    public function submit(int $id)
    {
        if ($r = $this->checkView()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usage      = $usageModel->find($id);

        if (! $usage || $usage['created_by'] != $this->currentUser()['id']) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Request tidak ditemukan.');
        }
        if (! in_array($usage['status'], ['draft', 'rejected'])) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Hanya draft atau rejected yang bisa disubmit.');
        }

        // Re-check conflict on submit
        $spot = (new PromoMediaSpotModel())->find($usage['spot_id']);
        if ($spot['tipe'] === 'digital') {
            if ($usageModel->hasConflictDigital((int)$usage['spot_id'], (int)$usage['slot_number'], $usage['tanggal_mulai'], $usage['tanggal_selesai'], $id)) {
                return redirect()->to('/creative/media-promo/my')->with('error', 'Slot sudah diambil orang lain. Silakan edit request terlebih dahulu.');
            }
        } else {
            if ($usageModel->hasConflictCetak((int)$usage['spot_id'], $usage['tanggal_mulai'], $usage['tanggal_selesai'], $id)) {
                return redirect()->to('/creative/media-promo/my')->with('error', 'Titik sudah dipakai di periode tersebut. Silakan edit request terlebih dahulu.');
            }
        }

        $usageModel->update($id, [
            'status'       => 'pending',
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('update', 'promo_media_usage', (string)$id, "Submit: {$usage['nama_materi']}");
        return redirect()->to('/creative/media-promo/my')->with('success', 'Request berhasil disubmit untuk approval.');
    }

    public function submitSelected()
    {
        if ($r = $this->checkView()) return $r;

        $ids        = array_filter(array_map('intval', (array)($this->request->getPost('ids') ?? [])));
        $usageModel = new PromoMediaUsageModel();
        $spotModel  = new PromoMediaSpotModel();
        $userId     = $this->currentUser()['id'];

        if (empty($ids)) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Pilih minimal 1 request.');
        }

        $submitted = [];
        $failed    = [];
        $now       = date('Y-m-d H:i:s');

        foreach ($ids as $id) {
            $usage = $usageModel->find($id);
            if (! $usage || $usage['created_by'] != $userId) continue;
            if (! in_array($usage['status'], ['draft', 'rejected'])) continue;

            $spot        = $spotModel->find($usage['spot_id']);
            $label       = $spot['kode'] . ($usage['slot_number'] ? ' Slot '.$usage['slot_number'] : '');
            $hasConflict = $spot['tipe'] === 'digital'
                ? $usageModel->hasConflictDigital((int)$usage['spot_id'], (int)$usage['slot_number'], $usage['tanggal_mulai'], $usage['tanggal_selesai'], $id)
                : $usageModel->hasConflictCetak((int)$usage['spot_id'], $usage['tanggal_mulai'], $usage['tanggal_selesai'], $id);

            if ($hasConflict) {
                $failed[] = $label;
                continue;
            }

            $usageModel->update($id, ['status' => 'pending', 'submitted_at' => $now]);
            ActivityLog::write('update', 'promo_media_usage', (string)$id, "Submit: {$usage['nama_materi']}");
            $submitted[] = $label;
        }

        if (empty($submitted)) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Semua request yang dipilih konflik: ' . implode(', ', $failed));
        }

        $msg = count($submitted) . ' request berhasil disubmit.';
        if ($failed) $msg .= ' Konflik (dilewati): ' . implode(', ', $failed) . '.';
        return redirect()->to('/creative/media-promo/my')->with('success', $msg);
    }

    public function cancel(int $id)
    {
        if ($r = $this->checkView()) return $r;

        $usageModel = new PromoMediaUsageModel();
        $usage      = $usageModel->find($id);

        if (! $usage || $usage['created_by'] != $this->currentUser()['id']) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Request tidak ditemukan.');
        }
        if (in_array($usage['status'], ['approved', 'done'])) {
            return redirect()->to('/creative/media-promo/my')->with('error', 'Request yang sudah approved tidak bisa dibatalkan.');
        }

        $usageModel->delete($id);
        ActivityLog::write('delete', 'promo_media_usage', (string)$id, "Cancel: {$usage['nama_materi']}");
        return redirect()->to('/creative/media-promo/my')->with('success', 'Request dibatalkan.');
    }
}
