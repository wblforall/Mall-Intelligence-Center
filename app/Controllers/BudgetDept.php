<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\BudgetEkspor;
use App\Libraries\BudgetRekap;
use App\Libraries\BudgetXlsx;
use App\Models\BudgetDeptSettingModel;
use App\Models\BudgetPosModel;
use App\Models\BudgetRealisasiModel;
use App\Models\BudgetRencanaModel;
use App\Models\DepartmentModel;

/**
 * Budget Departemen — catatan internal budget & realisasi per departemen.
 *
 * Siapa melihat apa:
 * - staf / dept head : departemennya sendiri (lihat & isi)
 * - Deputy GM        : semua dept di divisinya (lihat saja) — Deputy tak punya dept
 * - GM               : semua dept (lihat saja)
 * - admin            : semua dept (lihat & isi)
 * Mengisi selalu hanya untuk dept sendiri (atau admin): ini buku catatan
 * dept, bukan anggaran resmi (itu rencana modul Keuangan WBL One).
 * Tetap butuh hak menu budget_dept — Deputy/GM tanpa dept memakai grant per orang.
 */
class BudgetDept extends BaseController
{
    const MENU = 'budget_dept';

    /**
     * Tahun pertama BAWAAN yang dicatat di modul ini. Modul dipakai mulai menyusun
     * budget 2027 (keputusan 12 Okt 2026); tahun sebelumnya tidak dibuka agar tak
     * ada angka yang nyasar ke tahun berjalan. Bisa dimajukan per dept lewat
     * budget_dept_setting.tahun_mulai (mis. CCD melanjutkan 2026 dari Excel).
     */
    const TAHUN_MULAI = 2027;

    /** Tahun mulai untuk dept ini (pengaturan dept, atau bawaan). */
    private function tahunMulai(array $dept): int
    {
        return (new BudgetDeptSettingModel())->tahunMulai((int) $dept['id'], self::TAHUN_MULAI);
    }

    private ?array $_terlihat = null;

    /** [dept_id => dept] yang boleh DILIHAT pengguna ini, urut divisi lalu nama. */
    private function deptTerlihat(): array
    {
        if ($this->_terlihat !== null) return $this->_terlihat;
        $semua = array_column((new DepartmentModel())->selectable(), null, 'id');

        if ($this->isAdmin()) return $this->_terlihat = $semua;

        $userId = (int) session()->get('user_id');
        $emp = db_connect()->table('employees e')
            ->select('e.division_id, j.grade, j.nama AS jabatan')
            ->join('jabatans j', 'j.id = e.jabatan_id', 'left')
            ->where('e.user_id', $userId)->where('e.status', 'aktif')
            ->get()->getRowArray();

        $out = [];
        if ($emp && str_contains(strtolower((string) $emp['jabatan']), 'general manager')) {
            $out = $semua;                                          // GM: lintas divisi
        } elseif ($emp && (int) $emp['grade'] === 3 && ! empty($emp['division_id'])) {
            foreach ($semua as $id => $d) {                         // Deputy: satu divisi
                if ((int) $d['division_id'] === (int) $emp['division_id']) $out[$id] = $d;
            }
        }
        $sendiri = (int) session()->get('dept_id');
        if ($sendiri && isset($semua[$sendiri])) $out = [$sendiri => $semua[$sendiri]] + $out;

        return $this->_terlihat = $out;
    }

    /** Departemen yang sedang dibuka — ?dept= bila termasuk yang boleh dilihat, selain itu dept sendiri. */
    private function dept(): ?array
    {
        $daftar = $this->deptTerlihat();
        $id = (int) $this->request->getGetPost('dept');
        if ($id && isset($daftar[$id])) return $daftar[$id];
        $sendiri = (int) session()->get('dept_id');
        if ($sendiri && isset($daftar[$sendiri])) return $daftar[$sendiri];
        return $daftar ? reset($daftar) : null;
    }

    /** Boleh mengisi dept ini? Hanya dept sendiri, atau admin. */
    private function bolehUbah(array $dept): bool
    {
        if (! $this->canEditMenu(self::MENU)) return false;
        return $this->isAdmin() || (int) $dept['id'] === (int) session()->get('dept_id');
    }

