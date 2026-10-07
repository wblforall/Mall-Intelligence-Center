<?php

use App\Libraries\PestRekap;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan rekap impor bulanan pada rentang tanggal bebas — tanpa DB.
 *
 * @internal
 */
final class PestRekapTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('tanggal');
    }

    private function legacy(string $mall, string $bulan, array $items, bool $tergeser = false): array
    {
        return ['id' => 1, 'mall' => $mall, 'bulan' => $bulan, 'tanggal' => date('Y-m-t', strtotime($bulan . '-01')),
                'tergeser' => $tergeser, 'items' => $items, 'total' => array_sum($items)];
    }

    public function testRekapImporBulanUtuhDihitungTerpotongTidakTergeserTidak(): void
    {
        $harian = ['2026-03-05' => ['ewalk' => [1 => 3]]];
        $kunj   = [['mall' => 'ewalk', 'tanggal' => '2026-03-05', 'total' => 3]];
        $legacy = [
            $this->legacy('ewalk', '2026-01', [1 => 10, 4 => 5]),         // utuh → dihitung
            $this->legacy('pentacity', '2026-03', [1 => 7]),              // terpotong → tidak
            $this->legacy('ewalk', '2026-03', [1 => 99], true),           // tergeser → tidak
        ];

        $r = PestRekap::susun($harian, $kunj, $legacy, '2026-01-01', '2026-03-10', null);

        $this->assertSame(18, $r['grand']);                 // 3 kunjungan + 15 impor
        $this->assertSame(15, $r['grandLegacy']);
        $this->assertSame(3, $r['grandKunjungan']);
        $this->assertCount(1, $r['legacyDipakai']);
        $this->assertCount(1, $r['legacyTerpotong']);
        $this->assertCount(1, $r['legacyTergeser']);
        $this->assertSame(10, $r['legacyTerpotong'][0]['hari_tercakup']);   // 1–10 Mar
        $this->assertSame(['ewalk' => 18, 'pentacity' => 0], $r['totalMall']);
    }

    public function testTerpotongMencatatHariTercakup(): void
    {
        $legacy = [$this->legacy('ewalk', '2026-02', [1 => 7])];
        $r = PestRekap::susun([], [], $legacy, '2026-02-10', '2026-03-31', 'ewalk');
        $this->assertSame(0, $r['grand']);
        $this->assertSame(19, $r['legacyTerpotong'][0]['hari_tercakup']);   // 10–28 Feb
        $this->assertSame(28, $r['legacyTerpotong'][0]['hari_bulan']);
    }

    public function testRincianMingguanMenjumlahSamaDenganTotalDenganBarisImporTerpisah(): void
    {
        $harian = [
            '2026-01-05' => ['ewalk' => [1 => 2]],
            '2026-02-20' => ['pentacity' => [4 => 6]],
        ];
        $kunj = [
            ['mall' => 'ewalk', 'tanggal' => '2026-01-05', 'total' => 2],
            ['mall' => 'pentacity', 'tanggal' => '2026-02-20', 'total' => 6],
            ['mall' => 'ewalk', 'tanggal' => '2026-02-21', 'total' => 0],
        ];
        $legacy = [$this->legacy('pentacity', '2026-01', [1 => 40])];
        $r = PestRekap::susun($harian, $kunj, $legacy, '2026-01-01', '2026-03-01', null);
        $rc = PestRekap::rincian($r);

        $this->assertSame('mingguan', $rc['mode']);
        $this->assertSame(40, $rc['legacy']['total']);
        $sum = array_sum(array_column($rc['ember'], 'total')) + $rc['legacy']['total'];
        $this->assertSame($r['grand'], $sum);
        $this->assertTrue($rc['ember'][0]['sebagian']);                  // W01 dimulai 29 Des 2025
        $this->assertSame('2026-01-01', $rc['ember'][0]['dari']);
        $this->assertSame(3, array_sum(array_column($rc['ember'], 'kunjungan')));
    }

    public function testRincianBulananMemasukkanImorKeEmberBulannya(): void
    {
        $legacy = [$this->legacy('ewalk', '2025-06', [1 => 12])];
        $r  = PestRekap::susun([], [], $legacy, '2025-05-15', '2025-09-30', null);
        $rc = PestRekap::rincian($r);
        $this->assertSame('bulanan', $rc['mode']);
        $this->assertSame(0, $rc['legacy']['total']);
        $juni = array_values(array_filter($rc['ember'], fn($e) => $e['kunci'] === '2025-06'))[0];
        $this->assertSame(12, $juni['total']);
        $this->assertTrue($juni['legacy']);
        $this->assertTrue($rc['ember'][0]['sebagian']);                  // Mei mulai tgl 15
    }

    public function testRentangSahMenukarDanMembatasi(): void
    {
        $this->assertSame(['2026-01-01', '2026-02-01'], PestRekap::rentangSah('2026-02-01', '2026-01-01', 'x', 'y'));
        $this->assertSame(['2026-09-01', '2026-09-30'], PestRekap::rentangSah('salah', '2026-02-30', '2026-09-01', '2026-09-30'));
        [$a, $b] = PestRekap::rentangSah('2000-01-01', '2026-12-31', 'x', 'y');
        $this->assertSame(PestRekap::MAKS_HARI, (int) ((strtotime($b) - strtotime($a)) / 86400) + 1);
    }

    public function testPeriodeSebelumnyaSamaPanjang(): void
    {
        $this->assertSame(['2026-08-02', '2026-08-31'], PestRekap::periodeSebelumnya('2026-09-01', '2026-09-30'));
    }
}
