<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Services\CreativePaketService;
use App\Services\CreativeTurunanService;

/**
 * Katalog Turunan dan Paket Item Awal Creative. Hanya lead Creative (hak Setujui) atau admin.
 *  - Jenis katalog tidak dihapus bila sudah dipakai turunan: cukup dinonaktifkan.
 *  - Mengubah katalog / paket tidak mengubah turunan atau item yang sudah dibuat.
 */
class CreativeKatalogCtrl extends BaseController
{
    private function boleh(): bool
    {
        return $this->canViewMenu('creative_main') && $this->canApproveCreative();
    }

    private function kembali(string $tab = 'turunan', string $hash = '')
    {
        return redirect()->to('/creative/katalog?tab=' . $tab . $hash);
    }

    private function tolak()
    {
        return redirect()->to('/creative')->with('error', 'Katalog dan paket awal hanya dapat diatur oleh lead Creative atau admin.');
    }

    public function index()
    {
        if (! $this->boleh()) return $this->tolak();

        $db = db_connect();
        $dipakai = [];
        foreach ($db->query('SELECT jenis_id, COUNT(*) n FROM creative_turunan WHERE jenis_id IS NOT NULL GROUP BY jenis_id')->getResultArray() as $r) {
            $dipakai[(int) $r['jenis_id']] = (int) $r['n'];
        }
        $tab = $this->request->getGet('tab') === 'paket' ? 'paket' : 'turunan';

        return view('creative_main/katalog', [
            'user'    => $this->currentUser(),
            'tab'     => $tab,
            'katalog' => CreativeTurunanService::katalog(false),
            'dipakai' => $dipakai,
            'paket'   => ['bulanan' => CreativePaketService::baris('bulanan', false), 'event' => CreativePaketService::baris('event', false)],
        ]);
    }

    // ───────────────────────── Jenis turunan ─────────────────────────

    public function simpanJenis()
    {
        if (! $this->boleh()) return $this->tolak();

        $id     = (int) $this->request->getPost('id');
        $induk  = (string) $this->request->getPost('induk');
        $kel    = (string) $this->request->getPost('kelompok');
        $nama   = trim((string) $this->request->getPost('nama'));
        $bebas  = (bool) $this->request->getPost('bebas');

        if (! in_array($induk, ['design', 'motion_graphic'], true)) return $this->kembali()->with('error', 'Induk tidak valid.');
        $kelOk = $induk === 'design' ? ['digital', 'cetak'] : ['video'];
        if (! in_array($kel, $kelOk, true)) return $this->kembali()->with('error', 'Kelompok tidak sesuai dengan induk.');
        if ($nama === '') return $this->kembali()->with('error', 'Nama jenis wajib diisi.');

        $opsi = [];
        if (! $bebas) {
            $lb = (array) $this->request->getPost('opsi_label');
            $w  = (array) $this->request->getPost('opsi_w');
            $h  = (array) $this->request->getPost('opsi_h');
            $st = (array) $this->request->getPost('opsi_sat');
            foreach ($lb as $i => $label) {
                $label = trim((string) $label);
                if ($label === '') continue;
                $sat = in_array(($st[$i] ?? 'px'), ['px', 'mm', 'cm', 'm'], true) ? $st[$i] : 'px';
                $o = ['label' => mb_substr($label, 0, 120)];
                if ((int) ($w[$i] ?? 0) > 0) $o['w'] = (int) $w[$i];
                if ((int) ($h[$i] ?? 0) > 0) $o['h'] = (int) $h[$i];
                $o['satuan'] = $sat;
                $opsi[] = $o;
            }
            if (! $opsi) return $this->kembali()->with('error', 'Isi minimal satu opsi ukuran, atau centang “ukuran bebas”.');
        }
        $ukuranBaku = $bebas ? ['bebas' => true] : $opsi;

        $durasi = null;
        if ($induk === 'motion_graphic') {
            $d = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;]+/', (string) $this->request->getPost('durasi'))), fn($x) => $x > 0 && $x <= 3600)));
            sort($d);
            $durasi = $d ? json_encode($d) : null;
        }

        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $data = [
            'induk' => $induk, 'kelompok' => $kel, 'nama' => mb_substr($nama, 0, 120),
            'ukuran_baku' => json_encode($ukuranBaku, JSON_UNESCAPED_UNICODE), 'durasi_opsi' => $durasi, 'updated_at' => $now,
        ];
        if ($id) {
            $lama = $db->table('creative_turunan_jenis')->where('id', $id)->get()->getRowArray();
            if (! $lama) return $this->kembali()->with('error', 'Jenis tidak ditemukan.');
            $db->table('creative_turunan_jenis')->where('id', $id)->update($data);
        } else {
            $max = (int) ($db->table('creative_turunan_jenis')->selectMax('urutan', 'm')->where('induk', $induk)->get()->getRow()->m ?? 0);
            $db->table('creative_turunan_jenis')->insert($data + ['aktif' => 1, 'urutan' => $max + 1, 'dibuat_oleh' => (int) $this->currentUser()['id'], 'created_at' => $now]);
            $id = (int) $db->insertID();
        }
        ActivityLog::write($this->request->getPost('id') ? 'update' : 'create', 'creative_turunan', 'katalog:' . $id, $nama, ['aksi' => 'katalog_jenis']);