    /** Dept untuk aksi tulis — null bila tak boleh mengisinya. */
    private function deptUntukUbah(): ?array
    {
        $dept = $this->dept();
        return $dept && $this->bolehUbah($dept) ? $dept : null;
    }

    private function tolak()
    {
        return redirect()->to('/')->with('error', 'Akses ditolak.');
    }

    /** Data bersama header semua halaman (pilihan dept & tahun). */
    private function konteks(array $dept, int $tahun, string $tab): array
    {
        return [
            'user'       => $this->currentUser(),
            'dept'       => $dept,
            'tahun'      => $tahun,
            'tab'        => $tab,
            'isAdmin'    => $this->isAdmin(),
            'canEdit'    => $this->bolehUbah($dept),
            'depts'      => count($this->deptTerlihat()) > 1 ? array_values($this->deptTerlihat()) : [],
            'deptSendiri' => (int) session()->get('dept_id'),
            'years'      => (new BudgetRencanaModel())->tahunTersedia((int) $dept['id'], $this->tahunMulai($dept)),
            'tahunMulai' => $this->tahunMulai($dept),
            'pisahMal'   => (new BudgetDeptSettingModel())->pisahMal((int) $dept['id']),
        ];
    }

    /**
     * Tahun yang dibuka. Tanpa ?tahun= dipakai tahun TERAKHIR dibuka pengguna
     * (cookie, setahun) — keputusan 13 Okt 2026: tahun tidak boleh pindah
     * sendiri ke tahun berjalan. Tahun berjalan hanya cadangan.
     */
    private function tahun(array $dept): int
    {
        helper('cookie');
        $mulai  = $this->tahunMulai($dept);
        $bawaan = max((int) date('Y'), $mulai);
        $sah    = fn (int $t) => $t >= $mulai && $t <= 2100;

        $diminta = (int) $this->request->getGetPost('tahun');
        if ($sah($diminta)) {
            set_cookie('budget_tahun', (string) $diminta, 365 * 86400);
            return $diminta;
        }
        $terakhir = (int) get_cookie('budget_tahun');
        return $sah($terakhir) ? $terakhir : $bawaan;
    }

    private function url(string $path, array $dept, array $q = []): string
    {
        if (count($this->deptTerlihat()) > 1) $q = ['dept' => $dept['id']] + $q;
        return base_url('budget-dept' . ($path ? '/' . $path : '')) . ($q ? '?' . http_build_query($q) : '');
    }

    // ── Rekap ────────────────────────────────────────────────────────

    public function index()
    {
        if (! $this->canViewMenu(self::MENU)) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);

        $ctx = $this->konteks($dept, $tahun, 'rekap');
        $mal = $this->request->getGet('mal');
        if (! $ctx['pisahMal'] || ! isset(BudgetRekap::MAL[$mal])) $mal = 'semua';

        // "s.d. bulan" bawaan: bulan berjalan untuk tahun ini, Desember untuk tahun lalu.
        $sd = (int) $this->request->getGet('sd');
        if ($sd < 1 || $sd > 12) {
            $sd = $tahun < (int) date('Y') ? 12 : ($tahun > (int) date('Y') ? 12 : (int) date('n'));
        }

        $tampil = $this->request->getGet('tampil') === 'bulanan' ? 'bulanan' : 'ringkas';
        // Per Bulan pada dept yang memisah mal: eWalk & Pentacity berdampingan
        // tiap bulan (seperti Excel), jadi tak ada pilihan mal — gabungan + per mal.
        if ($tampil === 'bulanan' && $ctx['pisahMal']) $mal = 'semua';

        $realModel = new BudgetRealisasiModel();
        $data = [
            (new BudgetPosModel())->milikDept($deptId),
            (new BudgetRencanaModel())->milikDept($deptId, $tahun),
            $realModel->untukRekap($deptId, $tahun),
            $realModel->totalTahun($deptId, $tahun - 1),
        ];
        $budgetLalu = (new BudgetRencanaModel())->milikDept($deptId, $tahun - 1);
        $rekap  = BudgetRekap::susun(...$data, ...[$mal, $sd, $budgetLalu]);
        $perMal = [];
        if ($tampil === 'bulanan' && $ctx['pisahMal']) {
            foreach (array_keys(BudgetRekap::MAL) as $k) $perMal[$k] = BudgetRekap::susun(...$data, ...[$k, $sd, $budgetLalu]);
        }

