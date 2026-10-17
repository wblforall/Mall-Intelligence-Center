<?php

namespace App\Commands;

use App\Libraries\BudgetFsRekomendasi;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Uji mesin rekomendasi pos terhadap item IT NYATA dari fixture (writable/flowstore-fixture.json).
 *
 *   php spark mic:fs-uji-rekomendasi
 *
 * Bagian 1 menguji aturan NAMA barang dengan keperluan sengaja dikosongkan (meniru feed lama); bagian 2 menguji
 * KEPERLUAN (keterangan order) yang didahulukan atas nama barang; memori & kemiripan seperti semula.
 * Membaca budget_pos & budget_fs_aturan dept IT; bagian "memori" berjalan di dalam transaksi
 * yang di-ROLLBACK, jadi tidak meninggalkan jejak. Keluar dengan kode 1 bila ada kasus gagal.
 */
class FsUjiRekomendasi extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:fs-uji-rekomendasi';
    protected $description = 'Uji mesin rekomendasi pos FlowStore dengan item IT dari fixture.';

    public function run(array $params)
    {
        $db = db_connect();
        $dept = $db->table('departments')->where('name', 'IT / EDP')->get()->getRowArray();
        $berkas = WRITEPATH . 'flowstore-fixture.json';
        if (! $dept || ! is_file($berkas)) { CLI::error('Dept IT atau fixture tidak ada.'); return EXIT_ERROR; }
        $deptId = (int) $dept['id'];
        $items = array_filter(json_decode(file_get_contents($berkas), true)['items'], fn ($i) => (int) $i['id_divisi'] === 40);
        $per = [];
        $perKep = [];
        foreach ($items as $i) { $perKep[(int) $i['id_btb_item']] = $i; $i['keperluan'] = null; $per[(int) $i['id_btb_item']] = $i; }   // $per = tanpa keperluan
        $posNama = array_column($db->table('budget_pos')->where('dept_id', $deptId)->get()->getResultArray(), 'nama', 'id');

        $rek = new BudgetFsRekomendasi($db);
        $gagal = 0; $n = 0;
        $cek = function (string $label, bool $ok, string $info = '') use (&$gagal, &$n) {
            $n++;
            if (! $ok) $gagal++;
            CLI::write(($ok ? '  ok    ' : '  GAGAL ') . $label . ($info !== '' ? '  → ' . $info : ''), $ok ? 'green' : 'red');
        };
        $nama = fn (?int $id) => $id ? ($posNama[$id] ?? '#' . $id) : '(tanpa rekomendasi)';

        // [id item fixture, pos yang diharapkan (null = tanpa rekomendasi), sumber]
        $kasus = [
            [5510, 'Penambahan CCTV', 'kata_kunci'],                         // CAMERA CCTV OUTDOOR HIKVISION
            [2180, 'Penambahan CCTV', 'kata_kunci'],                         // NVR 32CH HIKVISION
            [2181, 'Penambahan CCTV', 'kata_kunci'],                         // IP CAMERA INDOOR
            [1185, 'Pengadaan Smart DoorLock', 'kata_kunci'],                // SMART CYLINDER LOCK
            [1174, 'Pengadaan Smart Steker', 'kata_kunci'],                  // SMART PLUG STEKER
            [2196, 'RTU Meteran Listrik IoT', 'kata_kunci'],                 // GATEWAY IFEMTO CELL
            [3695, 'RTU Meteran Listrik IoT', 'kata_kunci'],                 // RS485 LORA BRIDGE RTU
            [5742, 'Penambahan Temperatur dan Humidity Sensor', 'kata_kunci'],
            [5682, 'Peremajaan Komputer', 'kata_kunci'],                     // LAPTOP ASUS VIVOBOOK
            [3584, 'Peremajaan Komputer', 'kata_kunci'],                     // MACBOOK AIR
            [1462, 'Peremajaan Komputer', 'kata_kunci'],                     // IPAD AIR
            [3135, 'Peremajaan Komputer', 'kata_kunci'],                     // MINI PC
            [1428, 'Peremajaan Komputer', 'kata_kunci'],                     // MONITOR XIAOMI
            [2994, 'Peremajaan Komputer', 'kata_kunci'],                     // PC ALL IN ONE ... RAM, SSD (PC lebih awal dari RAM)
            [3190, 'Maintenance Komputer Kantor', 'kata_kunci'],             // CHARGER ADAPTOR LAPTOP (CHARGER lebih awal dari LAPTOP)
            [2011, 'Maintenance Komputer Kantor', 'kata_kunci'],             // SSD SAMSUNG
            [1304, 'Maintenance Komputer Kantor', 'kata_kunci'],             // MOUSE WIRELESS
            [1463, 'Maintenance Komputer Kantor', 'kata_kunci'],             // KEYBOARD IPAD (KEYBOARD lebih awal dari IPAD)
            [3242, null, 'none'],                                            // MIKROTIK ROUTERBOARD: jaringan → sengaja tanpa pos
            [2012, null, 'none'],                                            // KABEL UTP: jaringan didahulukan atas KABEL
            [2083, null, 'none'],                                            // SWITCH POE
            [595,  null, 'none'],                                            // FRAME LED VINYL: tak ada petunjuk
        ];
        CLI::write('Kata kunci NAMA BARANG (tanpa keperluan) terhadap ' . count($kasus) . ' item IT nyata:', 'yellow');
        foreach ($kasus as [$id, $harap, $sumber]) {
            if (! isset($per[$id])) { $cek("item #$id ada di fixture", false); continue; }
            $r = $rek->untuk($deptId, $per[$id]);
            $ok = $r['sumber'] === $sumber && $nama($r['pos_id']) === ($harap ?? '(tanpa rekomendasi)');
            $cek(sprintf('#%d %s', $id, mb_substr($per[$id]['nama_barang'], 0, 44)), $ok, $nama($r['pos_id']) . ' [' . $r['sumber'] . '] ' . $r['alasan']);
        }

        // Keperluan (keterangan order) — [id item, pos yang diharapkan (null = tanpa rekomendasi), sumber, potongan alasan]
        $kasusKep = [
            [2012, 'Penambahan CCTV', 'keperluan', 'penambahan kamera cctv'],          // KABEL UTP untuk CCTV
            [2083, 'Penambahan CCTV', 'keperluan', 'penambahan kamera cctv'],          // SWITCH POE untuk CCTV
            [3869, 'Penambahan CCTV', 'keperluan', 'penambahan kamera cctv'],          // CONNECTOR RJ45 untuk CCTV
            [2182, 'Penambahan CCTV', 'keperluan', 'pemasangan baru cctv'],
            [2026, 'Penambahan CCTV', 'keperluan', 'mengalahkan nama barang'],         // HARDDISK (nama → Maintenance Komputer) untuk CCTV
            [5510, 'Penambahan CCTV', 'keperluan', 'nama barang sejalan'],
            [3976, 'Maintenance CCTV Gedung', 'keperluan', 'penggantian kamera cctv'],  // penggantian ≠ penambahan
            [1304, 'Maintenance Komputer Kantor', 'keperluan', 'penggantian mouse'],     // "mouse NVR CCTV" bukan CCTV
            [5519, 'Maintenance Komputer Kantor', 'keperluan', 'penggantian adaptor'],
            [2081, 'Maintenance Komputer Kantor', 'keperluan', 'upgrade pc'],
            [1428, 'Peremajaan Komputer', 'keperluan', 'penggantian monitor'],
            [2835, 'Replacement Computer to Mobile devices', 'keperluan', 'penggantian komputer'],
            [3695, 'RTU Meteran Listrik IoT', 'keperluan', 'kwh meter'],
            [4570, 'Pengadaan Smart DoorLock', 'keperluan', 'accessdoor'],              // BATERAI (nama → Maintenance Komputer) untuk pintu
            [5740, null, 'none', 'videotron'],                                          // SKRUP untuk LED VIDEOTRON → tanpa pos
            [4017, null, 'none', 'monitor cctv'],                                       // pos TV monitoring CCTV nonaktif
            [3242, null, 'none', 'tidak ada petunjuk'],                                 // MIKROTIK "PEREMAJAAN MIKROTIK OFFICE": tetap jaringan → tanpa pos (aturan nama)
        ];
        CLI::write('Keperluan (keterangan order) terhadap ' . count($kasusKep) . ' item IT nyata:', 'yellow');
        foreach ($kasusKep as [$id, $harap, $sumber, $petunjuk]) {
            if (! isset($perKep[$id])) { $cek("item #$id ada di fixture", false); continue; }
            $r = $rek->untuk($deptId, $perKep[$id]);
            $ok = $r['sumber'] === $sumber && $nama($r['pos_id']) === ($harap ?? '(tanpa rekomendasi)') && ($id === 3242 || str_contains(mb_strtolower($r['alasan']), $petunjuk));
            $cek(sprintf('#%d %s ← %s', $id, mb_substr($perKep[$id]['nama_barang'], 0, 28), mb_substr((string) $perKep[$id]['keperluan'], 0, 40)), $ok, $nama($r['pos_id']) . ' [' . $r['sumber'] . '] ' . $r['alasan']);
        }
        $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => 'KABEL UTP', 'keperluan' => null]);
        $cek('feed lama (keperluan null): jalur nama barang tetap (KABEL UTP → tanpa pos)', $r['pos_id'] === null && $r['sumber'] === 'none', $r['alasan']);
        $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => 'KABEL UTP', 'keperluan' => 'UNTUK KEPERLUAN KANTIN']);
        $cek('keperluan tanpa aturan yang cocok: jatuh ke aturan nama barang', $r['pos_id'] === null && $r['sumber'] === 'none');

        // Memori: dalam transaksi yang di-rollback
        CLI::write('Memori (transaksi di-rollback):', 'yellow');
        $db->transStart();
        $posKomp = (int) array_search('Peremajaan Komputer', $posNama, true);
        $posCctv = (int) array_search('Penambahan CCTV', $posNama, true);
        $monitor = $per[1428];                                                // MONITOR XIAOMI A24I, kode kosong
        $rek->ingat($deptId, $monitor, $posCctv);                             // dipetakan "salah" sengaja agar memori mengalahkan kata kunci
        $r = $rek->untuk($deptId, $monitor);
        $cek('memori nama menang atas kata kunci', $r['sumber'] === 'memori' && $r['pos_id'] === $posCctv, $nama($r['pos_id']) . ' ' . $r['alasan']);
        $rek->ingat($deptId, $monitor, $posKomp); $rek->ingat($deptId, $monitor, $posKomp);
        $r = $rek->untuk($deptId, $monitor);
        $cek('pos yang paling sering dipakai menang (2× vs 1×)', $r['pos_id'] === $posKomp, $nama($r['pos_id']) . ' ' . $r['alasan']);
        $dgnKode = $per[1540];                                                // MONITOR XIAOMI A24I, kode S250649
        $rek->ingat($deptId, $dgnKode, $posCctv);
        $r = $rek->untuk($deptId, $dgnKode);
        $cek('memori kode_barang diutamakan atas nama', $r['sumber'] === 'memori' && $r['pos_id'] === $posCctv && str_contains($r['alasan'], 'kode'), $r['alasan']);
        $rek->lupakan($deptId, $dgnKode, $posCctv);
        $r = $rek->untuk($deptId, $dgnKode);
        $cek('lupakan() menghapus ingatan kode (kembali ke ingatan nama)', $r['sumber'] === 'memori' && $r['pos_id'] === $posKomp, $r['alasan']);
        // memori kalah dari keperluan yang jelas (user pernah memetakan kabel UTP ke pos lain, tapi kali ini untuk CCTV)
        $kabel = $perKep[2012];
        $posMaint = (int) array_search('Maintenance Komputer Kantor', $posNama, true);
        $rek->ingat($deptId, $kabel, $posMaint);
        $r = $rek->untuk($deptId, $kabel);
        $cek('keperluan jelas didahulukan atas memori', $r['sumber'] === 'keperluan' && $nama($r['pos_id']) === 'Penambahan CCTV', $r['alasan']);
        $db->transRollback();
        $rek->segarkan();
        $r = $rek->untuk($deptId, $monitor);
        $cek('setelah rollback: kembali ke kata kunci', $r['sumber'] === 'kata_kunci', $r['alasan']);

        // Kemiripan nama ↔ pos (tanpa kata kunci)
        CLI::write('Kemiripan token:', 'yellow');
        $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => 'Hosting dan Domain tahunan kantor']);
        $cek('"Hosting dan Domain …" → Hosting dan Domain', $nama($r['pos_id']) === 'Hosting dan Domain');
        $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => 'Langganan dashboard meteran air bulanan']);
        $cek('tanpa kata kunci → kemiripan token ke "Subs Dashboard Monitoring Meteran Air IoT"', $r['sumber'] === 'kemiripan' && str_contains($nama($r['pos_id']), 'Meteran Air'), $nama($r['pos_id']) . ' [' . $r['sumber'] . '] ' . $r['alasan']);
        $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => 'Galon air mineral']);
        $cek('tanpa petunjuk → tanpa rekomendasi', $r['pos_id'] === null && $r['sumber'] === 'none');

        // Pola regex
        $cek('cocokKata: kata pendek harus kata utuh (PC ≠ SPECIAL)', BudgetFsRekomendasi::cocokKata('PC', ' SPECIAL OFFER ') === null);
        $cek('cocokKata: regex /^ ?mouse/', BudgetFsRekomendasi::cocokKata('/^ ?mouse/', ' MOUSE LOGITECH ') !== null);

        // CCD (Creative Concept & Design): nama barang DGM nyata + kualifier mal (T-Banner e-Walk vs Pentacity) + pengganti terakhir
        $this->ujiCcd($db, $rek, $cek);
        if (in_array('--laporan', $params, true) || CLI::getOption('laporan') !== null) $this->laporanCcd($db, $rek);
        $db->query('SELECT 1');
        CLI::write(($gagal ? "$gagal dari $n kasus GAGAL" : "Semua $n kasus lulus"), $gagal ? 'red' : 'green');
        return $gagal ? EXIT_ERROR : EXIT_SUCCESS;
    }
    /** Ambil dept CCD + semua pos, atau null bila tidak ada. */
    private function deptCcd($db): ?array
    {
        $d = $db->table('departments')->where('name', 'Creative Concept & Design')->get()->getRowArray();
        return $d ?: null;
    }

    private function ujiCcd($db, BudgetFsRekomendasi $rek, callable $cek): void
    {
        $dept = $this->deptCcd($db);
        if (! $dept) { CLI::write('Dept CCD tidak ada — bagian CCD dilewati.', 'yellow'); return; }
        $deptId = (int) $dept['id'];
        $pos = $db->table('budget_pos')->where('dept_id', $deptId)->get()->getResultArray();
        $nm = array_column($pos, 'nama', 'id');
        $aktif = array_column($pos, 'aktif', 'nama');
        $nama = fn (?int $id) => $id ? ($nm[$id] ?? '#' . $id) : '(tanpa rekomendasi)';
        // pos nonaktif dilewati mesin: aturan "Cover FOH" jatuh ke pengganti bila pos itu nonaktif
        $ganti = fn (string $pos) => ! empty($aktif[$pos]) ? $pos : 'Materi cetak lainnya';
        $E = ['id_skema' => 2, 'skema' => 'Ewalk']; $P = ['id_skema' => 1, 'skema' => 'Pentacity'];

        // [nama barang, keperluan, mal, pos harapan (null = tanpa rekomendasi), potongan alasan]
        $kasus = [
            ['STICKER LIFT UK.123 X 206CM - WHATS ON JANUARY ( BAHAN STICKER + LAMINASI DOFF, LG KIRI, UG KANAN )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, 'Sticker Lift', 'sticker lift'],
            ['STICKER LIFT LAMINASI DOFF UK. 123 CM X 206 CM', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON OKTOBER 2026', $P, 'Sticker Lift', 'sticker lift'],
            ['T - BANNER UK.1 X 2,4 M - BAHAN VINYL ( TITIK PL1, PL2, PL3, PL4 )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, 'T-Banner 1x2,4m (Pentacity)', 'mal pentacity'],
            ['MATERI T-BANNER VINYL UK. 100 CM X 240 CM', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON OKTOBER 2026', $P, 'T-Banner 1x2,4m (Pentacity)', 'mal pentacity'],
            ['MATERI T-BANNER UK. 100 CM X 300 CM - BAHAN VINYL - WHATS ON RAMADHAN', 'UNTUK MEDIA PROMOSI WHATS ON RAMADHAN 2026', $E, 'T-Banner 1x3m (e-Walk)', 'mal e-walk'],
            ['T-BANNER UK 1X3 M', null, $E, 'T-Banner 1x3m (e-Walk)', 'mal e-walk'],
            ['X-BANNER 60X160', null, $E, 'T-Banner 1x3m (e-Walk)', 'mal e-walk'],
            ['TBANNER VINYL', null, $P, 'T-Banner 1x2,4m (Pentacity)', 'mal pentacity'],
            ['T-BANNER UK 1X3 M', null, ['id_skema' => null, 'skema' => null], null, 'tidak ada pos khusus'],   // mal tak dikenal: jangan menebak
            ['MATERI GIANT BANNER UK. 300 CM X 1000 CM - BAHAN FLEXI DOFF - 2 SISI', 'UNTUK MEDIA PROMOSI WHATS ON AGUSTUS', $E, 'Hanging Banner', 'giant banner'],
            ['HANGING BANNER 3X8', null, $P, 'Hanging Banner', 'hanging'],
            ['STICKER UK. 85 CM X 105 CM - NEON BOX BEACH GATE ( BAHAN STICKER )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, 'Neon Box Beach Gate', ''],
            ['STICKER UK. 85 CM X 105 CM - STICKER NEON BOX BEACH GATE ( BAHAN STICKER + LAMINASI DOFF )', 'UNTUK MEDIA PROMOSI MERDEKA BELANJA', $P, 'Neon Box Beach Gate', ''],
            ['ALBATROS UK 59,4 CM X 84,1 CM - FOODCOURT ( BAHAN ALBATROS + LAMINASI DOFF )', 'UNTUK KEPERLUAN MEDIA PROMOSI SUMMER VIBES', $P, 'Neon Box Foodcourt', ''],
            ['POSTER ALBATROS LAMINASI DOFF UK. 59,4 CM X 84,1 CM', 'UNTUK KEPERLUAN MEDIA PROMOSI UNSTOPPABLE SHOPPING CELEBRATION', $P, 'Neon Box Foodcourt', ''],
            ['STICKER NEON BOX FOODCOURT', null, $P, 'Neon Box Foodcourt', ''],
            ['COVER UK. 693 CM X 143 CM - FOH TENGAH ( BAHAN VINYL )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, $ganti('Cover FOH'), ''],
            ['COVER BACKSTAGE UK 300X200', null, $P, $ganti('Cover Backstage'), ''],
            ['STICKER UK. 117 CM X 40 CM - ATAS KIRI ( BAHAN STICKER + LAMINASI DOFF - STICKER KACA LOBBY LG KIDDY HOPE )', 'UNTUK MEDIA PROMOSI WHATS ON AGUSTUS', $P, 'Sticker Main Gate', ''],
            ['STICKER UK. 117 CM X 185 CM - BAWAH KIRI ( BAHAN STICKER + LAMINASI GLOSSY )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, 'Sticker Main Gate', ''],
            ['STICKER UK. 200,96 CM X 200,96 CM - MANDALA MASUK-1 ( BAHAN UV TRANSPARANT STICKER + TINTA PUTIH )', 'MEDIA STICKER KACA GATE LOBBY UTAMA PENTACITY DEADLINE PEMASANGAN : TGL 13 MARET 2026', $P, 'Sticker Main Gate', 'gate lobby'],
            ['STICKER UK. 138 CM X 207 CM - TENGAH LIFT SHILLOUETE ( BAHAN STICKER + LAMINASI DOFF )', 'MEDIA FASAD LIFT GF PENTACITY', $P, 'Materi cetak lainnya', 'pengganti'],   // bukan "Sticker Lift"
            ['BANNER UK. 40 CM X 122 CM - BAHAN STICKER + LAMINASI DOFF', 'UNTUK MEDIA PROMOSI SUMMER VIBES JUNI-JULI', $P, 'Materi cetak lainnya', 'pengganti'],
            ['BANNER - STICKER LAMINASI DOFF UK. 40 CM X 122 CM', 'U/N MEDIA PROMOSI WHATS ON SEPTEMBER-OKTOBER 2026', $E, 'Materi cetak lainnya', 'pengganti'],
            ['WALL SIGN VINYL UK. 250 CM X 400 CM', 'PERGANTIAN WALL SIGN MATERI LADIES MARKET', $E, 'Materi cetak lainnya', 'pengganti'],
            ['POSTER UK. 39 CM X 124 CM - WHATS ON JANUARY ( BAHAN ALBATROS )', 'UNTUK KEPERLUAN MEDIA PROMOSI WHATS ON JANUARY 2026', $P, 'Materi cetak lainnya', 'pengganti'],
            ['KERTAS DOUBLE SIDE MATTE INKJET PAPER E-PRINT A4 220 GSM 50 LEMBAR', 'UNTUK ADMINISTRASI DEPARTEMEN CREATIVE CONCEPT & DESIGN', $E, 'Materi cetak lainnya', 'administrasi departemen creative'],
            ['TEMPERED GLASS IPAD PRO M4 11 INCH GEN4', 'PERGANTIAN TEMPERED GLASS IPAD & HP ASET CREATIVE CONCEPT & DESIGN', $P, null, ''],   // aset, bukan cetak
            ['ENDORSE INFLUENCER INSTAGRAM', null, $P, 'KOL/Talent', 'endorse'],
            ['PAKET KOL REVIEW', null, $P, 'KOL/Talent', 'kol'],
            ['HADIAH GIVEAWAY AKHIR TAHUN', null, $P, 'Giveaway', 'giveaway'],
            ['SNACK BOX', 'UNTUK KEPERLUAN PRESS CONFERENCE ANNIVERSARY', $P, 'Press Conference', 'press conference'],
            ['PAKET DATA TELKOMSEL 50GB', null, $P, 'Kuota HP Public Relation', ''],
            ['IG ADS BOOST JANUARI', null, $P, 'IG Ads', 'ig ads'],
            ['LANGGANAN CANVA PRO TAHUNAN', null, $P, 'Subscription Canva', 'canva'],
            ['ENVATO ELEMENTS 1 TAHUN', null, $P, 'Subscription Envato', 'envato'],
            ['FREEPIK PREMIUM', null, $P, 'Subscription Freepik', 'freepik'],
            ['CAPCUT PRO BULANAN', null, $P, 'Subscription Capcut', 'capcut'],
            ['GOOGLE ONE 2TB', null, $P, 'Subscription GoogleOne', 'google one'],
        ];
        CLI::write('CCD (data DGM + kualifier mal) terhadap ' . count($kasus) . ' kasus:', 'yellow');
        foreach ($kasus as $i => [$barang, $kep, $skema, $harap, $petunjuk]) {
            $r = $rek->untuk($deptId, ['kode_barang' => null, 'nama_barang' => $barang, 'keperluan' => $kep] + $skema);
            $ok = $nama($r['pos_id']) === ($harap ?? '(tanpa rekomendasi)') && ($petunjuk === '' || str_contains(mb_strtolower($r['alasan']), $petunjuk));
            $cek(sprintf('CCD %d %s', $i + 1, mb_substr($barang, 0, 46)), $ok, $nama($r['pos_id']) . ' [' . $r['sumber'] . '] ' . $r['alasan']);
        }

        // Memori tidak boleh menimpa aturan bermal: nama yang sama pernah dipetakan ke pos e-Walk, item Pentacity tetap ke Pentacity
        $db->transStart();
        $posEw = (int) array_search('T-Banner 1x3m (e-Walk)', $nm, true);
        $item = ['kode_barang' => null, 'nama_barang' => 'T - BANNER VINYL', 'keperluan' => null];
        $rek->ingat($deptId, $item, $posEw); $rek->ingat($deptId, $item, $posEw);
        $r = $rek->untuk($deptId, $item + $P);
        $cek('CCD memori e-Walk tidak menimpa mal Pentacity', $nama($r['pos_id']) === 'T-Banner 1x2,4m (Pentacity)' && $r['sumber'] === 'kata_kunci', $nama($r['pos_id']) . ' [' . $r['sumber'] . ']');
        $db->transRollback();
        $rek->segarkan();
    }

    /**
     * Evaluasi pada data DGM nyata (fixture writable/flowstore-fixture-dgm.json, dibuat scripts/fs-buat-fixture.php --divisi=35):
     * cakupan rekomendasi 2026, daftar yang meragukan, dan perbandingan total BTB per pos per bulan terima dengan realisasi
     * Budget Departemen CCD (hanya informasi; tidak menulis apa pun).
     */
    private function laporanCcd($db, BudgetFsRekomendasi $rek): void
    {
        $dept = $this->deptCcd($db);
        $berkas = WRITEPATH . 'flowstore-fixture-dgm.json';
        if (! $dept || ! is_file($berkas)) { CLI::write('Fixture DGM tidak ada — laporan dilewati.', 'yellow'); return; }
        $deptId = (int) $dept['id'];
        $nm = array_column($db->table('budget_pos')->where('dept_id', $deptId)->get()->getResultArray(), 'nama', 'id');
        $items = array_filter(json_decode(file_get_contents($berkas), true)['items'], fn ($i) => $i['tanggal_diterima'] >= '2026-01-01' && $i['status_btb'] !== 'batal');
        $ada = 0; $sumber = []; $btb = []; $ragu = []; $tanpa = [];
        foreach ($items as $i) {
            $r = $rek->untuk($deptId, $i);
            $bln = (int) substr($i['tanggal_diterima'], 5, 2);
            $p = $r['pos_id'] ? $nm[$r['pos_id']] : '(tanpa rekomendasi)';
            if ($r['pos_id']) $ada++; else $tanpa[] = $i['nama_barang'] . ' — ' . $r['alasan'];
            $sumber[$r['sumber']] = ($sumber[$r['sumber']] ?? 0) + 1;
            $btb[$p][$bln] = ($btb[$p][$bln] ?? 0) + (float) $i['nilai_diterima'];
            if (str_contains($r['alasan'], 'pengganti') || str_contains($r['alasan'], 'mengalahkan')) $ragu[$p . ' | ' . mb_substr($i['nama_barang'], 0, 70) . ' | ' . $r['alasan']] = 1;
        }
        CLI::write(sprintf("\nLAPORAN CCD 2026: %d item (bukan batal), %d berekomendasi (%d%%), %d tanpa rekomendasi", count($items), $ada, count($items) ? round($ada / count($items) * 100) : 0, count($items) - $ada), 'yellow');
        CLI::write('Sumber: ' . json_encode($sumber));
        foreach (array_slice($tanpa, 0, 15) as $t) CLI::write('  tanpa: ' . mb_substr($t, 0, 150));
        $real = [];
        foreach ($db->query("SELECT p.nama, MONTH(r.tanggal) b, SUM(r.nilai) v FROM budget_realisasi r JOIN budget_pos p ON p.id = r.pos_id WHERE r.dept_id = ? AND YEAR(r.tanggal) = 2026 GROUP BY p.nama, MONTH(r.tanggal)", [$deptId])->getResultArray() as $x) $real[$x['nama']][(int) $x['b']] = (float) $x['v'];
        CLI::write("\nBTB (bulan terima) vs Budget Dept (Excel) per pos, Rp ribu — selisih = BTB - Budget Dept:");
        $semua = array_unique(array_merge(array_keys($btb), array_keys($real)));
        sort($semua);
        $tot = ['b' => array_fill(1, 12, 0.0), 'r' => array_fill(1, 12, 0.0)];
        foreach ($semua as $p) {
            $baris = [];
            for ($m = 1; $m <= 9; $m++) {
                $b = $btb[$p][$m] ?? 0; $r = $real[$p][$m] ?? 0; $tot['b'][$m] += $b; $tot['r'][$m] += $r;
                $baris[] = ($b || $r) ? sprintf('%d:%s/%s', $m, number_format($b / 1000, 0, ',', '.'), number_format($r / 1000, 0, ',', '.')) : null;
            }
            CLI::write(sprintf('  %-34s %s', mb_substr($p, 0, 34), implode('  ', array_filter($baris))));
        }
        $baris = [];
        for ($m = 1; $m <= 9; $m++) $baris[] = sprintf('%d:%s/%s(%+s)', $m, number_format($tot['b'][$m] / 1000, 0, ',', '.'), number_format($tot['r'][$m] / 1000, 0, ',', '.'), number_format(($tot['b'][$m] - $tot['r'][$m]) / 1000, 0, ',', '.'));
        CLI::write('  TOTAL (BTB/BudgetDept)             ' . implode('  ', $baris), 'green');
        CLI::write("\nMeragukan (pengganti terakhir / mengalahkan nama barang), unik: " . count($ragu));
        foreach (array_slice(array_keys($ragu), 0, 40) as $r) CLI::write('  ? ' . $r);
    }
}