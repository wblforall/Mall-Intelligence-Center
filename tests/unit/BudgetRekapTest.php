<?php

use App\Libraries\BudgetRekap;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan rekap Budget Departemen — tanpa DB.
 *
 * @internal
 */
final class BudgetRekapTest extends CIUnitTestCase
{
    private array $pos = [
        ['id' => 1, 'nama' => 'Reimburse poin', 'aktif' => 1],
        ['id' => 2, 'nama' => 'Opening tenant', 'aktif' => 1],
        ['id' => 3, 'nama' => 'Pos lama', 'aktif' => 0],
    ];

    public function testKeduanyaDibagiRataDanJumlahnyaTetapUtuh(): void
    {
        $this->assertSame(500.0, BudgetRekap::porsi('both', 1000, 'ewalk'));
        $this->assertSame(500.0, BudgetRekap::porsi('both', 1000, 'pentacity'));
        // Nilai ganjil: selisih sen jatuh ke Pentacity, jumlahnya tetap utuh.
        $e = BudgetRekap::porsi('both', 0.05, 'ewalk');
        $p = BudgetRekap::porsi('both', 0.05, 'pentacity');
        $this->assertEqualsWithDelta(0.05, $e + $p, 0.0001);
        $this->assertSame(0.0, BudgetRekap::porsi('ewalk', 1000, 'pentacity'));
        $this->assertSame(1000.0, BudgetRekap::porsi('', 1000, 'semua'));
    }

    public function testTotalDijumlahDariSemuaBulanTermasukJuniSampaiAgustus(): void
    {
        // Kasus Excel LPSS: realisasi Jun–Agu tak ikut total. Di sini wajib ikut.
        $rencana = [];
        foreach (range(1, 12) as $m) {
            $rencana[] = ['pos_id' => 1, 'bulan' => $m, 'mall' => 'ewalk', 'jumlah' => 10];
            $rencana[] = ['pos_id' => 1, 'bulan' => $m, 'mall' => 'pentacity', 'jumlah' => 10];
        }
        $realisasi = [
            ['pos_id' => 1, 'bulan' => 1, 'mall' => 'both', 'nilai' => 80],
            ['pos_id' => 1, 'bulan' => 6, 'mall' => 'both', 'nilai' => 180],
            ['pos_id' => 1, 'bulan' => 8, 'mall' => 'ewalk', 'nilai' => 70],
        ];

        $r = BudgetRekap::susun($this->pos, $rencana, $realisasi, [], 'semua', 8);

        $this->assertEqualsWithDelta(240.0, $r['total']['budget'], 0.001);
        $this->assertEqualsWithDelta(160.0, $r['total']['budgetSd'], 0.001);
        $this->assertEqualsWithDelta(330.0, $r['total']['realisasi'], 0.001);
        $this->assertEqualsWithDelta(-90.0, $r['total']['sisa'], 0.001);
        $this->assertEqualsWithDelta(180.0, $r['baris'][1]['bulan'][6]['r'], 0.001);

        $e = BudgetRekap::susun($this->pos, $rencana, $realisasi, [], 'ewalk', 8);
        $this->assertEqualsWithDelta(40 + 90 + 70, $e['total']['realisasi'], 0.001);
        $this->assertEqualsWithDelta(120.0, $e['total']['budget'], 0.001);
    }

    public function testRealisasiTanpaBudgetTetapIkutTotalDenganPersenKosong(): void
    {
        // Kasus Privilege Tier & Merchandise: ada realisasi, budget nol.
        $realisasi = [['pos_id' => 2, 'bulan' => 5, 'mall' => '', 'nilai' => 42]];

        $r = BudgetRekap::susun($this->pos, [], $realisasi, [], 'semua', 12);

        $this->assertEqualsWithDelta(42.0, $r['total']['realisasi'], 0.001);
        $this->assertNull($r['baris'][2]['pct']);
        $this->assertEqualsWithDelta(-42.0, $r['baris'][2]['sisa'], 0.001);
    }

    public function testBarisTanpaMalDicatatTerpisahSaatFilterMal(): void
    {
        $realisasi = [['pos_id' => 2, 'bulan' => 5, 'mall' => '', 'nilai' => 42]];

        $r = BudgetRekap::susun($this->pos, [], $realisasi, [], 'ewalk', 12);

        $this->assertEqualsWithDelta(0.0, $r['total']['realisasi'], 0.001);
        $this->assertEqualsWithDelta(42.0, $r['tanpaMal'], 0.001);
    }

    public function testPosNonaktifHanyaTampilBilaMasihPunyaAngka(): void
    {
        $r = BudgetRekap::susun($this->pos, [], [], [], 'semua', 12);
        $this->assertArrayNotHasKey(3, $r['baris']);
        $this->assertArrayHasKey(2, $r['baris']);

        $r = BudgetRekap::susun($this->pos, [], [['pos_id' => 3, 'bulan' => 2, 'mall' => '', 'nilai' => 5]], [], 'semua', 12);
        $this->assertArrayHasKey(3, $r['baris']);
    }