        return view('budget_dept/index', $ctx + [
            'rekap'  => $rekap,
            'perMal' => $perMal,
            'mal'    => $mal,
            'sd'     => $sd,
            'tampil' => $tampil,
            'url'    => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    /**
     * Unduh Excel satu dept satu tahun: Rekap, Per Bulan (keterangan rincian
     * jadi komentar sel), Rincian Budget, Realisasi. Siapa pun yang boleh
     * melihat dept ini boleh mengunduh.
     */
    public function excel()
    {
        if (! $this->canViewMenu(self::MENU)) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);

        $realModel = new BudgetRealisasiModel();
        $data = [
            (new BudgetPosModel())->milikDept($deptId),
            $rencana = (new BudgetRencanaModel())->milikDept($deptId, $tahun),
            $realModel->untukRekap($deptId, $tahun),
            $realModel->totalTahun($deptId, $tahun - 1),
        ];
        $budgetLalu = (new BudgetRencanaModel())->milikDept($deptId, $tahun - 1);
        $rekap  = BudgetRekap::susun(...$data, ...['semua', 12, $budgetLalu]);
        $perMal = null;
        if ((new BudgetDeptSettingModel())->pisahMal($deptId)) {
            foreach (array_keys(BudgetRekap::MAL) as $k) $perMal[$k] = BudgetRekap::susun(...$data, ...[$k, 12, $budgetLalu]);
        }
        $realisasi = array_reverse($realModel->daftar($deptId, "$tahun-01-01", "$tahun-12-31", null, ''));

        $info = ['dept' => $dept['name'], 'tahun' => $tahun, 'oleh' => (string) session()->get('user_name'), 'waktu' => date('d/m/Y H:i')];
        $nama = 'Budget ' . preg_replace('/[^A-Za-z0-9 &_-]/', '', $dept['name']) . ' ' . $tahun . '.xlsx';
        ActivityLog::write('export', 'budget_dept', (string) $deptId, 'Unduh Excel budget ' . $dept['name'] . ' ' . $tahun);
        BudgetXlsx::download($nama, BudgetEkspor::lembar($info, $rekap, $perMal, $rencana, $realisasi));
    }

    // ── Rencana (isi budget) ─────────────────────────────────────────

    public function rencana()
    {
        if (! $this->canViewMenu(self::MENU)) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);
        $ctx    = $this->konteks($dept, $tahun, 'rencana');

        // grid[pos_id][mallKey][bulan] = [{k: keterangan, j: nilai}, ...] — mallKey 'x' = tanpa mal.
        $grid = [];
        foreach ((new BudgetRencanaModel())->milikDept($deptId, $tahun) as $r) {
            $grid[(int) $r['pos_id']][$r['mall'] === '' ? 'x' : $r['mall']][(int) $r['bulan']][] =
                ['k' => (string) ($r['keterangan'] ?? ''), 'j' => (float) $r['jumlah']];
        }

        $adaTahunLalu = $tahun - 1 >= $this->tahunMulai($dept) && (new BudgetRencanaModel())->milikDept($deptId, $tahun - 1) !== [];

        // Siapa terakhir menyimpan budget dept ini — membantu saat beberapa orang mengisi.
        $terakhir = db_connect()->table('activity_logs')
            ->select('user_name, created_at')
            ->where('module', 'budget_rencana')->where('target_label', 'Budget ' . $dept['name'] . ' ' . $tahun)
            ->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();

        return view('budget_dept/rencana', $ctx + [
            'terakhir'     => $terakhir,
            'pos'          => (new BudgetPosModel())->milikDept($deptId),
            'grid'         => $grid,
            'adaTahunLalu' => $adaTahunLalu,
            'url'          => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    public function simpanRencana()
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);

        $posSah = array_column((new BudgetPosModel())->milikDept($deptId), 'nama', 'id');
        $model  = new BudgetRencanaModel();
        $now    = date('Y-m-d H:i:s');

        $sebelum = $this->petaRencana($model->milikDept($deptId, $tahun), $posSah);

        // Seluruh grid dikirim sebagai SATU field JSON — bukan satu input per
        // sel — agar dept dengan banyak pos × 2 mal × 12 bulan tidak menabrak
        // max_input_vars PHP (1000) dan kehilangan sel tanpa pesan.
        $isian = json_decode((string) $this->request->getPost('data'), true);
        if (! is_array($isian)) {
            return redirect()->to($this->url('rencana', $dept, ['tahun' => $tahun]))->with('error', 'Isian budget tidak terbaca, tidak ada yang disimpan.');
        }

        // Sel tersimpan sekarang, dikelompokkan sama seperti isian → hanya sel
        // yang berubah yang ditulis ulang.
        $lama = [];
        foreach ($model->milikDept($deptId, $tahun) as $r) {
            $lama[(int) $r['pos_id']][$r['mall']][(int) $r['bulan']][] =
                ['keterangan' => $r['keterangan'] ?? null, 'jumlah' => round((float) $r['jumlah'], 2)];
        }

        // Beberapa orang bisa mengisi bersamaan. Klien mengirim HANYA sel yang ia
        // ubah, masing-masing {baru, asli} (asli = isi sel saat halamannya dibuka).
        // Sel yang sudah diubah orang lain sejak itu TIDAK ditimpa — dilaporkan
        // sebagai bentrok. (12 Okt 2026: halaman lama Nancy menimpa isian Affan.)
        // Halaman yang dibuka sebelum pembaruan ini mengirim SELURUH grid tanpa info
        // "asli" — justru pola yang menimpa isian orang lain. Tolak, minta muat ulang.
        foreach ($isian as $perMal) {
            foreach ((array) $perMal as $perBulan) {
                foreach ((array) $perBulan as $sel) {
                    if (is_array($sel) && ! array_key_exists('baru', $sel)) {
                        return redirect()->to($this->url('rencana', $dept, ['tahun' => $tahun]))->with('error',
                            'Halaman Anda versi lama dan bisa menimpa isian orang lain, jadi TIDAK disimpan. Halaman sudah dimuat ulang dengan angka terbaru — silakan isi ulang perubahan Anda.');
                    }
                    break 3;
                }
            }
        }

        $BLN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $ditulis = 0;
        $bentrok = [];

        $db = db_connect();
        $db->transStart();
        foreach ($isian as $posId => $perMal) {
            if (! isset($posSah[(int) $posId]) || ! is_array($perMal)) continue;
            foreach ($perMal as $malKey => $perBulan) {
                $mall = $malKey === 'x' ? '' : (string) $malKey;
                if ($mall !== '' && ! isset(BudgetRekap::MAL[$mall])) continue;
                if (! is_array($perBulan)) continue;
                foreach ($perBulan as $bulan => $sel) {
                    $bulan = (int) $bulan;
                    if ($bulan < 1 || $bulan > 12 || ! is_array($sel)) continue;
                    // Format baru {baru, asli}; format lama (daftar rincian) dari halaman
                    // yang dibuka sebelum pembaruan ini → tanpa info asli.
                    $formatBaru = array_key_exists('baru', $sel);
                    $baru = BudgetRekap::rincianSel($formatBaru ? $sel['baru'] : $sel);
                    $asli = $formatBaru ? BudgetRekap::rincianSel($sel['asli'] ?? []) : null;
                    $sekarang = $lama[(int) $posId][$mall][$bulan] ?? [];

                    $putusan = BudgetRekap::putusanSel($baru, $asli, $sekarang);
                    if ($putusan === 'lewati') continue;
                    if ($putusan === 'bentrok') {
                        $bentrok[] = $posSah[(int) $posId] . ' · ' . ($mall === '' ? 'tanpa mal' : BudgetRekap::MAL[$mall]) . ' · ' . $BLN[$bulan];
                        continue;
                    }

                    $kunci = ['pos_id' => (int) $posId, 'tahun' => $tahun, 'bulan' => $bulan, 'mall' => $mall];
                    $model->where($kunci)->delete();
                    foreach ($baru as $i => $b) {
                        $model->insert($kunci + $b + ['urutan' => $i, 'updated_at' => $now]);
                    }
                    $ditulis++;
                }
            }
        }
        $db->transComplete();

        $kembali = redirect()->to($this->url('rencana', $dept, ['tahun' => $tahun]));
        if ($ditulis > 0) {
            ActivityLog::captureBefore($sebelum);
            ActivityLog::captureAfter($this->petaRencana($model->milikDept($deptId, $tahun), $posSah));
            ActivityLog::write('update', 'budget_rencana', (string) $tahun, 'Budget ' . $dept['name'] . ' ' . $tahun, ['sel_diubah' => $ditulis, 'bentrok' => $bentrok]);
        }
        if ($bentrok) {
            $daftar = implode('; ', array_slice($bentrok, 0, 8)) . (count($bentrok) > 8 ? '; dan ' . (count($bentrok) - 8) . ' lainnya' : '');
            return $kembali->with('warning', $ditulis . ' sel disimpan. ' . count($bentrok) . ' sel TIDAK disimpan karena sudah diubah orang lain sejak halaman Anda dibuka: '
                . esc($daftar) . '. Angka terbaru sudah tampil di bawah — periksa lalu isi ulang bila perlu.');
        }
        return $kembali->with('success', $ditulis ? 'Budget ' . $tahun . ' disimpan (' . $ditulis . ' sel diubah).' : 'Tidak ada perubahan untuk disimpan.');
    }

    /** Ringkasan per pos untuk diff log: "Nama pos" => "Rp total setahun". */
    private function petaRencana(array $rows, array $posNama): array
    {
        $out = [];
        foreach ($rows as $r) {
            $k = $posNama[(int) $r['pos_id']] ?? ('#' . $r['pos_id']);
            $out[$k] = ($out[$k] ?? 0) + (float) $r['jumlah'];
        }
        ksort($out);
        return array_map(fn ($v) => 'Rp ' . number_format($v, 0, ',', '.'), $out);
    }

    public function salinRencana()
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);
        $model  = new BudgetRencanaModel();
        $back   = redirect()->to($this->url('rencana', $dept, ['tahun' => $tahun]));

