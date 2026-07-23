<?php

namespace App\Controllers;

use App\Models\AppraisalTemplateModel;
use App\Models\AppraisalTemplateKpiModel;
use App\Models\AppraisalTemplateCompetencyModel;
use App\Models\JabatanModel;
use App\Libraries\AppraisalConfig;
use App\Libraries\AppraisalAuthority;
use App\Libraries\ActivityLog;
use App\Libraries\SimpleXlsx;

class AppraisalTemplate extends BaseController
{
    private ?AppraisalAuthority $_auth = null;

    // ── Akses ────────────────────────────────────────────────────────────
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }
    private function uid(): int { return (int) $this->currentUser()['id']; }
    private function authority(): AppraisalAuthority { return $this->_auth ??= new AppraisalAuthority(); }

    /** Boleh akses modul template = HR, atau ditunjuk sebagai dept head/deputy. */
    private function canManage(): bool
    {
        return $this->isHr() || $this->authority()->isAssignedAuthor($this->uid());
    }

    private function jabatanRow(int $id): ?array
    {
        return db_connect()->table('jabatans')->select('id, grade, dept_id')->where('id', $id)->get()->getRowArray();
    }

    /** Boleh menyusun template untuk satu jabatan? */
    private function canAuthorJab(int $jabatanId): bool
    {
        $j = $this->jabatanRow($jabatanId);
        return $j ? $this->authority()->canAuthor($this->uid(), $j, $this->isHr()) : false;
    }

    // ── List ─────────────────────────────────────────────────────────────
    public function index()
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $kpiModel      = new AppraisalTemplateKpiModel();
        $templates     = $templateModel->listWithJabatan();

        $isHr = $this->isHr();
        if (! $isHr) {
            $templates = array_values(array_filter($templates, fn($t) =>
                $this->authority()->canAuthor($this->uid(), ['grade' => $t['grade'], 'dept_id' => $t['jabatan_dept_id']], false)));
        }
        foreach ($templates as &$t) {
            $t['total_bobot'] = $kpiModel->totalBobot((int) $t['id']);
            $t['kpi_count']   = $kpiModel->where('template_id', $t['id'])->countAllResults();
        }
        unset($t);

        // Jabatan yang belum punya template & yang BOLEH disusun user ini (untuk tombol buat baru)
        $jabs = db_connect()->table('jabatans j')
            ->select('j.id, j.nama, j.grade, d.name AS dept_name, j.dept_id')
            ->join('departments d', 'd.id = j.dept_id', 'left')
            ->orderBy('d.name')->orderBy('j.grade')->get()->getResultArray();
        $hasTemplate = array_column($templates, null, 'jabatan_id');
        $jabsAvailable = array_values(array_filter($jabs, fn($j) =>
            ! isset($hasTemplate[$j['id']]) && $this->authority()->canAuthor($this->uid(), $j, $isHr)));

        return view('appraisal/templates/index', [
            'user'          => $this->currentUser(),
            'templates'     => $templates,
            'jabsAvailable' => $jabsAvailable,
            'isHr'          => $isHr,
        ]);
    }

    // ── Buat template untuk satu jabatan ─────────────────────────────────
    public function create()
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $jabatanId = (int) $this->request->getPost('jabatan_id');
        if (! $jabatanId) return redirect()->back()->with('error', 'Jabatan wajib dipilih.');

        if (! $this->canAuthorJab($jabatanId)) {
            return redirect()->back()->with('error', 'Anda tidak berwenang menyusun template untuk jabatan ini.');
        }

        $templateModel = new AppraisalTemplateModel();
        if ($templateModel->getForJabatan($jabatanId)) {
            return redirect()->back()->with('error', 'Template untuk jabatan ini sudah ada.');
        }

        $jab = (new JabatanModel())->find($jabatanId);
        $id = $templateModel->insert([
            'jabatan_id'       => $jabatanId,
            'nama'             => 'KPI ' . ($jab['nama'] ?? ''),
            'status'           => 'draft',
            'bobot_kpi'        => AppraisalConfig::BOBOT_KPI,
            'bobot_kompetensi' => AppraisalConfig::BOBOT_KOMPETENSI,
            'created_by'       => $this->currentUser()['id'],
        ]);

        // Seed 5 aspek kompetensi default
        $compModel = new AppraisalTemplateCompetencyModel();
        $urut = 1;
        foreach (AppraisalConfig::DEFAULT_KOMPETENSI as $c) {
            $compModel->insert(['template_id' => $id, 'nama_aspek' => $c['nama_aspek'], 'deskripsi' => $c['deskripsi'], 'urutan' => $urut++]);
        }

        ActivityLog::write('create', 'appraisal_template', (string) $id, $jab['nama'] ?? '');
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template dibuat. Silakan susun item KPI.');
    }

    // ── Salin template ke jabatan lain ───────────────────────────────────
    public function copy()
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $sourceId  = (int) $this->request->getPost('source_id');
        $jabatanId = (int) $this->request->getPost('jabatan_id');
        if (! $sourceId || ! $jabatanId) return redirect()->back()->with('error', 'Template sumber & jabatan tujuan wajib dipilih.');

        $templateModel = new AppraisalTemplateModel();
        $src = $templateModel->find($sourceId);
        if (! $src) return redirect()->back()->with('error', 'Template sumber tidak ditemukan.');
        if (! $this->canAuthorJab((int) $src['jabatan_id'])) return redirect()->back()->with('error', 'Anda tidak berwenang menyalin template sumber ini (di luar dept yang Anda kelola).');
        if (! $this->canAuthorJab($jabatanId)) return redirect()->back()->with('error', 'Anda tidak berwenang menyusun template untuk jabatan tujuan.');
        if ($templateModel->getForJabatan($jabatanId)) return redirect()->back()->with('error', 'Jabatan tujuan sudah punya template.');

        $jab = (new JabatanModel())->find($jabatanId);
        $newId = $templateModel->insert([
            'jabatan_id'       => $jabatanId,
            'nama'             => 'KPI ' . ($jab['nama'] ?? ''),
            'status'           => 'draft',
            'bobot_kpi'        => $src['bobot_kpi'],
            'bobot_kompetensi' => $src['bobot_kompetensi'],
            'created_by'       => $this->currentUser()['id'],
        ]);

        $kpiModel = new AppraisalTemplateKpiModel();
        foreach ($kpiModel->getByTemplate($sourceId) as $k) {
            $kpiModel->insert(['template_id' => $newId, 'area' => $k['area'], 'indikator' => $k['indikator'], 'unit' => $k['unit'], 'bobot' => $k['bobot'], 'target' => $k['target'], 'urutan' => $k['urutan']]);
        }
        $compModel = new AppraisalTemplateCompetencyModel();
        foreach ($compModel->getByTemplate($sourceId) as $c) {
            $compModel->insert(['template_id' => $newId, 'nama_aspek' => $c['nama_aspek'], 'deskripsi' => $c['deskripsi'], 'urutan' => $c['urutan']]);
        }

        ActivityLog::write('create', 'appraisal_template', (string) $newId, $jab['nama'] ?? '', ['disalin_dari' => $sourceId]);
        return redirect()->to('appraisal/templates/' . $newId)->with('success', 'Template disalin sebagai draft. Silakan sesuaikan lalu ajukan.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $tpl = $templateModel->find($id);
        if (! $tpl) return redirect()->to('appraisal/templates')->with('error', 'Template tidak ditemukan.');

        if (! $this->canAuthorJab((int) $tpl['jabatan_id'])) {
            return redirect()->to('appraisal/templates')->with('error', 'Akses ditolak.');
        }

        $jab = (new JabatanModel())->db->table('jabatans j')
            ->select('j.nama, d.name AS dept_name')
            ->join('departments d', 'd.id = j.dept_id', 'left')
            ->where('j.id', $tpl['jabatan_id'])->get()->getRowArray();

        $kpiModel  = new AppraisalTemplateKpiModel();
        $compModel = new AppraisalTemplateCompetencyModel();

        // Approved → terkunci untuk manager; HR bisa buka kembali.
        $locked = ($tpl['status'] === 'approved' && ! $this->isHr())
               || ($tpl['status'] === 'submitted' && ! $this->isHr());

        return view('appraisal/templates/edit', [
            'user'        => $this->currentUser(),
            'tpl'         => $tpl,
            'jab'         => $jab,
            'kpis'        => $kpiModel->getByTemplate($id),
            'comps'       => $compModel->getByTemplate($id),
            'totalBobot'  => $kpiModel->totalBobot($id),
            'areas'       => AppraisalConfig::AREAS,
            'units'       => AppraisalConfig::UNITS,
            'isHr'        => $this->isHr(),
            'locked'      => $locked,
        ]);
    }

    // ── Simpan item KPI (replace semua, editor tunggal) ──────────────────
    public function saveKpi(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $kpiModel = new AppraisalTemplateKpiModel();
        $db = db_connect();
        $db->transStart();
        $kpiModel->where('template_id', $id)->delete();

        $rows = $this->request->getPost('kpi') ?? [];
        $urut = 1;
        foreach ($rows as $r) {
            $indikator = trim($r['indikator'] ?? '');
            if ($indikator === '') continue;
            $kpiModel->insert([
                'template_id' => $id,
                'area'        => in_array($r['area'] ?? '', array_keys(AppraisalConfig::AREAS)) ? $r['area'] : 'pencapaian_target',
                'indikator'   => $indikator,
                'unit'        => in_array($r['unit'] ?? '', array_keys(AppraisalConfig::UNITS)) ? $r['unit'] : 'persen',
                'bobot'       => (float) ($r['bobot'] ?? 0),
                'target'      => ($r['target'] ?? '') === '' ? null : (float) $r['target'],
                'urutan'      => $urut++,
            ]);
        }
        $db->transComplete();

        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Item KPI — ' . ($tpl['nama'] ?? ''), ['total_bobot' => $kpiModel->totalBobot($id)]);
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Item KPI disimpan.');
    }

    // ── Simpan aspek kompetensi ──────────────────────────────────────────
    public function saveCompetency(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $compModel = new AppraisalTemplateCompetencyModel();
        $db = db_connect();
        $db->transStart();
        $compModel->where('template_id', $id)->delete();

        $rows = $this->request->getPost('comp') ?? [];
        $urut = 1;
        foreach ($rows as $r) {
            $nama = trim($r['nama_aspek'] ?? '');
            if ($nama === '') continue;
            $compModel->insert([
                'template_id' => $id,
                'nama_aspek'  => $nama,
                'deskripsi'   => trim($r['deskripsi'] ?? '') ?: null,
                'urutan'      => $urut++,
            ]);
        }
        $db->transComplete();

        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Aspek Kompetensi — ' . ($tpl['nama'] ?? ''));
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Aspek kompetensi disimpan.');
    }

    // ── Unduh template Excel untuk diisi sebelum import ──────────────────
    public function downloadImportTemplate(int $id)
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $tpl = (new AppraisalTemplateModel())->find($id);
        if (! $tpl) return redirect()->to('appraisal/templates')->with('error', 'Template tidak ditemukan.');
        if (! $this->canAuthorJab((int) $tpl['jabatan_id'])) {
            return redirect()->to('appraisal/templates')->with('error', 'Akses ditolak.');
        }

        $jab = (new JabatanModel())->find((int) $tpl['jabatan_id']);
        $namaJab = $jab['nama'] ?? 'Template';

        // Prefill dengan data yang sudah ada (bila sudah terisi) + 1 contoh bila kosong.
        $kpiModel  = new AppraisalTemplateKpiModel();
        $compModel = new AppraisalTemplateCompetencyModel();
        $kpis  = $kpiModel->getByTemplate($id);
        $comps = $compModel->getByTemplate($id);

        $kpiRows = [['Area Kinerja', 'Indikator (KPI)', 'Unit', 'Bobot (%)', 'Target']];
        if ($kpis) {
            foreach ($kpis as $k) {
                $kpiRows[] = [
                    AppraisalConfig::areaLabel($k['area']),
                    (string) $k['indikator'],
                    AppraisalConfig::unitLabel($k['unit']),
                    (float) $k['bobot'],
                    $k['target'] !== null ? (float) $k['target'] : '',
                ];
            }
        } else {
            $kpiRows[] = ['Pencapaian Target Pekerjaan', 'Contoh: Pencapaian omzet penjualan sesuai target', '%', 40, 100];
            $kpiRows[] = ['Program Kerja & Pelatihan', 'Contoh: Menyelesaikan program kerja tepat waktu', 'Jumlah Nilai', 30, 4];
            $kpiRows[] = ['Metode Kerja & Program Improvisasi', 'Contoh: Menerapkan 1 inisiatif improvisasi', 'Jumlah Nilai', 20, 1];
            $kpiRows[] = ['Pelaporan & Pertanggungjawaban Pekerjaan', 'Contoh: Laporan bulanan tepat waktu', 'Bulan', 10, 12];
        }

        $compRows = [['Nama Aspek', 'Deskripsi']];
        if ($comps) {
            foreach ($comps as $c) $compRows[] = [(string) $c['nama_aspek'], (string) ($c['deskripsi'] ?? '')];
        } else {
            foreach (AppraisalConfig::DEFAULT_KOMPETENSI as $c) $compRows[] = [$c['nama_aspek'], $c['deskripsi']];
        }

        // Sheet petunjuk: daftar nilai valid Area & Unit.
        $petunjuk = [
            ['PETUNJUK PENGISIAN', ''],
            ['', ''],
            ['1. Isi sheet "KPI" dan "Kompetensi". Jangan ubah baris judul (baris pertama).', ''],
            ['2. Kolom "Area Kinerja" harus salah satu dari daftar berikut (tulis persis).', ''],
            ['3. Kolom "Unit" harus salah satu dari daftar berikut (tulis persis).', ''],
            ['4. Total "Bobot (%)" seluruh KPI sebaiknya 100 (divalidasi saat pengajuan ke HR).', ''],
            ['5. "Target" boleh dikosongkan. Baris tanpa indikator akan dilewati.', ''],
            ['6. Saat diimpor, isi lama pada bagian yang Anda isi di Excel akan DIGANTIKAN.', ''],
            ['', ''],
            ['DAFTAR AREA KINERJA VALID', ''],
        ];
        foreach (AppraisalConfig::AREAS as $label) $petunjuk[] = [$label, ''];
        $petunjuk[] = ['', ''];
        $petunjuk[] = ['DAFTAR UNIT VALID', ''];
        foreach (AppraisalConfig::UNITS as $label) $petunjuk[] = [$label, ''];

        $sheets = [
            ['name' => 'KPI',        'rows' => $kpiRows],
            ['name' => 'Kompetensi', 'rows' => $compRows],
            ['name' => 'Petunjuk',   'rows' => $petunjuk],
        ];
        $widths = [
            'KPI'        => [34, 60, 16, 12, 12],
            'Kompetensi' => [40, 80],
            'Petunjuk'   => [70, 4],
        ];
        $fname = 'Template Appraisal - ' . preg_replace('/[^A-Za-z0-9 _-]/', '', $namaJab) . '.xlsx';
        SimpleXlsx::download($fname, $sheets, $widths);
    }

    // ── Import KPI & Kompetensi dari file Excel ───────────────────────────
    public function import(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'File tidak valid: ' . ($file ? $file->getErrorString() : 'tidak ada file'));
        }
        if (strtolower($file->getExtension()) !== 'xlsx' && strtolower($file->getClientExtension()) !== 'xlsx') {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Format harus .xlsx (gunakan template yang diunduh).');
        }

        $path = $file->getTempName();

        // Peta terbalik label/slug → slug (case-insensitive) untuk area & unit.
        $areaMap = []; $unitMap = [];
        foreach (AppraisalConfig::AREAS as $slug => $label) { $areaMap[self::norm($slug)] = $slug; $areaMap[self::norm($label)] = $slug; }
        foreach (AppraisalConfig::UNITS as $slug => $label) { $unitMap[self::norm($slug)] = $slug; $unitMap[self::norm($label)] = $slug; }

        // Baca kedua sheet dengan satu kali buka arsip.
        $sheets  = SimpleXlsx::readSheets($path, [0, 1]);
        $kpiRows = $sheets[0] ?? [];
        $kpiParsed = [];
        foreach ($kpiRows as $ri => $row) {
            $area = trim((string) ($row[0] ?? ''));
            $indi = trim((string) ($row[1] ?? ''));
            // lewati header (baris berlabel "Area..."/"Indikator...")
            if ($ri === 0 && (stripos($area, 'area') !== false || stripos($indi, 'indikator') !== false)) continue;
            if ($indi === '') continue; // baris tanpa indikator diabaikan
            $kpiParsed[] = [
                'area'   => $areaMap[self::norm($area)] ?? 'pencapaian_target',
                'indi'   => $indi,
                'unit'   => $unitMap[self::norm(trim((string) ($row[2] ?? '')))] ?? 'persen',
                'bobot'  => self::parseNum($row[3] ?? '0') ?? 0.0,
                'target' => self::parseNum($row[4] ?? ''),
            ];
        }

        // ── Sheet Kompetensi (index 1) ──
        $compRows = $sheets[1] ?? [];
        $compParsed = [];
        foreach ($compRows as $ri => $row) {
            $nama = trim((string) ($row[0] ?? ''));
            $desk = trim((string) ($row[1] ?? ''));
            if ($ri === 0 && (stripos($nama, 'aspek') !== false || stripos($nama, 'nama') !== false)) continue;
            if ($nama === '') continue;
            $compParsed[] = ['nama' => $nama, 'desk' => $desk !== '' ? $desk : null];
        }

        if (! $kpiParsed && ! $compParsed) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Tidak ada baris data terbaca. Pastikan sheet "KPI"/"Kompetensi" terisi.');
        }

        $db = db_connect();
        $db->transStart();

        if ($kpiParsed) {
            $kpiModel = new AppraisalTemplateKpiModel();
            $kpiModel->where('template_id', $id)->delete();
            $urut = 1;
            foreach ($kpiParsed as $r) {
                $kpiModel->insert([
                    'template_id' => $id, 'area' => $r['area'], 'indikator' => $r['indi'],
                    'unit' => $r['unit'], 'bobot' => $r['bobot'], 'target' => $r['target'], 'urutan' => $urut++,
                ]);
            }
        }
        if ($compParsed) {
            $compModel = new AppraisalTemplateCompetencyModel();
            $compModel->where('template_id', $id)->delete();
            $urut = 1;
            foreach ($compParsed as $r) {
                $compModel->insert(['template_id' => $id, 'nama_aspek' => $r['nama'], 'deskripsi' => $r['desk'], 'urutan' => $urut++]);
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Import gagal disimpan (transaksi dibatalkan). Periksa isi file lalu coba lagi.');
        }

        $totBobot = (new AppraisalTemplateKpiModel())->totalBobot($id);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Import Excel — ' . ($tpl['nama'] ?? ''),
            ['kpi' => count($kpiParsed), 'kompetensi' => count($compParsed), 'total_bobot' => $totBobot]);

        $msg = 'Import berhasil: ' . count($kpiParsed) . ' KPI, ' . count($compParsed) . ' aspek kompetensi.';
        if ($kpiParsed && abs($totBobot - 100) > 0.01) $msg .= " Perhatikan: total bobot KPI = {$totBobot} (harus 100 sebelum diajukan).";
        return redirect()->to('appraisal/templates/' . $id)->with('success', $msg);
    }

    /** Normalisasi untuk pencocokan label/slug: lowercase, rapatkan spasi. */
    private static function norm(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }

    /**
     * Parse angka dari sel Excel dengan toleransi format lokal (Indonesia).
     * Angka numerik dari Excel dikembalikan apa adanya; teks "12,5" → 12.5,
     * "1.000.000" → 1000000, "1.000,50" → 1000.5. Kosong → null.
     */
    private static function parseNum($v): ?float
    {
        if (is_int($v) || is_float($v)) return (float) $v;
        $s = trim((string) $v);
        if ($s === '') return null;
        $neg = strpos(ltrim($s), '-') === 0;
        $s = preg_replace('/[^0-9.,]/', '', $s); // sisakan digit, koma, titik
        if ($s === '') return null;
        $hasComma = strpos($s, ',') !== false;
        $hasDot   = strpos($s, '.') !== false;
        if ($hasComma && $hasDot) {
            // Pemisah desimal = yang muncul paling akhir; sisanya pemisah ribuan.
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace(['.', ','], ['', '.'], $s); // titik=ribuan, koma=desimal
            } else {
                $s = str_replace(',', '', $s);               // koma=ribuan, titik=desimal
            }
        } elseif ($hasComma) {
            $s = str_replace(',', '.', $s);                  // hanya koma → desimal
        } elseif (substr_count($s, '.') > 1) {
            $s = str_replace('.', '', $s);                   // beberapa titik → ribuan
        }
        $n = (float) $s;
        return $neg ? -$n : $n;
    }

    // ── Submit ke HR ─────────────────────────────────────────────────────
    public function submit(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;

        $kpiModel = new AppraisalTemplateKpiModel();
        $total = $kpiModel->totalBobot($id);
        if (abs($total - 100) > 0.01) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', "Total bobot KPI harus 100 (sekarang {$total}).");
        }
        if ($kpiModel->where('template_id', $id)->countAllResults() === 0) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Belum ada item KPI.');
        }

        (new AppraisalTemplateModel())->update($id, ['status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Submit ke HR — ' . ($tpl['nama'] ?? ''));
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template diajukan ke HR untuk persetujuan.');
    }

    // ── HR: approve / reject ─────────────────────────────────────────────
    public function approve(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Hanya HR yang dapat menyetujui.');
        $m = new AppraisalTemplateModel();
        $tpl = $m->find($id);
        if (! $tpl || $tpl['status'] !== 'submitted') return redirect()->back()->with('error', 'Template belum diajukan.');

        $total = (new AppraisalTemplateKpiModel())->totalBobot($id);
        if (abs($total - 100) > 0.01) return redirect()->back()->with('error', "Total bobot KPI harus 100 (sekarang {$total}).");

        $m->update($id, ['status' => 'approved', 'approved_by' => $this->currentUser()['id'], 'approved_at' => date('Y-m-d H:i:s'), 'catatan_hr' => null]);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Approve — ' . ($tpl['nama'] ?? ''));
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Hanya HR yang dapat menolak.');
        $m = new AppraisalTemplateModel();
        $tpl = $m->find($id);
        if (! $tpl || $tpl['status'] !== 'submitted') return redirect()->back()->with('error', 'Template belum diajukan.');

        $catatan = trim($this->request->getPost('catatan_hr') ?? '');
        if ($catatan === '') return redirect()->to('appraisal/templates/' . $id)->with('error', 'Catatan revisi wajib diisi saat mengembalikan template.');
        $m->update($id, ['status' => 'draft', 'catatan_hr' => $catatan]);
        ActivityLog::write('update', 'appraisal_template', (string) $id, 'Reject — ' . ($tpl['nama'] ?? ''), ['catatan' => $catatan]);
        return redirect()->to('appraisal/templates/' . $id)->with('success', 'Template dikembalikan ke penyusun.');
    }

    public function delete(int $id)
    {
        $tpl = $this->guardEditable($id);
        if (! is_array($tpl)) return $tpl;
        (new AppraisalTemplateModel())->delete($id); // cascade kpi & competency
        ActivityLog::write('delete', 'appraisal_template', (string) $id, $tpl['nama'] ?? '');
        return redirect()->to('appraisal/templates')->with('success', 'Template dihapus.');
    }

    /** Guard umum untuk aksi edit: kembalikan template row atau RedirectResponse. */
    private function guardEditable(int $id)
    {
        if (! $this->canManage()) return redirect()->to('/')->with('error', 'Akses ditolak.');
        $tpl = (new AppraisalTemplateModel())->find($id);
        if (! $tpl) return redirect()->to('appraisal/templates')->with('error', 'Template tidak ditemukan.');

        if (! $this->canAuthorJab((int) $tpl['jabatan_id'])) {
            return redirect()->to('appraisal/templates')->with('error', 'Akses ditolak.');
        }
        // approved/submitted hanya HR yang boleh ubah (mengembalikan ke draft dulu untuk approved)
        if (in_array($tpl['status'], ['submitted', 'approved']) && ! $this->isHr()) {
            return redirect()->to('appraisal/templates/' . $id)->with('error', 'Template sedang dikunci (menunggu/terkunci HR).');
        }
        if ($tpl['status'] === 'approved' && $this->isHr()) {
            // HR membuka kembali approved → kembali ke draft saat mulai edit
            (new AppraisalTemplateModel())->update($id, ['status' => 'draft']);
            $tpl['status'] = 'draft';
        }
        return $tpl;
    }
}
