<?php

namespace App\Commands;

use App\Libraries\SponsorshipDobel as D;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Uji unit pendeteksi sponsor dobel (Sponsorship program mandiri ↔ event).
 *
 *   php spark mic:sp-uji-dobel
 *
 * HANYA MEMBACA: bagian murni diuji dengan data buatan; bagian "data lokal"
 * membaca database (September 2026, Juni 2026) tanpa menulis apa pun.
 * Keluar dengan kode 1 bila ada yang gagal.
 */
class SpUjiDobel extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:sp-uji-dobel';
    protected $description = 'Uji pendeteksi sponsor dobel (program mandiri vs event). Hanya membaca.';
    protected $usage       = 'mic:sp-uji-dobel';

    private int $lulus = 0;
    private int $gagal = 0;

    private function cek(string $nama, bool $ok, string $info = ''): void
    {
        if ($ok) { $this->lulus++; CLI::write('  [LULUS] ' . $nama, 'green'); return; }
        $this->gagal++;
        CLI::write('  [GAGAL] ' . $nama . ($info !== '' ? '  → ' . $info : ''), 'red');
    }

    private function prog(int $id, string $nama, ?string $mulai, ?string $selesai): array
    {
        return ['id' => $id, 'nama_program' => $nama, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai];
    }

    private function ev(int $id, string $nama, ?string $mulai, int $hari = 1): array
    {
        return ['id' => $id, 'name' => $nama, 'start_date' => $mulai, 'event_days' => $hari, 'mall' => 'ewalk'];
    }

    private function sp(int $id, int $parent, string $nama, int $nilai, bool $program, string $status = 'lunas'): array
    {
        return $program
            ? ['id' => $id, 'program_id' => $parent, 'nama_sponsor' => $nama, 'nilai' => $nilai, 'jenis' => 'barang', 'status_deal' => $status]
            : ['id' => $id, 'event_id' => $parent, 'nama_sponsor' => $nama, 'nilai' => $nilai, 'jenis' => 'barang'];
    }

    public function run(array $params)
    {
        CLI::write('Uji pendeteksi sponsor dobel', 'yellow');

        // ── 1. Normalisasi ────────────────────────────────────────────────
        CLI::write('1. Normalisasi nama', 'cyan');
        $this->cek('huruf kecil + trim + spasi ganda dirapatkan', D::normalisasi("  Pizza   HUT  ") === 'pizza hut');
        $this->cek('tanda baca dibuang', D::normalisasi("Toys-Kingdom, Inc.") === 'toys kingdom inc');
        $this->cek('Pizza Hut = Pizza hut (skor 1)', D::skorNama('Pizza Hut', 'Pizza hut') === 1.0);
        $this->cek('tanpa spasi: ToysKingdom ≈ Toys Kingdom (>= 0,97)', D::skorNama('ToysKingdom', 'Toys Kingdom') >= D::AMBANG_SAMA);
        $this->cek('GC Korean BBQ ≈ GC Korean (>= 0,85)', D::skorNama('GC Korean BBQ', 'GC Korean') >= D::AMBANG_MIRIP, (string) D::skorNama('GC Korean BBQ', 'GC Korean'));
        $this->cek('nama berbeda (Maxim vs Grab) < 0,85', D::skorNama('Maxim', 'Grab') < D::AMBANG_MIRIP);
        $this->cek('nama kosong → 0', D::skorNama('', 'Maxim') === 0.0);

        // ── 2. Keyakinan ──────────────────────────────────────────────────
        CLI::write('2. Tingkat keyakinan', 'cyan');
        $this->cek('nama + nilai sama → kuat', D::keyakinan('Pizza Hut', 230000, 'Pizza hut', 230000) === 'kuat');
        $this->cek('nama mirip + nilai sama → sedang', D::keyakinan('GC Korean BBQ', 1000000, 'GC Korean', 1000000) === 'sedang');
        $this->cek('nama sama + nilai beda 44% → sedang (Fun Station)', D::keyakinan('Fun Station', 500000, 'Fun Station', 900000) === 'sedang');
        $this->cek('nama sama + nilai beda 75% → lemah (tidak tampil)', D::keyakinan('Fun Station', 500000, 'Fun Station', 2000000) === null);
        $this->cek('nama mirip + nilai beda → lemah', D::keyakinan('GC Korean BBQ', 1000000, 'GC Korean', 900000) === null);
        $this->cek('nama berbeda + nilai sama → lemah', D::keyakinan('Maxim', 1000000, 'Grab', 1000000) === null);

        // ── 3. Periode ────────────────────────────────────────────────────
        CLI::write('3. Irisan periode', 'cyan');
        $juniP = D::bulanProgram($this->prog(1, 'Juni', '2026-06-01', '2026-06-30'));
        $kuliner = D::bulanEvent($this->ev(1, 'Kuliner', '2026-06-25', 11));      // 25 Jun – 5 Jul
        $this->cek('event 25 Jun + 11 hari menyentuh Juni & Juli', $kuliner === ['2026-06', '2026-07'], json_encode($kuliner));
        $this->cek('Program Juni beririsan dengan event 25 Jun', D::periodeBeririsan($juniP, $kuliner));
        $this->cek('Program Mei TIDAK beririsan dengan event 25 Jun', ! D::periodeBeririsan(D::bulanProgram($this->prog(2, 'Mei', '2026-05-01', '2026-05-30')), $kuliner));
        $this->cek('periode tak diketahui → tidak cocok', ! D::periodeBeririsan(null, $kuliner) && ! D::periodeBeririsan($juniP, D::bulanEvent($this->ev(2, 'x', null))));
        $this->cek('program tanpa tanggal selesai = bulan mulai saja', D::bulanProgram($this->prog(3, 'x', '2026-09-10', null)) === ['2026-09']);

        // ── 4. cocokkan (data buatan) ─────────────────────────────────────
        CLI::write('4. Pencocokan (data buatan)', 'cyan');
        $programs = [$this->prog(12, 'Sponsorship September 2026', '2026-09-01', '2026-09-30'), $this->prog(2, 'Sponsorship Mei 2026', '2026-05-01', '2026-05-30')];
        $events   = [$this->ev(15, 'Anniversary', '2026-09-04', 10)];
        $spP = [$this->sp(1, 12, 'Pizza Hut', 230000, true), $this->sp(2, 12, 'GC Korean BBQ', 1000000, true), $this->sp(3, 12, 'Fun Station', 500000, true),
                $this->sp(4, 12, 'Maxim', 700000, true), $this->sp(5, 12, 'Squades', 1104000, true), $this->sp(6, 12, 'Squades', 900000, true),
                $this->sp(7, 12, 'Prospek Co', 100000, true, 'prospek'), $this->sp(8, 2, 'Pizza Hut', 230000, true)];
        $spE = [$this->sp(101, 15, 'Pizza hut', 230000, false), $this->sp(102, 15, 'GC Korean', 1000000, false), $this->sp(103, 15, 'Fun Station', 900000, false),
                $this->sp(104, 15, 'Grab', 700000, false), $this->sp(105, 15, 'Squades', 900000, false), $this->sp(106, 15, 'Squades', 1104000, false),
                $this->sp(107, 15, 'Prospek Co', 100000, false)];
        $h = D::cocokkan($programs, $spP, $events, $spE, []);
        $this->cek('satu kelompok (Program Mei tidak beririsan)', count($h['grup']) === 1, (string) count($h['grup']));
        $g = $h['grup'][0] ?? null;
        $this->cek('5 pasangan: 3 kuat + 2 sedang', $g && $g['n'] === 5 && $g['n_kuat'] === 3 && $g['n_sedang'] === 2, $g ? json_encode([$g['n'], $g['n_kuat'], $g['n_sedang']]) : 'kosong');
        $peta = [];
        foreach ($g['pasangan'] ?? [] as $p) { $peta[$p['ps_id']] = $p['es_id']; }
        $this->cek('Squades dipasangkan 1:1 sesuai nilai (5↔106, 6↔105)', ($peta[5] ?? 0) === 106 && ($peta[6] ?? 0) === 105, json_encode($peta));
        $this->cek('Maxim vs Grab (nama berbeda) tidak cocok', ! isset($peta[4]));
        $this->cek('sponsor program berstatus prospek tidak dianggap deal', ! isset($peta[7]));
        $this->cek('ringkasan memuat "5 dari 6 sponsor kembar"', str_contains(D::ringkasan($g), '5 dari 6 sponsor kembar'), D::ringkasan($g));

        $h2 = D::cocokkan($programs, [], $events, $spE, []);
        $this->cek('tanpa sponsor program → tanpa kelompok', $h2['grup'] === []);
        $h3 = D::cocokkan($programs, $spP, $events, [], []);
        $this->cek('tanpa sponsor event → tanpa kelompok', $h3['grup'] === []);
        $h4 = D::cocokkan([], [], [], [], []);
        $this->cek('semua kosong → tanpa kelompok', $h4['grup'] === []);

        // ── 5. Keputusan memengaruhi hasil ────────────────────────────────
        CLI::write('5. Pengaruh keputusan', 'cyan');
        $putus = [['program_sponsor_id' => 1, 'event_sponsor_id' => 101, 'status' => 'bukan_dobel', 'sisi_kecuali' => null]];
        $h5 = D::cocokkan($programs, $spP, $events, $spE, $putus);
        $adaPizza = false;
        foreach ($h5['grup'][0]['pasangan'] ?? [] as $p) { if ($p['ps_id'] === 1) $adaPizza = true; }
        $this->cek('"bukan dobel" menyembunyikan pasangan', ! $adaPizza && $h5['grup'][0]['n'] === 4);
        $putus2 = [['program_sponsor_id' => 1, 'event_sponsor_id' => 101, 'status' => 'dobel', 'sisi_kecuali' => 'program']];
        $h6 = D::cocokkan($programs, $spP, $events, $spE, $putus2);
        $kunci = null;
        foreach ($h6['grup'][0]['pasangan'] ?? [] as $p) { if ($p['ps_id'] === 1) $kunci = $p; }
        $this->cek('keputusan "dobel" tetap tampil, terkunci (ber-keputusan)', $kunci !== null && $kunci['keputusan'] !== null && $h6['grup'][0]['n_belum'] === 4);

        // ── 6. Data lokal (hanya membaca) ─────────────────────────────────
        CLI::write('6. Data lokal (hanya membaca)', 'cyan');
        $db = db_connect();
        $adaSept = $db->table('sponsorship_programs')->where('id', 12)->countAllResults() > 0 && $db->table('events')->where('id', 15)->countAllResults() > 0;
        if (! $adaSept) {
            CLI::write('  (dilewati: Program 12 / Event 15 tidak ada di database ini)', 'yellow');
        } else {
            $det = D::deteksi();
            $gS = null; $gJ = null;
            foreach ($det['grup'] as $x) {
                if ((int) $x['program']['id'] === 12 && (int) $x['event']['id'] === 15) $gS = $x;
                if ((int) $x['program']['id'] === 8 && (int) $x['event']['id'] === 9) $gJ = $x;
            }
            $this->cek('September: Program 12 ↔ Event 15 terdeteksi', $gS !== null);
            if ($gS) {
                $this->cek('September: 22–24 sponsor kembar', $gS['n'] >= 22 && $gS['n'] <= 24, 'n=' . $gS['n'] . ' kuat=' . $gS['n_kuat'] . ' sedang=' . $gS['n_sedang']);
                $this->cek('September: >= 22 kembar kuat', $gS['n_kuat'] >= 22, (string) $gS['n_kuat']);
                CLI::write('     ' . D::ringkasan($gS));
            }
            $this->cek('Juni: Program 8 ↔ Event 9 terdeteksi', $gJ !== null);
            if ($gJ) {
                $squades = array_filter($gJ['pasangan'], fn($p) => strtolower($p['nama_p']) === 'squades');
                $this->cek('Juni: 2 pasangan Squades kembar (Rp 10.000.000 & Rp 15.585.000)', count($squades) === 2
                    && array_sum(array_column($squades, 'nilai_p')) === 25585000, json_encode(array_column($squades, 'nilai_p')));
            }
            // Pasangan antar-periode yang tak beririsan tidak boleh muncul.
            $salah = 0;
            foreach ($det['grup'] as $x) {
                if (! D::periodeBeririsan(D::bulanProgram($x['program']), D::bulanEvent($x['event']))) $salah++;
            }
            $this->cek('tidak ada kelompok dengan periode tak beririsan', $salah === 0);
            $kec = D::idDikecualikan(true);
            $this->cek('idDikecualikan() berbentuk {program:[],event:[]}', isset($kec['program'], $kec['event']) && is_array($kec['program']) && is_array($kec['event']));
        }

        CLI::newLine();
        CLI::write($this->lulus . ' lulus, ' . $this->gagal . ' gagal', $this->gagal ? 'red' : 'green');
        return $this->gagal ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