        if ($tahun - 1 < $this->tahunMulai($dept)) return $back->with('error', 'Tidak ada tahun sebelumnya untuk disalin.');
        if ($model->milikDept($deptId, $tahun) !== []) {
            return $back->with('error', 'Budget ' . $tahun . ' sudah berisi. Salin hanya bisa ke tahun yang masih kosong.');
        }
        $asal = $model->milikDept($deptId, $tahun - 1);
        if ($asal === []) return $back->with('error', 'Budget ' . ($tahun - 1) . ' kosong, tidak ada yang disalin.');

        $now = date('Y-m-d H:i:s');
        $model->insertBatch(array_map(fn ($r) => [
            'pos_id' => $r['pos_id'], 'tahun' => $tahun, 'bulan' => $r['bulan'],
            'mall' => $r['mall'], 'keterangan' => $r['keterangan'], 'urutan' => $r['urutan'],
            'jumlah' => $r['jumlah'], 'updated_at' => $now,
        ], $asal));

        ActivityLog::write('create', 'budget_rencana', (string) $tahun, 'Salin budget ' . ($tahun - 1) . ' → ' . $tahun . ' (' . $dept['name'] . ')');
        return $back->with('success', 'Budget ' . ($tahun - 1) . ' disalin ke ' . $tahun . '. Silakan sesuaikan angkanya.');
    }

    // ── Realisasi ────────────────────────────────────────────────────

    public function realisasi()
    {
        if (! $this->canViewMenu(self::MENU)) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $tahun  = $this->tahun($dept);

        $bulan = (int) $this->request->getGet('bulan');           // 0 = setahun
        if ($bulan < 0 || $bulan > 12) $bulan = 0;
        $dari   = $bulan ? sprintf('%d-%02d-01', $tahun, $bulan) : "$tahun-01-01";
        $sampai = $bulan ? date('Y-m-t', strtotime($dari)) : "$tahun-12-31";
        $posId  = (int) $this->request->getGet('pos') ?: null;
        $cari   = trim((string) $this->request->getGet('q'));

        $rows = (new BudgetRealisasiModel())->daftar($deptId, $dari, $sampai, $posId, $cari);

        return view('budget_dept/realisasi', $this->konteks($dept, $tahun, 'realisasi') + [
            'rows'  => $rows,
            'pos'   => (new BudgetPosModel())->milikDept($deptId),
            'bulan' => $bulan,
            'posId' => $posId,
            'cari'  => $cari,
            'url'   => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    public function simpanRealisasi()
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $p      = $this->request->getPost();
        $model  = new BudgetRealisasiModel();
        $id     = (int) ($p['id'] ?? 0);
        $kembali = $this->request->getPost('kembali');
        $back = redirect()->to(is_string($kembali) && str_starts_with($kembali, base_url()) ? $kembali : $this->url('realisasi', $dept));

        $lama = null;
        if ($id) {
            $lama = $model->where('id', $id)->where('dept_id', $deptId)->first();
            if (! $lama) return $back->with('error', 'Transaksi tidak ditemukan.');
        }

        $pos = (new BudgetPosModel())->milik((int) ($p['pos_id'] ?? 0), $deptId);
        $tanggal = (string) ($p['tanggal'] ?? '');
        $nilai   = BudgetRekap::angka($p['nilai'] ?? '');
        $uraian  = trim((string) ($p['uraian'] ?? ''));
        $asal    = trim((string) ($p['periode_asal'] ?? ''));
        $pisah   = (new BudgetDeptSettingModel())->pisahMal($deptId);
        $mall    = $pisah ? (string) ($p['mall'] ?? '') : '';

        $salah = [];
        if (! $pos) $salah[] = 'pos budget';
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || ! strtotime($tanggal)) $salah[] = 'tanggal';
        if (! $nilai) $salah[] = 'nilai';
        if ($uraian === '') $salah[] = 'uraian';
        $mulai = $this->tahunMulai($dept);
        if (preg_match('/^\d{4}/', $tanggal) && (int) substr($tanggal, 0, 4) < $mulai) $salah[] = 'tanggal (mulai ' . $mulai . ')';
        if ($asal !== '' && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $asal)) $salah[] = 'periode asal';
        if ($pisah && ! isset(BudgetRekap::MAL_REALISASI[$mall])) $salah[] = 'mal';
        if ($salah) return $back->withInput()->with('error', 'Periksa isian: ' . implode(', ', $salah) . '.');

        $data = [
            'dept_id'      => $deptId,
            'pos_id'       => (int) $pos['id'],
            'tanggal'      => $tanggal,
            'periode_asal' => $asal !== '' && $asal !== substr($tanggal, 0, 7) ? $asal : null,
            'uraian'       => mb_substr($uraian, 0, 255),
            'keterangan'   => trim((string) ($p['keterangan'] ?? '')) ?: null,
            'nilai'        => $nilai,
            'mall'         => $mall,
            'referensi'    => mb_substr(trim((string) ($p['referensi'] ?? '')), 0, 100) ?: null,
            'updated_by'   => (int) session()->get('user_id'),
        ];

        if ($lama) {
            $model->update($id, $data);
            ActivityLog::captureBefore(array_intersect_key($lama, $data));
            ActivityLog::captureAfter($data);
            ActivityLog::write('update', 'budget_realisasi', (string) $id, $uraian);
            $pesan = 'Transaksi diperbarui.';
        } else {
            $data['created_by'] = (int) session()->get('user_id');
            $id = $model->insert($data);
            ActivityLog::captureAfter($data);
            ActivityLog::write('create', 'budget_realisasi', (string) $id, $uraian);
            $pesan = 'Transaksi dicatat.';
        }
        return $back->with('success', $pesan);
    }

    public function hapusRealisasi(int $id)
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $model = new BudgetRealisasiModel();
        $row = $model->where('id', $id)->where('dept_id', (int) $dept['id'])->first();
        $kembali = $this->request->getPost('kembali');
        $back = redirect()->to(is_string($kembali) && str_starts_with($kembali, base_url()) ? $kembali : $this->url('realisasi', $dept));
        if (! $row) return $back->with('error', 'Transaksi tidak ditemukan.');

        $model->delete($id);
        ActivityLog::captureBefore($row);
        ActivityLog::write('delete', 'budget_realisasi', (string) $id, $row['uraian']);
        return $back->with('success', 'Transaksi dihapus.');
    }

    // ── Pos budget & pengaturan ──────────────────────────────────────

    public function pos()
    {
        if (! $this->canViewMenu(self::MENU)) return $this->tolak();
        $dept = $this->dept();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];

        return view('budget_dept/pos', $this->konteks($dept, $this->tahun($dept), 'pos') + [
            'pos'   => (new BudgetPosModel())->milikDept($deptId),
            'jumlah'=> (new BudgetPosModel())->jumlahData($deptId),
            'url'   => fn (string $p, array $q = []) => $this->url($p, $dept, $q),
        ]);
    }

    public function simpanPos()
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $deptId = (int) $dept['id'];
        $model  = new BudgetPosModel();
        $back   = redirect()->to($this->url('pos', $dept));

        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return $back->with('error', 'Nama pos wajib diisi.');

        $data = [
            'nama'       => mb_substr($nama, 0, 150),
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'aktif'      => $this->request->getPost('aktif') ? 1 : 0,
        ];

        if ($id) {
            $lama = $model->milik($id, $deptId);
            if (! $lama) return $back->with('error', 'Pos tidak ditemukan.');
            $model->update($id, $data);
            ActivityLog::captureBefore(array_intersect_key($lama, $data));
            ActivityLog::captureAfter($data);
            ActivityLog::write('update', 'budget_pos', (string) $id, $nama);
            return $back->with('success', 'Pos diperbarui.');
        }

        $maks = (int) ($model->selectMax('urutan')->where('dept_id', $deptId)->first()['urutan'] ?? 0);
        $data += ['dept_id' => $deptId, 'urutan' => $maks + 1, 'aktif' => 1];
        $id = $model->insert($data);
        ActivityLog::write('create', 'budget_pos', (string) $id, $nama . ' (' . $dept['name'] . ')');
        return $back->with('success', 'Pos "' . $nama . '" ditambahkan.');
    }

    /**
     * Simpan urutan pos. Dipanggil lewat AJAX dari halaman Pos Budget (seret-lepas
     * atau tombol panah) → JSON berisi token CSRF baru (regenerate = true: tanpa
     * itu semua form lain di halaman jadi basi). Kiriman form biasa tetap dialihkan.
     */
    public function urutPos()
    {
        $ajax = $this->request->isAJAX();
        $dept = $this->deptUntukUbah();
        if (! $dept) {
            return $ajax
                ? $this->response->setStatusCode(403)->setJSON(['ok' => false, 'pesan' => 'Akses ditolak.', 'csrf' => csrf_hash()])
                : $this->tolak();
        }
        $model = new BudgetPosModel();
        $sah   = array_column($model->milikDept((int) $dept['id']), 'id', 'id');
        foreach (array_values((array) $this->request->getPost('ids')) as $i => $id) {
            if (isset($sah[(int) $id])) $model->update((int) $id, ['urutan' => $i + 1]);
        }
        if ($ajax) return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
        return redirect()->to($this->url('pos', $dept));
    }

    public function hapusPos(int $id)
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $model = new BudgetPosModel();
        $back  = redirect()->to($this->url('pos', $dept));
        $pos   = $model->milik($id, (int) $dept['id']);
        if (! $pos) return $back->with('error', 'Pos tidak ditemukan.');

        $n = $model->jumlahData((int) $dept['id'])[$id] ?? ['n_rencana' => 0, 'n_realisasi' => 0];
        if ($n['n_rencana'] > 0 || $n['n_realisasi'] > 0) {
            return $back->with('error', 'Pos "' . $pos['nama'] . '" sudah punya budget/realisasi — nonaktifkan saja, jangan dihapus.');
        }
        db_connect()->table('budget_rencana')->where('pos_id', $id)->delete();
        $model->delete($id);
        ActivityLog::captureBefore($pos);
        ActivityLog::write('delete', 'budget_pos', (string) $id, $pos['nama']);
        return $back->with('success', 'Pos dihapus.');
    }

    public function simpanSetting()
    {
        $dept = $this->deptUntukUbah();
        if (! $dept) return $this->tolak();
        $pisah = (bool) $this->request->getPost('pisah_mal');
        $model = new BudgetDeptSettingModel();
        $lama  = $model->pisahMal((int) $dept['id']);
        $model->simpan((int) $dept['id'], $pisah, (int) session()->get('user_id'));
        ActivityLog::captureBefore(['pisah_mal' => $lama]);
        ActivityLog::captureAfter(['pisah_mal' => $pisah]);
        ActivityLog::write('update', 'budget_dept_setting', (string) $dept['id'], $dept['name']);
        return redirect()->to($this->url('pos', $dept))->with('success', $pisah
            ? 'Budget kini dipisah per mal (eWalk / Pentacity).'
            : 'Budget kini tidak dipisah per mal.');
    }
}
