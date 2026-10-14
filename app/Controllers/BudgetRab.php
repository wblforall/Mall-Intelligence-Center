<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\RabSusun;
use App\Models\BudgetDeptSettingModel;
use App\Models\BudgetPosModel;
use App\Models\RabDokumenModel;
use App\Models\RabLaporanModel;

/**
 * RAB (Rencana Anggaran Biaya) — tab di Budget Departemen untuk dept yang
 * terhubung ke modul Events (budget_dept_setting.event_mulai). Isi RAB = item
 * Event Content di periode itu; RAB sendiri hanya dokumen pembungkus:
 *
 *   draf (pratinjau hidup) → Ajukan (isi dibekukan, PDF dicetak untuk e-Sign)
 *   → admin E&P unggah PDF bertanda tangan (hak menu 'rab' tingkat menyetujui)
 *   → Disetujui. Perubahan = Revisi (versi baru). Laporan realisasi akhir bulan
 *   memakai alur yang sama (→ Disahkan, realisasi bulan itu terkunci).
 *
 * Keputusan 14 Okt 2026 — lihat app/Libraries/RabSusun.php untuk aturannya.
 */
class BudgetRab extends BudgetDept
{
    const MENU_RAB = 'rab';
    const DIR      = WRITEPATH . 'uploads/rab/';

    /** Dept yang dibuka, hanya bila terhubung ke Events. */
    private function deptRab(): ?array
    {
        if (! $this->canViewMenu(self::MENU)) return null;
        $dept = $this->dept();
        return $dept && (new BudgetDeptSettingModel())->eventMulai((int) $dept['id']) ? $dept : null;
    }

    /** Admin E&P: boleh mengunggah PDF bertanda tangan (menyetujui RAB / mengesahkan laporan). */
    private function bolehSetujui(): bool
    {
        return $this->isAdmin() || $this->canApproveMenu(self::MENU_RAB);
    }

    /** Nomor dokumen sama? Toleran tanda pisah (– — -), spasi, dan huruf besar/kecil — orang mengetik "-" biasa. */
    private static function nomorSama(string $ketik, string $nomor): bool
    {
        $norm = fn (string $x) => strtoupper(preg_replace('/\s+/u', '', str_replace(['–', '—', '−'], '-', $x)));
        return $norm($ketik) === $norm($nomor);
    }

