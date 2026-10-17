<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\CreativeTemaModel;
use App\Services\CreativePaketService;
use App\Services\CreativePemetaanService;
use App\Services\CreativeTemaService;

/**
 * Tema Creative: Tema Bulanan (tambah/ubah/hapus), tutup/buka Tema, dan panel
 * pemetaan data lama. Hak:
 *  - kelola Tema Bulanan & pemetaan : hak ubah menu `creative_main`;
 *  - tutup / buka Tema              : "lead Creative" = hak SETUJUI creative_main
 *                                     (canApproveCreative: admin, role manager, atau grant Setujui).
 */
class CreativeTemaCtrl extends BaseController
{
    private function kembali(string $hash = '')
    {
        return redirect()->to('/creative' . $hash);
    }

    // ───────────────────────── Tema Bulanan ─────────────────────────

    public function tambah()
    {
        if (! $this->canEditMenu('creative_main')) return $this->kembali()->with('error', 'Akses ditolak.');

        $nama  = trim((string) $this->request->getPost('nama'));
        $bulan = (string) $this->request->getPost('bulan');
        if ($nama === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            return $this->kembali()->with('error', 'Nama Tema dan bulan wajib diisi.');
        }

        $id = (new CreativeTemaModel())->insert([
            'nama' => mb_substr($nama, 0, 200), 'bulan' => $bulan, 'ditutup' => 0,
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'dibuat_oleh' => $this->currentUser()['id'],
        ]);
        ActivityLog::write('create', 'creative_tema', 'bulanan:' . $id, $nama, ['bulan' => $bulan]);

        // Paket item awal (item "Rencana") untuk Tema Bulanan yang baru
        $paket = (new CreativePaketService())->tambahkan('bulanan', (int) $id, mb_substr($nama, 0, 200), (int) $this->currentUser()['id']);

        return $this->kembali('#tema-t' . $id)->with('success', 'Tema Bulanan “' . $nama . '” dibuat'
            . ($paket['dibuat'] > 0 ? ' dengan ' . $paket['dibuat'] . ' item rencana dari paket awal. Hapus yang tidak diperlukan.' : '.'));
    }

    public function ubah(int $id)
    {
        if (! $this->canEditMenu('creative_main')) return $this->kembali()->with('error', 'Akses ditolak.');

        $model = new CreativeTemaModel();
        $tema  = $model->find($id);
        if (! $tema) return $this->kembali()->with('error', 'Tema tidak ditemukan.');
        if ((int) $tema['ditutup'] === 1) return $this->kembali('#tema-t' . $id)->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);

        $nama  = trim((string) $this->request->getPost('nama'));
        $bulan = (string) $this->request->getPost('bulan');
        if ($nama === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            return $this->kembali('#tema-t' . $id)->with('error', 'Nama Tema dan bulan wajib diisi.');
        }
        $model->update($id, ['nama' => mb_substr($nama, 0, 200), 'bulan' => $bulan,
                             'deskripsi' => trim((string) $this->request->getPost('deskripsi')) ?: null]);
        ActivityLog::write('update', 'creative_tema', 'bulanan:' . $id, $nama, ['bulan' => $bulan]);

