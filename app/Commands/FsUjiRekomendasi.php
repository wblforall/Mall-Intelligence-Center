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
            $ok = $r['sumber'] === $sumber && $nama($r['pos_id']) === ($harap ?? '(tanpa rekomendasi)') && ($id === 3242 || str_contains($r['alasan'], $petunjuk));
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

        $db->query('SELECT 1');
        CLI::write(($gagal ? "$gagal dari $n kasus GAGAL" : "Semua $n kasus lulus"), $gagal ? 'red' : 'green');
        return $gagal ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
