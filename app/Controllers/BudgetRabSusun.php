<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\RabSusun;
use App\Models\BudgetPosModel;
use App\Models\EventContentItemModel;
use App\Models\EventContentRealisasiModel;
use App\Models\EventLocationModel;
use App\Models\EventModel;
use App\Models\EventRundownModel;
use App\Models\RabDokumenModel;

/**
 * Susun RAB — lembar kerja RAB draf dalam bentuk yang biasa disusun tim E&P:
 * satu tab per mal × bulan, berisi program (= event) → kelompok → baris
 * qty × freq × harga satuan.
 *
 * Lembar kerja ini tidak punya tabel sendiri. Setiap program disimpan sebagai
 * event di modul Events dan setiap baris sebagai item Event Content-nya, jadi
 * data tetap rinci per event; RAB (pratinjau, PDF e-Sign, laporan realisasi)
 * membacanya seperti biasa. Aturan bulan/mal/pos baris: RabSusun.
 */
class BudgetRabSusun extends BudgetRab
{
    /** Boleh menyusun: mengisi budget dept ini dan membuat/mengubah isi event. */
    private function bolehSusun(array $dept): bool
    {
        return $this->bolehUbah($dept) && ($this->can('can_create_event') || $this->canEditMenu('content'));
    }

    /** Header event (nama, jenis, tanggal, lokasi, pos) boleh diubah dari sini? */
    private function bolehUbahHeader(array $ev, array $terkunci): bool
    {
        foreach ($ev['_item_ids'] ?? [] as $i) if (isset($terkunci[$i])) return false;    // biaya event sudah di RAB aktif lain
        if (in_array((string) (session()->get('user_role') ?? ''), ['admin', 'manager'], true)) return true;
        return ($ev['approval_status'] ?? '') !== 'approved' && (int) ($ev['created_by'] ?? 0) === (int) session()->get('user_id');
    }

    /** Tab yang diminta: [bulan, mal]; bawaan bulan pertama periode, eWalk. */
    private function tab(array $d): array
    {
        $bulanP = RabSusun::daftarBulan($d['periode_mulai'], $d['periode_selesai']);
        $b = (string) $this->request->getGetPost('bulan');
        $m = (string) $this->request->getGetPost('mall');
        return [in_array($b, $bulanP, true) ? $b : $bulanP[0], in_array($m, ['ewalk', 'pentacity'], true) ? $m : 'ewalk', $bulanP];
    }

    /**
     * Program & baris satu tab dari data hidup.
     * Program = event mal ini (atau dua mal) yang jadwalnya menyentuh bulan ini, atau punya baris di tab ini.
     */
    private function isiTab(string $bulan, string $mall): array
    {
        $db    = db_connect();
        $awal  = $bulan . '-01';
        $akhir = date('Y-m-t', strtotime($awal));
        $events = $db->query(
            "SELECT e.* FROM events e
              WHERE e.mall IN (?, 'keduanya')
                AND ((e.start_date <= ? AND DATE_ADD(e.start_date, INTERVAL (e.event_days - 1) DAY) >= ?)
                     OR e.id IN (SELECT event_id FROM event_content_items WHERE tanggal BETWEEN ? AND ?))",
            [$mall, $akhir, $awal, $awal, $akhir]
        )->getResultArray();
        if (! $events) return [[], []];

        $ids   = array_column($events, 'id');
        $semua = $db->table('event_content_items')->whereIn('event_id', $ids)->orderBy('urutan')->orderBy('id')->get()->getResultArray();
        $evById = array_column($events, null, 'id');
        $lok = [];
        foreach ($db->table('event_location_map')->whereIn('event_id', $ids)->get()->getResultArray() as $r) $lok[$r['event_id']][] = (int) $r['location_id'];
        $income = array_column($db->table('event_exhibitors')->select('event_id, SUM(nilai_dealing) AS n')->whereIn('event_id', $ids)
            ->groupBy('event_id')->get()->getResultArray(), 'n', 'event_id');

        $items = [];
        foreach ($semua as $it) {
            $ev = $evById[$it['event_id']];
            $evById[$it['event_id']]['_item_ids'][] = (int) $it['id'];
            if (RabSusun::bulanItem($it, $ev) === $bulan && RabSusun::mallItem($it, $ev) === $mall) $items[] = $it;
        }
        $adaBaris = array_unique(array_map('intval', array_column($items, 'event_id')));
        $program = [];
        foreach ($evById as $id => $ev) {
            $akhirEv = RabSusun::akhirEvent($ev);
            $singgung = $ev['start_date'] <= $akhir && $akhirEv >= $awal;
            if (! $singgung && ! in_array((int) $id, $adaBaris, true)) continue;
            $ev['_lokasi'] = $lok[$id] ?? [];
            $ev['_income'] = (float) ($income[$id] ?? 0);
            $ev['_akhir']  = $akhirEv;
            $program[(int) $id] = $ev;
        }
        uasort($program, fn ($a, $b) => RabSusun::kunciUrutEvent($a, (int) $a['id']) <=> RabSusun::kunciUrutEvent($b, (int) $b['id']));
        return [$program, $items];
    }