        return $this->kembali('#tema-t' . $id)->with('success', 'Tema diperbarui.');
    }

    public function hapus(int $id)
    {
        if (! $this->canEditMenu('creative_main')) return $this->kembali()->with('error', 'Akses ditolak.');

        $model = new CreativeTemaModel();
        $tema  = $model->find($id);
        if (! $tema) return $this->kembali()->with('error', 'Tema tidak ditemukan.');

        $n = db_connect()->table('creative_items')->where('tema_id', $id)->countAllResults();
        if ($n > 0) {
            return $this->kembali('#tema-t' . $id)->with('error', 'Tema masih berisi ' . $n . ' item. Pindahkan atau hapus itemnya dulu.');
        }
        if ((int) $tema['ditutup'] === 1) return $this->kembali('#tema-t' . $id)->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);

        $model->delete($id);
        ActivityLog::write('delete', 'creative_tema', 'bulanan:' . $id, $tema['nama'], []);

        return $this->kembali()->with('success', 'Tema “' . $tema['nama'] . '” dihapus.');
    }

    // ───────────────────────── Tutup / buka ─────────────────────────

    public function tutup()  { return $this->setDitutup(true); }
    public function buka()   { return $this->setDitutup(false); }

    private function setDitutup(bool $tutup)
    {
        if (! $this->canViewMenu('creative_main') || ! $this->canApproveCreative()) {
            return $this->kembali()->with('error', 'Hanya lead Creative atau admin yang dapat menutup / membuka Tema.');
        }
        $jenis = $this->request->getPost('jenis') === 'event' ? 'event' : 'bulanan';
        $id    = (int) $this->request->getPost('id');
        $key0  = ($jenis === 'event' ? 'e' : 't') . $id;

        // Menutup Tema yang masih punya item "Rencana": pengguna harus memilih hapus atau tetap.
        $rencanaMsg = '';
        if ($tutup) {
            $rencana = CreativePaketService::itemRencana($jenis, $id);
            if ($rencana) {
                $pilih = (string) $this->request->getPost('rencana');
                if ($pilih === 'hapus') {
                    $nama = $jenis === 'event'
                        ? (string) (db_connect()->table('events')->select('name')->where('id', $id)->get()->getRow()->name ?? '')
                        : (string) ((new CreativeTemaModel())->find($id)['nama'] ?? '');
                    $n = (new CreativePaketService())->hapusRencana($jenis, $id, $nama);
                    $rencanaMsg = ' ' . $n . ' item rencana yang belum dikerjakan dihapus.';
                } elseif ($pilih === 'tetap') {
                    $rencanaMsg = ' ' . count($rencana) . ' item rencana dibiarkan.';
                } else {
                    return $this->kembali('#tema-' . $key0)->with('error', count($rencana) . ' item belum dikerjakan: pilih hapus atau tetap sebelum menutup Tema.');
                }
            }
        }

        $hasil = (new CreativeTemaService())->setDitutup($jenis, $id, $tutup, $this->currentUser());
        if ($hasil['ok']) $hasil['message'] .= $rencanaMsg;
        $key   = ($jenis === 'event' ? 'e' : 't') . $id;

        return $this->kembali($hasil['ok'] ? '#tema-' . $key : '')->with($hasil['ok'] ? 'success' : 'error', $hasil['message']);
    }

    // ───────────────────────── Paket item awal ─────────────────────────

    /** Tombol "Tambahkan paket awal" pada kartu Tema (idempoten). */
    public function paketAwal()
    {
        if (! $this->canEditMenu('creative_main')) return $this->kembali()->with('error', 'Akses ditolak.');

        $jenis = $this->request->getPost('jenis') === 'event' ? 'event' : 'bulanan';
        $id    = (int) $this->request->getPost('id');
        $key   = ($jenis === 'event' ? 'e' : 't') . $id;

        if ($jenis === 'event') {
            $ev = db_connect()->table('events')->select('id,name')->where('id', $id)->get()->getRowArray();
            if (! $ev) return $this->kembali()->with('error', 'Event tidak ditemukan.');
            if (CreativeTemaService::eventDitutup($id)) return $this->kembali('#tema-' . $key)->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);
            $nama = $ev['name'];
        } else {
            $tema = (new CreativeTemaModel())->find($id);
            if (! $tema) return $this->kembali()->with('error', 'Tema tidak ditemukan.');
            if ((int) $tema['ditutup'] === 1) return $this->kembali('#tema-' . $key)->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);
            $nama = $tema['nama'];
        }

        $h = (new CreativePaketService())->tambahkan($jenis, $id, $nama, (int) $this->currentUser()['id']);
        $msg = $h['dibuat'] > 0
            ? $h['dibuat'] . ' item rencana ditambahkan' . ($h['dilewati'] ? ', ' . $h['dilewati'] . ' sudah ada (dilewati).' : '.')
            : 'Tidak ada item baru: semua item paket sudah ada di Tema ini.';
        return $this->kembali('#tema-' . $key)->with('success', $msg);
    }

    // ───────────────────────── Pemetaan data lama ─────────────────────────

    public function pemetaan()
    {
        if (! $this->canViewMenu('creative_main') || ! $this->canEditMenu('creative_main')) {
            return $this->kembali()->with('error', 'Akses ditolak.');
        }
        $svc = new CreativePemetaanService();
        return view('creative_main/pemetaan', ['user' => $this->currentUser(), 'ukuranUsulan' => $svc->usulanUkuran()] + $svc->usulan());
    }

    public function terapkan()
    {
        if (! $this->canEditMenu('creative_main')) return redirect()->to('/creative/pemetaan')->with('error', 'Akses ditolak.');

        $type = (string) $this->request->getPost('type');
        if (! in_array($type, ['event', 'tema', 'baru'], true)) return redirect()->to('/creative/pemetaan')->with('error', 'Tujuan pemetaan tidak valid.');

        $hasil = (new CreativePemetaanService())->terapkan(
            $type,
            $type === 'baru' ? 0 : (int) $this->request->getPost('id'),
            (string) $this->request->getPost('nama'),
            (string) $this->request->getPost('bulan'),
            (array) $this->request->getPost('items'),
            $this->currentUser()
        );
        return redirect()->to('/creative/pemetaan')->with($hasil['ok'] ? 'success' : 'error', $hasil['message']);
    }

    public function terapkanUkuran()
    {
        if (! $this->canEditMenu('creative_main')) return redirect()->to('/creative/pemetaan')->with('error', 'Akses ditolak.');
        $pilih = (array) $this->request->getPost('ukuran_ok');
        $isi   = (array) $this->request->getPost('ukuran');
        $pairs = [];
        foreach ($pilih as $id) if (isset($isi[$id])) $pairs[(int) $id] = $isi[$id];
        $h = (new CreativePemetaanService())->terapkanUkuran($pairs, $this->currentUser());
        return redirect()->to('/creative/pemetaan#ukuran')->with($h['ok'] ? 'success' : 'error', $h['message']);
    }

    public function batal()
    {
        if (! $this->canEditMenu('creative_main')) return redirect()->to('/creative/pemetaan')->with('error', 'Akses ditolak.');

        $hasil = (new CreativePemetaanService())->batalkan((string) $this->request->getPost('batch'), $this->currentUser());
        return redirect()->to('/creative/pemetaan')->with($hasil['ok'] ? 'success' : 'error', $hasil['message']);
    }
}