        return $this->kembali('turunan', '#jenis-' . $id)->with('success', 'Jenis “' . $nama . '” disimpan. Turunan yang sudah dibuat tidak berubah.');
    }

    public function toggleJenis(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        $db = db_connect();
        $r = $db->table('creative_turunan_jenis')->where('id', $id)->get()->getRowArray();
        if (! $r) return $this->kembali()->with('error', 'Jenis tidak ditemukan.');
        $db->table('creative_turunan_jenis')->where('id', $id)->update(['aktif' => $r['aktif'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'creative_turunan', 'katalog:' . $id, $r['nama'], ['aksi' => $r['aktif'] ? 'katalog_nonaktif' : 'katalog_aktif']);
        return $this->kembali('turunan', '#jenis-' . $id)->with('success', $r['aktif'] ? 'Jenis dinonaktifkan; tidak muncul lagi di daftar centang.' : 'Jenis diaktifkan kembali.');
    }

    public function geserJenis(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        $db = db_connect();
        $r = $db->table('creative_turunan_jenis')->where('id', $id)->get()->getRowArray();
        if (! $r) return $this->kembali()->with('error', 'Jenis tidak ditemukan.');
        $arah = $this->request->getPost('arah') === 'naik' ? 'naik' : 'turun';
        $ids = array_column($db->table('creative_turunan_jenis')->select('id')->where('induk', $r['induk'])->orderBy('urutan')->orderBy('id')->get()->getResultArray(), 'id');
        $i = array_search((string) $id, array_map('strval', $ids), true);
        $j = $arah === 'naik' ? $i - 1 : $i + 1;
        if ($i !== false && $j >= 0 && $j < count($ids)) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $n => $jid) $db->table('creative_turunan_jenis')->where('id', $jid)->update(['urutan' => $n + 1]);
        }
        return $this->kembali('turunan', '#jenis-' . $id);
    }

    public function hapusJenis(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        $db = db_connect();
        $r = $db->table('creative_turunan_jenis')->where('id', $id)->get()->getRowArray();
        if (! $r) return $this->kembali()->with('error', 'Jenis tidak ditemukan.');
        if ($db->table('creative_turunan')->where('jenis_id', $id)->countAllResults() > 0) {
            return $this->kembali('turunan', '#jenis-' . $id)->with('error', 'Jenis ini sudah dipakai turunan sehingga tidak bisa dihapus. Nonaktifkan saja.');
        }
        $db->table('creative_turunan_jenis')->where('id', $id)->delete();
        ActivityLog::write('delete', 'creative_turunan', 'katalog:' . $id, $r['nama'], ['aksi' => 'katalog_jenis']);
        return $this->kembali()->with('success', 'Jenis “' . $r['nama'] . '” dihapus.');
    }

    // ───────────────────────── Paket item awal ─────────────────────────

    public function simpanPaket()
    {
        if (! $this->boleh()) return $this->tolak();
        $id = (int) $this->request->getPost('id');
        $h  = (new CreativePaketService())->simpan($id ?: null, $this->request->getPost(), (int) $this->currentUser()['id']);
        return $this->kembali('paket')->with($h['ok'] ? 'success' : 'error', $h['message']);
    }

    public function togglePaket(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        $h = (new CreativePaketService())->toggle($id);
        return $this->kembali('paket')->with($h['ok'] ? 'success' : 'error', $h['message']);
    }

    public function geserPaket(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        (new CreativePaketService())->geser($id, $this->request->getPost('arah') === 'naik' ? 'naik' : 'turun');
        return $this->kembali('paket');
    }

    public function hapusPaket(int $id)
    {
        if (! $this->boleh()) return $this->tolak();
        $h = (new CreativePaketService())->hapus($id);
        return $this->kembali('paket')->with($h['ok'] ? 'success' : 'error', $h['message']);
    }
}