    public function lembar(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        if ($d['status'] !== 'draf') return redirect()->to($this->url('rab/' . $id, $dept))->with('error', 'Hanya draf yang bisa disusun. Buat revisi dulu untuk mengubah RAB yang sudah diajukan.');
        if (! $this->bolehSusun($dept)) return redirect()->to($this->url('rab/' . $id, $dept))->with('error', 'Anda tidak punya hak menyusun RAB (perlu hak isi Budget Departemen dan Content event).');

        [$bulan, $mall, $bulanP] = $this->tab($d);
        [$program, $items] = $this->isiTab($bulan, $mall);
        $terkunci = (new RabDokumenModel())->itemTerkunci();
        $adaReal  = $items ? array_map('intval', array_column((new EventContentRealisasiModel())->select('content_item_id')
            ->whereIn('content_item_id', array_column($items, 'id'))->groupBy('content_item_id')->findAll(), 'content_item_id')) : [];

        // Bentuk data untuk lembar kerja (dirender JS).
        $baris = [];
        foreach ($items as $it) $baris[(int) $it['event_id']][] = $it;
        $data = [];
        foreach ($program as $evId => $ev) {
            $rows = []; $kel = null;
            foreach ($baris[$evId] ?? [] as $it) {
                $k = trim((string) ($it['kelompok'] ?? ''));
                if ($k !== '' && $k !== $kel) $rows[] = ['jenis' => 'kel', 'nama' => $k];
                $kel = $k !== '' ? $k : $kel;
                $rows[] = [
                    'jenis' => 'biaya', 'id' => (int) $it['id'], 'nama' => $it['nama'],
                    'qty' => $it['qty'], 'satuan' => $it['satuan'], 'freq' => $it['freq'], 'satuan_freq' => $it['satuan_freq'],
                    'harga' => $it['harga_satuan'], 'budget' => (int) $it['budget'], 'pos_id' => $it['pos_id'] ? (int) $it['pos_id'] : null,
                    'tanggal' => $it['tanggal'], 'keterangan' => $it['keterangan'],
                    'kunci' => $terkunci[(int) $it['id']] ?? null, 'real' => in_array((int) $it['id'], $adaReal, true),
                ];
            }
            $data[] = [
                'event_id' => $evId, 'nama' => $ev['name'], 'jenis' => $ev['jenis'] ?: 'event', 'mall' => $ev['mall'],
                'mulai' => $ev['start_date'], 'selesai' => $ev['_akhir'], 'lokasi' => $ev['_lokasi'], 'pos_id' => $ev['pos_id'] ? (int) $ev['pos_id'] : null,
                'approval' => $ev['approval_status'], 'income' => $ev['_income'], 'ubahHeader' => $this->bolehUbahHeader($ev, $terkunci), 'rows' => $rows,
            ];
        }

        $snap = $this->snapshotHidup($dept, $d['periode_mulai'], $d['periode_selesai']);
        $pos  = array_values(array_filter((new BudgetPosModel())->untukEvent(), fn ($p) => (int) $p['dept_id'] === (int) $dept['id']));

        return view('budget_dept/rab_susun', $this->konteks($dept, $this->tahun($dept), 'rab') + [
            'd'        => $d,
            'bulan'    => $bulan,
            'mall'     => $mall,
            'bulanP'   => $bulanP,
            'data'     => $data,
            'pos'      => $pos,
            'lokasi'   => (new EventLocationModel())->getByMall($mall),
            'ringkas'  => RabSusun::ringkasan($snap),
            'url'      => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    /** Simpan satu tab (AJAX, JSON). Semua atau tidak sama sekali. */
    public function simpan(int $id)
    {
        $jawab = fn (bool $ok, string $pesan, int $kode = 200) => $this->response->setStatusCode($kode)
            ->setJSON(['ok' => $ok, 'pesan' => $pesan, 'csrf' => csrf_hash()]);
        $dept = $this->deptRab();
        if (! $dept || ! ($d = $this->dokumen($id, $dept)) || ! $this->bolehSusun($dept)) return $jawab(false, 'Akses ditolak.', 403);
        if ($d['status'] !== 'draf') return $jawab(false, 'RAB ini sudah diajukan — buat revisi dulu.', 409);

        [$bulan, $mall] = $this->tab($d);
        $muat = json_decode((string) $this->request->getPost('isi'), true);
        if (! is_array($muat['program'] ?? null)) return $jawab(false, 'Isi lembar kerja tidak terbaca.', 400);

        $posSah  = array_map('intval', array_column(array_filter((new BudgetPosModel())->untukEvent(), fn ($p) => (int) $p['dept_id'] === (int) $dept['id']), 'id'));
        $lokSah  = array_map('intval', array_column((new EventLocationModel())->getByMall($mall), 'id'));
        $terkunci = (new RabDokumenModel())->itemTerkunci();
        [$program, $itemsLama] = $this->isiTab($bulan, $mall);
        $lamaById = array_column($itemsLama, null, 'id');
        $userId = (int) session()->get('user_id');

        $angka = fn ($v) => ($v = trim(str_replace(',', '.', (string) $v))) === '' || ! is_numeric($v) ? null : round((float) $v, 2);
        $rupiah = fn ($v) => ($v = preg_replace('/[^\d]/', '', (string) $v)) === '' ? null : (int) $v;
        $teks = fn ($v, int $max) => mb_substr(trim((string) $v), 0, $max);
        // Sama secara nilai: "1.00" (DB) = 1.0 (isian), null = ''.
        $sama = fn ($a, $b) => is_numeric($a) && is_numeric($b) ? (float) $a == (float) $b : (string) $a === (string) $b;
        $tgl = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) && strtotime((string) $v) ? (string) $v : null;

        // ── Periksa dulu seluruh isi sebelum menulis apa pun ──
        $rencana = []; $dipakai = [];
        foreach ($muat['program'] as $n => $p) {
            $no = 'Program ' . ($n + 1);
            $evId = (int) ($p['event_id'] ?? 0);
            if ($evId && ! isset($program[$evId])) return $jawab(false, "$no: event tidak ditemukan di tab ini — muat ulang halaman.", 409);
            $nama = $teks($p['nama'] ?? '', 200);
            if (mb_strlen($nama) < 3) return $jawab(false, "$no: nama program minimal 3 huruf.", 422);
            $mulai = $tgl($p['mulai'] ?? ''); $selesai = $tgl($p['selesai'] ?? '');
            if (! $mulai || ! $selesai || $selesai < $mulai) return $jawab(false, "$nama: tanggal mulai/selesai belum benar.", 422);
            $header = [
                'name' => $nama, 'jenis' => ($p['jenis'] ?? '') === 'rutin' ? 'rutin' : 'event',
                'pos_id' => in_array((int) ($p['pos_id'] ?? 0), $posSah, true) ? (int) $p['pos_id'] : null,
                'start_date' => $mulai, 'event_days' => (int) round((strtotime($selesai) - strtotime($mulai)) / 86400) + 1,
            ];
            $lokasi = array_values(array_intersect(array_map('intval', (array) ($p['lokasi'] ?? [])), $lokSah));
            $ev = $evId ? $program[$evId] : ['start_date' => $mulai, 'mall' => $mall];
            if ($evId && ! $this->bolehUbahHeader($ev, $terkunci)) $header = null;      // header tetap; baris boleh
            $evAcuan = $header ? ['start_date' => $header['start_date']] + $ev : $ev;

            $rows = []; $kel = null; $urut = 0;
            foreach ((array) ($p['rows'] ?? []) as $r) {
                if (($r['jenis'] ?? '') === 'kel') { $kel = $teks($r['nama'] ?? '', 100) ?: null; continue; }
                $rid = (int) ($r['id'] ?? 0);
                if ($rid && ! isset($lamaById[$rid])) return $jawab(false, "$nama: ada baris yang sudah berubah di tempat lain — muat ulang halaman.", 409);
                if ($rid && (int) $lamaById[$rid]['event_id'] !== ($evId ?: -1)) return $jawab(false, "$nama: baris tidak boleh dipindah ke program lain.", 422);
                if ($rid) $dipakai[$rid] = true;
                if ($rid && isset($terkunci[$rid])) { $urut++; continue; }               // terkunci RAB aktif: tidak diubah
                $rnama = $teks($r['nama'] ?? '', 255);
                if ($rnama === '') return $jawab(false, "$nama: ada baris biaya tanpa rincian.", 422);
                $f = [
                    'nama' => $rnama, 'kelompok' => $kel,
                    'qty' => $angka($r['qty'] ?? ''), 'satuan' => $teks($r['satuan'] ?? '', 30) ?: null,
                    'freq' => $angka($r['freq'] ?? ''), 'satuan_freq' => $teks($r['satuan_freq'] ?? '', 30) ?: null,
                    'harga_satuan' => $rupiah($r['harga'] ?? ''),
                    'pos_id' => in_array((int) ($r['pos_id'] ?? 0), $posSah, true) ? (int) $r['pos_id'] : null,
                    'keterangan' => $teks($r['keterangan'] ?? '', 2000) ?: null,
                    'tanggal' => RabSusun::tanggalBarisTab($r['tanggal'] ?? null, $evAcuan, $bulan),
                    'mall' => ($ev['mall'] ?? '') === $mall ? null : $mall,
                    'urutan' => ++$urut,
                ];
                $f['budget'] = $f['harga_satuan'] !== null ? (int) RabSusun::totalItem($f) : (int) ($rid ? $lamaById[$rid]['budget'] : 0);
                $rows[] = ['id' => $rid, 'f' => $f];
            }
            $rencana[] = ['event_id' => $evId, 'header' => $header, 'lokasi' => $lokasi, 'rows' => $rows];
        }

        // Hanya baris yang DIMUAT di halaman penyimpan lalu dibuang yang dihapus. Baris yang
        // ditambahkan orang lain sesudah halaman ini dibuka tidak dikenalnya → dibiarkan.
        $dimuat = array_flip(array_map('intval', (array) ($muat['dimuat'] ?? [])));
        $hapus = [];
        foreach ($itemsLama as $it) {
            $iid = (int) $it['id'];
            if (isset($dipakai[$iid]) || ! isset($dimuat[$iid])) continue;
            if (isset($terkunci[$iid])) return $jawab(false, '"' . $it['nama'] . '" tercantum di ' . $terkunci[$iid] . ' — hapus lewat revisi RAB itu.', 409);
            $hapus[$iid] = $it;
        }
        if ($hapus && ($r = (new EventContentRealisasiModel())->whereIn('content_item_id', array_keys($hapus))->first())) {
            return $jawab(false, '"' . $hapus[(int) $r['content_item_id']]['nama'] . '" sudah punya realisasi — tidak bisa dihapus dari RAB.', 409);
        }

        // ── Tulis ──
        $db = db_connect();
        $evModel = new EventModel(); $itModel = new EventContentItemModel(); $rundown = new EventRundownModel(); $lokModel = new EventLocationModel();
        $baru = []; $n = ['tambah' => 0, 'ubah' => 0, 'hapus' => 0];
        $db->transStart();
        foreach ($rencana as $p) {
            $evId = $p['event_id'];
            if (! $evId) {
                $evId = (int) $evModel->insert($p['header'] + ['mall' => $mall, 'status' => 'draft', 'created_by' => $userId]);
                $baru[$evId] = $p['header'];
                if ($p['lokasi']) $lokModel->syncEventLocations($evId, $p['lokasi']);
            } elseif ($p['header']) {
                $lama = $program[$evId];
                $beda = array_filter($p['header'], fn ($v, $k) => ! $sama($v, $lama[$k] ?? null), ARRAY_FILTER_USE_BOTH);
                if ($beda) {
                    $evModel->update($evId, $beda);
                    ActivityLog::write('update', 'event', (string) $evId, $p['header']['name'], ['dari' => 'Susun RAB', 'berubah' => array_keys($beda)]);
                }
                if ($p['lokasi'] != $lama['_lokasi']) $lokModel->syncEventLocations($evId, $p['lokasi']);
            }
            $evNow = $evModel->find($evId);
            foreach ($p['rows'] as $r) {
                if (! $r['id']) {
                    $iid = $itModel->insert($r['f'] + ['event_id' => $evId, 'tipe' => 'biaya', 'created_by' => $userId]);
                    ActivityLog::write('create', 'content', (string) $iid, $r['f']['nama'], ['event_id' => $evId, 'dari' => 'Susun RAB', 'budget' => $r['f']['budget']]);
                    $n['tambah']++;
                    continue;
                }
                $lama = $lamaById[$r['id']];
                $beda = array_filter($r['f'], fn ($v, $k) => ! $sama($v, $lama[$k] ?? null), ARRAY_FILTER_USE_BOTH);
                if (! $beda) continue;
                $itModel->update($r['id'], $beda);
                ActivityLog::write('update', 'content', (string) $r['id'], $r['f']['nama'], ['event_id' => $evId, 'dari' => 'Susun RAB', 'berubah' => array_keys($beda)]);
                $n['ubah']++;
                if (($lama['tipe'] ?? 'program') === 'program') {
                    $item = $itModel->find($r['id']);
                    $item['tanggal'] ? $rundown->syncFromContentItem($item, $evId, strtotime($evNow['start_date'])) : $rundown->deleteByContentItem($r['id']);
                }
            }
        }
        foreach ($hapus as $iid => $it) {
            $rundown->deleteByContentItem($iid);
            $itModel->delete($iid);
            ActivityLog::write('delete', 'content', (string) $iid, $it['nama'], ['event_id' => (int) $it['event_id'], 'dari' => 'Susun RAB']);
            $n['hapus']++;
        }
        $db->transComplete();
        if (! $db->transStatus()) return $jawab(false, 'Gagal menyimpan — tidak ada yang berubah. Coba lagi.', 500);

        foreach ($baru as $evId => $h) {
            // Kegiatan rutin tidak butuh paket Creative (poster dsb.) seperti event.
            if ($h['jenis'] === 'event') (new \App\Services\CreativePaketService())->tambahkan('event', $evId, $h['name'], $userId);
            \App\Libraries\Notify::send(
                \App\Libraries\OrgRecipients::merge(\App\Libraries\OrgRecipients::withRolePerm('can_approve_events'), \App\Libraries\OrgRecipients::menuApprovers('events')),
                $userId, 'event', 'approval', 'Event menunggu persetujuan: ' . $h['name'],
                ($this->currentUser()['name'] ?? '') . ' · ' . ucfirst($mall) . ' · dari RAB ' . RabSusun::labelPeriode($d['periode_mulai'], $d['periode_selesai']),
                'event', $evId, 'events'
            );
            ActivityLog::write('create', 'event', (string) $evId, $h['name'], ['mall' => $mall, 'start_date' => $h['start_date'], 'dari' => 'Susun RAB']);
        }

        $pesan = 'Tersimpan: ' . count($baru) . ' program baru, ' . $n['tambah'] . ' baris baru, ' . $n['ubah'] . ' diubah, ' . $n['hapus'] . ' dihapus.';
        return $jawab(true, $pesan);
    }
}
