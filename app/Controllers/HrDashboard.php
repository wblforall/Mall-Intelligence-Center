<?php

namespace App\Controllers;

class HrDashboard extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('hr_main') && ! $this->canViewMenu('people_dev')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $rows = db_connect()->table('employees e')
            ->select('e.id, e.nama, e.jenis_kelamin, e.tanggal_lahir, e.tanggal_masuk, e.status, e.status_kontrak, e.tanggal_akhir_kontrak, e.project, e.pendidikan, e.dept_id, d.name AS dept_name, dv.nama AS div_nama')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->join('divisions dv', 'dv.id = d.division_id', 'left')
            ->get()->getResultArray();

        $total   = count($rows);
        $aktif   = array_values(array_filter($rows, fn($r) => $r['status'] === 'aktif'));
        $nAktif  = count($aktif);

        // ── Komposisi (berdasarkan karyawan aktif) ───────────────────────
        $gender = ['Laki-laki' => 0, 'Perempuan' => 0];
        $kontrak = [];
        $divisi  = [];
        $dept    = [];
        $project = [];
        $pendidikan = [];
        $masaKerja  = ['< 1 thn' => 0, '1–3 thn' => 0, '3–5 thn' => 0, '5–10 thn' => 0, '10–15 thn' => 0, '> 15 thn' => 0];
        $usia       = ['< 25' => 0, '25–34' => 0, '35–44' => 0, '45–54' => 0, '≥ 55' => 0];
        $now = new \DateTime();
        $today = new \DateTime('today');
        $baru90 = 0;
        $kontrakHabis = []; // mendekati habis / lewat

        // Anggota per kategori (untuk drill-down klik grafik): dim => label => [{i,n,d}]
        $members = ['divisi' => [], 'dept' => [], 'kontrak' => [], 'gender' => [], 'project' => [], 'pendidikan' => [], 'masaKerja' => [], 'usia' => []];
        $rec = function (string $dim, ?string $key, array $r) use (&$members) {
            if ($key === null) return;
            $members[$dim][$key][] = ['i' => $r['id'], 'n' => $r['nama'], 'd' => $r['dept_name'] ?: '—'];
        };

        foreach ($aktif as $r) {
            // Gender
            $g = strtoupper(trim((string) $r['jenis_kelamin']));
            $gKey = ($g === 'L' || $g === 'LAKI-LAKI') ? 'Laki-laki' : (($g === 'P' || $g === 'PEREMPUAN') ? 'Perempuan' : null);
            if ($gKey) { $gender[$gKey]++; $rec('gender', $gKey, $r); }

            // Status kontrak
            $sk = $this->kontrakGroup($r['status_kontrak']);
            $kontrak[$sk] = ($kontrak[$sk] ?? 0) + 1; $rec('kontrak', $sk, $r);

            // Divisi & Dept
            $dv = $r['div_nama'] ?: 'Tanpa Divisi';
            $divisi[$dv] = ($divisi[$dv] ?? 0) + 1; $rec('divisi', $dv, $r);
            $dp = $r['dept_name'] ?: 'Tanpa Dept';
            $dept[$dp] = ($dept[$dp] ?? 0) + 1; $rec('dept', $dp, $r);

            // Project (sumber gaji)
            $pj = trim((string) $r['project']) ?: 'Tidak diisi';
            $project[$pj] = ($project[$pj] ?? 0) + 1; $rec('project', $pj, $r);

            // Kontrak mendekati habis (punya tanggal akhir, dalam 90 hari ke depan / sudah lewat)
            if (! empty($r['tanggal_akhir_kontrak'])) {
                $akhir = new \DateTime($r['tanggal_akhir_kontrak']);
                $sisa  = (int) $today->diff($akhir)->format('%r%a'); // negatif = sudah lewat
                if ($sisa <= 90) {
                    $kontrakHabis[] = [
                        'id'      => $r['id'],
                        'nama'    => $r['nama'],
                        'dept'    => $r['dept_name'] ?: '—',
                        'kontrak' => $r['status_kontrak'] ?: '—',
                        'akhir'   => $r['tanggal_akhir_kontrak'],
                        'sisa'    => $sisa,
                    ];
                }
            }

            // Pendidikan
            $pd = $this->pendidikanTier($r['pendidikan']);
            $pendidikan[$pd] = ($pendidikan[$pd] ?? 0) + 1; $rec('pendidikan', $pd, $r);

            // Masa kerja
            if (! empty($r['tanggal_masuk'])) {
                $diff = (new \DateTime($r['tanggal_masuk']))->diff($now);
                $thn = $diff->y + ($diff->m / 12);
                $mk = $thn < 1 ? '< 1 thn' : ($thn < 3 ? '1–3 thn' : ($thn < 5 ? '3–5 thn' : ($thn < 10 ? '5–10 thn' : ($thn < 15 ? '10–15 thn' : '> 15 thn'))));
                $masaKerja[$mk]++; $rec('masaKerja', $mk, $r);
                if ((int) $diff->days <= 90) $baru90++;
            }

            // Usia
            if (! empty($r['tanggal_lahir'])) {
                $u = (new \DateTime($r['tanggal_lahir']))->diff($now)->y;
                $uk = $u < 25 ? '< 25' : ($u < 35 ? '25–34' : ($u < 45 ? '35–44' : ($u < 55 ? '45–54' : '≥ 55')));
                $usia[$uk]++; $rec('usia', $uk, $r);
            }
        }

        arsort($divisi);
        arsort($dept);
        arsort($pendidikan);
        arsort($project);
        // urutkan kontrak: paling mendesak (sisa terkecil/sudah lewat) dulu
        usort($kontrakHabis, fn($a, $b) => $a['sisa'] <=> $b['sisa']);

        return view('people/hr_dashboard', [
            'user'         => $this->currentUser(),
            'total'        => $total,
            'nAktif'       => $nAktif,
            'nNonaktif'    => $total - $nAktif,
            'baru90'       => $baru90,
            'gender'       => $gender,
            'kontrak'      => $kontrak,
            'divisi'       => $divisi,
            'dept'         => $dept,
            'project'      => $project,
            'pendidikan'   => $pendidikan,
            'masaKerja'    => $masaKerja,
            'usia'         => $usia,
            'kontrakHabis' => $kontrakHabis,
            'members'      => $members,
        ]);
    }

    private function kontrakGroup(?string $sk): string
    {
        $s = strtoupper(trim((string) $sk));
        if ($s === '') return 'Tidak diisi';
        if (str_contains($s, 'PERMANENT') || str_contains($s, 'TETAP')) return 'Tetap';
        if (str_contains($s, 'PROBATION')) return 'Probation';
        if (str_contains($s, 'KONTRAK')) return 'Kontrak';
        return 'Lainnya';
    }

    private function pendidikanTier(?string $p): string
    {
        $s = strtoupper(trim((string) $p));
        if ($s === '') return 'Tidak diisi';
        if (str_contains($s, 'S3') || str_contains($s, 'DOKTOR')) return 'S3';
        if (str_contains($s, 'S2') || str_contains($s, 'MAGISTER') || str_contains($s, 'MASTER')) return 'S2';
        if (str_contains($s, 'S1') || str_contains($s, 'SARJANA')) return 'S1';
        if (str_contains($s, 'D4') || str_contains($s, 'D3') || str_contains($s, 'D2') || str_contains($s, 'D1') || str_contains($s, 'DIPLOMA')) return 'Diploma';
        if (str_contains($s, 'SMK') || str_contains($s, 'STM')) return 'SMK/STM';
        if (str_contains($s, 'SMA') || str_contains($s, 'SLTA') || str_contains($s, 'MA')) return 'SMA';
        if (str_contains($s, 'SMP') || str_contains($s, 'SLTP')) return 'SMP';
        return 'Lainnya';
    }
}