    private function ym(?string $v): ?string
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $v) ? $v : null;
    }

    // ── Pengumpul data ───────────────────────────────────────────────

    /** Bahan snapshot periode ini dari data hidup. */
    private function kumpul(array $dept, string $mulai, string $selesai): array
    {
        $db    = db_connect();
        $awal  = $mulai . '-01';
        $akhir = date('Y-m-t', strtotime($selesai . '-01'));

        // Event yang bersinggungan dengan periode, atau punya item bertanggal di periode.
        $events = $db->query(
            "SELECT e.id, e.name, e.jenis, e.pos_id, e.mall, e.start_date, e.event_days,
                    (SELECT GROUP_CONCAT(l.nama ORDER BY l.nama SEPARATOR ', ')
                       FROM event_location_map m JOIN event_locations l ON l.id = m.location_id WHERE m.event_id = e.id) AS lokasi
               FROM events e
              WHERE (e.start_date <= ? AND DATE_ADD(e.start_date, INTERVAL (e.event_days - 1) DAY) >= ?)
                 OR e.id IN (SELECT event_id FROM event_content_items WHERE tanggal BETWEEN ? AND ?)
              ORDER BY e.start_date, e.id",
            [$akhir, $awal, $awal, $akhir]
        )->getResultArray();
        $items = $events ? $db->table('event_content_items')->whereIn('event_id', array_column($events, 'id'))
            ->orderBy('urutan')->orderBy('id')->get()->getResultArray() : [];

        $tahun  = range((int) substr($mulai, 0, 4), (int) substr($selesai, 0, 4));
        $budget = $db->table('budget_rencana r')->select('r.pos_id, r.tahun, r.bulan, r.mall, r.jumlah')
            ->join('budget_pos p', 'p.id = r.pos_id')->where('p.dept_id', (int) $dept['id'])->whereIn('r.tahun', $tahun)
            ->get()->getResultArray();

        return [(new BudgetPosModel())->milikDept((int) $dept['id']), $budget, $events, $items];
    }

    private function snapshotHidup(array $dept, string $mulai, string $selesai): array
    {
        return RabSusun::snapshot(...[...$this->kumpul($dept, $mulai, $selesai), $mulai, $selesai]);
    }

    /** Nama & jabatan penanda tangan: Manager dept → Deputy GM divisinya → GM. */
    private function penandaTangan(array $dept): array
    {
        $q = fn (string $where, array $p) => db_connect()->query(
            "SELECT e.nama, j.nama AS jabatan FROM employees e JOIN jabatans j ON j.id = e.jabatan_id
              WHERE e.status = 'aktif' AND $where ORDER BY j.grade, e.id LIMIT 1", $p)->getRowArray();
        return [
            'Diajukan oleh'  => $q("e.dept_id = ? AND j.nama LIKE 'Manager%'", [(int) $dept['id']]) ?? ['nama' => '', 'jabatan' => 'Manager ' . $dept['name']],
            'Diketahui oleh' => $q("j.grade = 3 AND e.division_id = ?", [(int) ($dept['division_id'] ?? 0)]) ?? ['nama' => '', 'jabatan' => 'Deputy GM'],
            'Disetujui oleh' => $q("LOWER(j.nama) LIKE '%general manager%'", []) ?? ['nama' => '', 'jabatan' => 'General Manager'],
        ];
    }

    private function dokumen(int $id, array $dept): ?array
    {
        $d = (new RabDokumenModel())->find($id);
        return $d && (int) $d['dept_id'] === (int) $dept['id'] ? $d : null;
    }

    /** Snapshot versi yang disetujui sebelum dokumen ini (pembanding revisi). */
    private function snapshotSebelumnya(array $d): ?array
    {
        $rantai = (new RabDokumenModel())->rantai((int) ($d['induk_id'] ?: $d['id']));
        foreach ($rantai as $r) {
            if ((int) $r['versi'] < (int) $d['versi'] && in_array($r['status'], ['disetujui', 'digantikan'], true)) {
                return json_decode((string) $r['snapshot'], true) ?: null;
            }
        }
        return null;
    }

    // ── Halaman ──────────────────────────────────────────────────────

    public function index()
    {
        $dept = $this->deptRab();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);

        // Rantai versi: dokumen terbaru per rantai di atas, versi lama di bawahnya.
        $semua  = (new RabDokumenModel())->milikDept($deptId);
        $rantai = [];
        foreach ($semua as $d) $rantai[(int) ($d['induk_id'] ?: $d['id'])][] = $d;

        // Realisasi event yang belum punya pos — tak masuk Budget Departemen.
        $mulai = (new BudgetDeptSettingModel())->eventMulai($deptId);
        $tanpaPos = db_connect()->query(
            "SELECT e.id, e.name, COUNT(*) n, SUM(r.nilai) nilai
               FROM event_content_realisasi r JOIN event_content_items i ON i.id = r.content_item_id JOIN events e ON e.id = r.event_id
              WHERE COALESCE(i.pos_id, e.pos_id) IS NULL AND DATE_FORMAT(COALESCE(i.tanggal, e.start_date), '%Y-%m') >= ?
              GROUP BY e.id ORDER BY e.start_date", [$mulai]
        )->getResultArray();

        return view('budget_dept/rab', $this->konteks($dept, $tahun, 'rab') + [
            'rantai'       => $rantai,
            'laporan'      => (new RabLaporanModel())->milikDept($deptId),
            'tanpaPos'     => $tanpaPos,
            'bolehSetujui' => $this->bolehSetujui(),
            'url'          => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    public function buat()
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehUbah($dept)) return $this->tolak();
        $mulai   = $this->ym($this->request->getPost('mulai'));
        $selesai = $this->ym($this->request->getPost('selesai'));
        $back    = redirect()->to($this->url('rab', $dept));
        if (! $mulai || ! $selesai || $selesai < $mulai) return $back->with('error', 'Periode tidak valid.');
        if (count(RabSusun::daftarBulan($mulai, $selesai)) > 3) return $back->with('error', 'Periode RAB paling lama 3 bulan.');

        $model = new RabDokumenModel();
        if ($b = $model->bentrok((int) $dept['id'], $mulai, $selesai)) {
            return $back->with('error', 'Periode ini bersinggungan dengan RAB ' . ($b['nomor'] ?: RabSusun::labelPeriode($b['periode_mulai'], $b['periode_selesai']))
                . ' — buka RAB itu lalu buat revisi.');
        }
        $id = $model->insert([
            'dept_id' => (int) $dept['id'], 'versi' => 1, 'periode_mulai' => $mulai, 'periode_selesai' => $selesai,
            'status' => 'draf', 'dibuat_by' => (int) session()->get('user_id'),
        ]);
        ActivityLog::write('create', 'rab', (string) $id, 'Draf RAB ' . RabSusun::labelPeriode($mulai, $selesai) . ' (' . $dept['name'] . ')');
        return redirect()->to($this->url('rab/' . $id, $dept));
    }

    public function lihat(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();

        $snap  = $d['status'] === 'draf' ? $this->snapshotHidup($dept, $d['periode_mulai'], $d['periode_selesai'])
                                         : json_decode((string) $d['snapshot'], true);
        $lama  = $this->snapshotSebelumnya($d);
        $rantai = (new RabDokumenModel())->rantai((int) ($d['induk_id'] ?: $d['id']));

        return view('budget_dept/rab_lihat', $this->konteks($dept, $this->tahun($dept), 'rab') + [
            'd'            => $d,
            'snap'         => $snap,
            'ringkas'      => RabSusun::ringkasan($snap),
            'periksa'      => RabSusun::periksa($snap),
            'beda'         => RabSusun::beda($lama, $snap),
            'adaPembanding'=> $lama !== null,
            'rantai'       => $rantai,
            'terbaru'      => (int) $rantai[0]['id'] === (int) $d['id'],
            'bolehSetujui' => $this->bolehSetujui(),
            'url'          => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    public function cetak(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        $snap = $d['status'] === 'draf' ? $this->snapshotHidup($dept, $d['periode_mulai'], $d['periode_selesai'])
                                        : json_decode((string) $d['snapshot'], true);
        return view('budget_dept/rab_cetak', [
            'd'       => $d,
            'dept'    => $dept,
            'snap'    => $snap,
            'ringkas' => RabSusun::ringkasan($snap),
            'beda'    => RabSusun::beda($this->snapshotSebelumnya($d), $snap),
            'ttd'     => $this->penandaTangan($dept),
            'kembali' => $this->url('rab/' . $id, $dept),
        ]);
    }

    public function ajukan(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehUbah($dept) || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        $back = redirect()->to($this->url('rab/' . $id, $dept));
        if ($d['status'] !== 'draf') return $back->with('error', 'Hanya draf yang bisa diajukan.');

        $snap = $this->snapshotHidup($dept, $d['periode_mulai'], $d['periode_selesai']);
        $cek  = RabSusun::periksa($snap);
        if ($cek['halang']) return $back->with('error', 'Belum bisa diajukan: ' . implode('; ', array_slice($cek['halang'], 0, 5)) . (count($cek['halang']) > 5 ? '; …' : ''));

        $nomor = RabDokumenModel::nomor($dept['name'], $d['periode_mulai'], $d['periode_selesai'], (int) $d['versi']);
        (new RabDokumenModel())->update($id, [
            'status' => 'diajukan', 'nomor' => $nomor, 'snapshot' => json_encode($snap, JSON_UNESCAPED_UNICODE),
            'diajukan_by' => (int) session()->get('user_id'), 'diajukan_at' => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::write('update', 'rab', (string) $id, 'Ajukan ' . $nomor, ['total' => RabSusun::ringkasan($snap)['total']['rab']]);
        return $back->with('success', $nomor . ' diajukan. Cetak PDF-nya, tanda tangani lewat PAM e-Sign, lalu admin E&P mengunggah berkas yang sudah lengkap tanda tangannya.');
    }

    public function revisi(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehUbah($dept) || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        $model  = new RabDokumenModel();
        $induk  = (int) ($d['induk_id'] ?: $d['id']);
        $rantai = $model->rantai($induk);
        $back   = redirect()->to($this->url('rab/' . $id, $dept));
        if ((int) $rantai[0]['id'] !== $id || ! in_array($d['status'], RabDokumenModel::AKTIF, true)) {
            return $back->with('error', 'Revisi dibuat dari versi terbaru yang sudah diajukan/disetujui.');
        }
        $alasan = trim((string) $this->request->getPost('catatan'));
        if ($alasan === '') return $back->with('error', 'Isi alasan revisi.');

        // Versi yang belum disetujui lalu direvisi → batal (versi lama yang disetujui tetap berlaku).
        if ($d['status'] === 'diajukan') $model->update($id, ['status' => 'batal']);
        $baru = $model->insert([
            'dept_id' => (int) $dept['id'], 'induk_id' => $induk, 'versi' => (int) $d['versi'] + 1,
            'periode_mulai' => $d['periode_mulai'], 'periode_selesai' => $d['periode_selesai'],
            'status' => 'draf', 'catatan' => $alasan, 'dibuat_by' => (int) session()->get('user_id'),
        ]);
        ActivityLog::write('create', 'rab', (string) $baru, 'Revisi ' . $d['nomor'] . ' → v' . ((int) $d['versi'] + 1), ['alasan' => $alasan]);
        return redirect()->to($this->url('rab/' . $baru, $dept))->with('success', 'Draf revisi dibuat — item event bisa diubah lagi. Setelah selesai, ajukan revisi ini.');
    }

    public function hapus(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehUbah($dept) || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        if ($d['status'] !== 'draf') return redirect()->to($this->url('rab/' . $id, $dept))->with('error', 'Hanya draf yang bisa dihapus.');
        (new RabDokumenModel())->delete($id);
        ActivityLog::write('delete', 'rab', (string) $id, 'Hapus draf RAB v' . $d['versi'] . ' ' . RabSusun::labelPeriode($d['periode_mulai'], $d['periode_selesai']));
        return redirect()->to($this->url('rab', $dept))->with('success', 'Draf dihapus.');
    }

    /** Simpan PDF bertanda tangan; null + pesan galat bila tidak sah. */
    private function simpanBerkas(string $awalan): array
    {
        $file = $this->request->getFile('berkas');
        if (! $file || ! $file->isValid()) return [null, 'Pilih berkas PDF yang sudah ditandatangani.'];
        if ($err = $this->validateUpload($file, ['application/pdf'], 20)) return [null, $err];
        if (! is_dir(self::DIR)) mkdir(self::DIR, 0755, true);
        $nama = $awalan . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $file->move(self::DIR, $nama);
        return [$nama, null];
    }

    public function unggah(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehSetujui() || ! ($d = $this->dokumen($id, $dept))) return $this->tolak();
        $back = redirect()->to($this->url('rab/' . $id, $dept));
        if ($d['status'] !== 'diajukan') return $back->with('error', 'Berkas bertanda tangan hanya untuk RAB berstatus diajukan.');
        if (! self::nomorSama((string) $this->request->getPost('nomor'), (string) $d['nomor'])) {
            return $back->with('error', 'Nomor dokumen yang diketik tidak sama dengan ' . $d['nomor'] . ' — pastikan berkas yang diunggah versi ini.');
        }
        [$nama, $err] = $this->simpanBerkas('rab' . $id);
        if ($err) return $back->with('error', $err);

        $model = new RabDokumenModel();
        $db = db_connect();
        $db->transStart();
        foreach ($model->rantai((int) ($d['induk_id'] ?: $d['id'])) as $r) {
            if ((int) $r['id'] !== $id && $r['status'] === 'disetujui') $model->update((int) $r['id'], ['status' => 'digantikan']);
        }
        $model->update($id, ['status' => 'disetujui', 'file_ttd' => $nama, 'disetujui_by' => (int) session()->get('user_id'), 'disetujui_at' => date('Y-m-d H:i:s')]);
        $db->transComplete();
        ActivityLog::write('approve', 'rab', (string) $id, 'Unggah TTD ' . $d['nomor'] . ' — disetujui');
        return $back->with('success', $d['nomor'] . ' disetujui.');
    }

    public function berkas(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($d = $this->dokumen($id, $dept)) || ! $d['file_ttd'] || ! is_file(self::DIR . $d['file_ttd'])) return $this->tolak();
        return $this->response->download(self::DIR . $d['file_ttd'], null, true)->setFileName(str_replace('/', '-', $d['nomor']) . ' (TTD).pdf')->inline();
    }

    // ── Laporan realisasi akhir bulan ────────────────────────────────

    private function snapshotLaporan(array $dept, string $bulan): array
    {
        $model = new RabDokumenModel();
        $rabD  = $model->berlaku((int) $dept['id'], $bulan);
        $rab   = $rabD ? json_decode((string) $rabD['snapshot'], true) : null;
        $live  = $this->snapshotHidup($dept, $bulan, $bulan);

        $ids  = array_unique(array_merge(array_column($live['items'], 'id'), array_column(array_filter($rab['items'] ?? [], fn ($x) => $x['bulan'] === $bulan), 'id')));
        $real = $ids ? array_column(db_connect()->table('event_content_realisasi')->select('content_item_id, SUM(nilai) AS n')
            ->whereIn('content_item_id', $ids)->groupBy('content_item_id')->get()->getResultArray(), 'n', 'content_item_id') : [];

        $lap = RabSusun::laporan($rab, $live['items'], array_map('floatval', $real), $bulan);
        $budget = [];
        foreach ($live['budget'] as $k => $v) { [, , $p] = explode('|', $k); $budget[(int) $p] = ($budget[(int) $p] ?? 0) + $v; }
        return [
            'bulan'  => $bulan,
            'rab'    => $rabD ? ['id' => (int) $rabD['id'], 'nomor' => $rabD['nomor']] : null,
            'pos'    => $live['pos'],
            'events' => ($rab['events'] ?? []) + $live['events'],
            'budget' => $budget,
            'lap'    => $lap,
        ];
    }

    public function laporanBuat()
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehUbah($dept)) return $this->tolak();
        $bulan = $this->ym($this->request->getPost('bulan'));
        $back  = redirect()->to($this->url('rab', $dept));
        if (! $bulan) return $back->with('error', 'Bulan tidak valid.');
        if ($bulan >= date('Y-m')) return $back->with('error', 'Laporan realisasi dibuat setelah bulannya selesai.');

        $model = new RabLaporanModel();
        if ($model->where('dept_id', (int) $dept['id'])->where('bulan', $bulan)->where('status', 'disahkan')->first()) {
            return $back->with('error', 'Laporan ' . RabSusun::labelBulan($bulan) . ' sudah disahkan.');
        }
        $snap = $this->snapshotLaporan($dept, $bulan);
        $model->where('dept_id', (int) $dept['id'])->where('bulan', $bulan)->where('status', 'diajukan')->set(['status' => 'batal'])->update();
        preg_match_all('/\b[A-Za-z]/', $dept['name'], $m);
        $id = $model->insert([
            'dept_id' => (int) $dept['id'], 'bulan' => $bulan, 'rab_id' => $snap['rab']['id'] ?? null, 'status' => 'diajukan',
            'nomor' => 'LRAB/' . strtoupper(implode('', $m[0])) . '/' . $bulan . '/' . date('ymdHi'),
            'snapshot' => json_encode($snap, JSON_UNESCAPED_UNICODE), 'dibuat_by' => (int) session()->get('user_id'),
        ]);
        ActivityLog::write('create', 'rab_laporan', (string) $id, 'Laporan realisasi RAB ' . RabSusun::labelBulan($bulan));
        return redirect()->to($this->url('rab/laporan/' . $id . '/cetak', $dept));
    }

    private function laporan(int $id, array $dept): ?array
    {
        $l = (new RabLaporanModel())->find($id);
        return $l && (int) $l['dept_id'] === (int) $dept['id'] && $l['status'] !== 'batal' ? $l : null;
    }

    public function laporanCetak(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($l = $this->laporan($id, $dept))) return $this->tolak();
        return view('budget_dept/rab_laporan_cetak', [
            'l'       => $l,
            'dept'    => $dept,
            's'       => json_decode((string) $l['snapshot'], true),
            'ttd'     => $this->penandaTangan($dept),
            'kembali' => $this->url('rab', $dept),
        ]);
    }

    public function laporanUnggah(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! $this->bolehSetujui() || ! ($l = $this->laporan($id, $dept))) return $this->tolak();
        $back = redirect()->to($this->url('rab', $dept));
        if ($l['status'] !== 'diajukan') return $back->with('error', 'Laporan ini sudah disahkan.');
        if (! self::nomorSama((string) $this->request->getPost('nomor'), (string) $l['nomor'])) return $back->with('error', 'Nomor dokumen tidak sama dengan ' . $l['nomor'] . '.');
        [$nama, $err] = $this->simpanBerkas('lap' . $id);
        if ($err) return $back->with('error', $err);
        (new RabLaporanModel())->update($id, ['status' => 'disahkan', 'file_ttd' => $nama, 'disahkan_by' => (int) session()->get('user_id'), 'disahkan_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('approve', 'rab_laporan', (string) $id, 'Laporan realisasi ' . RabSusun::labelBulan($l['bulan']) . ' disahkan');
        return $back->with('success', 'Laporan ' . RabSusun::labelBulan($l['bulan']) . ' disahkan — realisasi bulan itu kini terkunci.');
    }

    public function laporanBerkas(int $id)
    {
        $dept = $this->deptRab();
        if (! $dept || ! ($l = $this->laporan($id, $dept)) || ! $l['file_ttd'] || ! is_file(self::DIR . $l['file_ttd'])) return $this->tolak();
        return $this->response->download(self::DIR . $l['file_ttd'], null, true)->setFileName(str_replace('/', '-', $l['nomor']) . ' (TTD).pdf')->inline();
    }
}