    public function testRealisasiTahunLaluIkutFilterMal(): void
    {
        $lalu = [['pos_id' => 1, 'mall' => 'both', 'nilai' => 100], ['pos_id' => 1, 'mall' => 'ewalk', 'nilai' => 10]];
        $r = BudgetRekap::susun($this->pos, [], [], $lalu, 'ewalk', 12);
        $this->assertEqualsWithDelta(60.0, $r['baris'][1]['lalu'], 0.001);
    }

    public function testSatuSelBerisiBeberapaRincianDijumlahDanKeterangannyaIkut(): void
    {
        // Januari: dua hari besar dalam satu pos — budget per event.
        $rencana = [
            ['pos_id' => 2, 'bulan' => 1, 'mall' => 'ewalk', 'keterangan' => 'Tahun Baru', 'jumlah' => 5],
            ['pos_id' => 2, 'bulan' => 1, 'mall' => 'ewalk', 'keterangan' => "Isra Mi'raj", 'jumlah' => 3],
            ['pos_id' => 2, 'bulan' => 1, 'mall' => 'ewalk', 'keterangan' => null, 'jumlah' => 2],
        ];

        $r = BudgetRekap::susun($this->pos, $rencana, [], [], 'semua', 12);

        $this->assertEqualsWithDelta(10.0, $r['baris'][2]['bulan'][1]['b'], 0.001);
        $this->assertSame(['Tahun Baru', "Isra Mi'raj"], array_column($r['baris'][2]['bulan'][1]['ket'], 'k'));
        $this->assertEqualsWithDelta(10.0, $r['total']['budget'], 0.001);
    }

    public function testKeteranganRincianHanyaMunculDiMalnyaSendiri(): void
    {
        $rencana = [
            ['pos_id' => 1, 'bulan' => 1, 'mall' => 'pentacity', 'keterangan' => 'Culinary Festival', 'jumlah' => 15],
            ['pos_id' => 1, 'bulan' => 1, 'mall' => 'both', 'keterangan' => 'Bersama', 'jumlah' => 10],
        ];

        $e = BudgetRekap::susun($this->pos, $rencana, [], [], 'ewalk', 12);
        $p = BudgetRekap::susun($this->pos, $rencana, [], [], 'pentacity', 12);

        $this->assertSame(['Bersama'], array_column($e['baris'][1]['bulan'][1]['ket'], 'k'));
        $this->assertSame(['Culinary Festival', 'Bersama'], array_column($p['baris'][1]['bulan'][1]['ket'], 'k'));
    }

    public function testRincianSelMembuangBarisKosongDanMembacaRupiah(): void
    {
        $out = BudgetRekap::rincianSel([
            ['k' => ' Imlek ', 'j' => '6.400.000'],
            ['k' => 'Kosong', 'j' => ''],
            ['k' => '', 'j' => 2500000],
            'sampah',
        ]);
        $this->assertSame([
            ['keterangan' => 'Imlek', 'jumlah' => 6400000.0],
            ['keterangan' => null, 'jumlah' => 2500000.0],
        ], $out);
        $this->assertSame([], BudgetRekap::rincianSel('bukan array'));
    }

    public function testPutusanSelSaatBeberapaOrangMengisi(): void
    {
        $a = [['keterangan' => 'Imlek', 'jumlah' => 5.0]];
        $b = [['keterangan' => 'Imlek', 'jumlah' => 7.0]];
        $c = [['keterangan' => 'Imlek', 'jumlah' => 9.0]];

        // Tak ada yang mengubah sel ini sejak dibuka → tulis.
        $this->assertSame('tulis', BudgetRekap::putusanSel($b, $a, $a));
        // Orang lain sudah mengubahnya (a → c) → jangan timpa.
        $this->assertSame('bentrok', BudgetRekap::putusanSel($b, $a, $c));
        // Keduanya mengisi angka yang sama → tak perlu ditulis, bukan bentrok.
        $this->assertSame('lewati', BudgetRekap::putusanSel($c, $a, $c));
        // Sel kosong diisi, padahal orang lain sudah mengisinya duluan → bentrok.
        $this->assertSame('bentrok', BudgetRekap::putusanSel($b, [], $a));
        // Klien lama tanpa info "asli" → perilaku lama (tulis).
        $this->assertSame('tulis', BudgetRekap::putusanSel($b, null, $c));
    }

    public function testAngkaMembacaFormatRupiah(): void
    {
        $this->assertSame(1250000.0, BudgetRekap::angka('Rp 1.250.000'));
        $this->assertSame(1250000.0, BudgetRekap::angka('1250000'));
        $this->assertSame(1500.5, BudgetRekap::angka('1.500,5'));
        $this->assertSame(500.0, BudgetRekap::angka('-500'));
        $this->assertNull(BudgetRekap::angka(''));
        $this->assertNull(BudgetRekap::angka('Rp'));
    }
}
