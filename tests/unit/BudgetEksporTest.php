<?php

use App\Libraries\BudgetEkspor;
use App\Libraries\BudgetRekap;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Lembar Excel Budget Departemen — tanpa DB.
 *
 * @internal
 */
final class BudgetEksporTest extends CIUnitTestCase
{
    private function lembar(bool $pisahMal): array
    {
        $pos = [['id' => 1, 'nama' => 'Biaya Event', 'aktif' => 1, 'keterangan' => 'Event reguler']];
        $ren = [
            ['pos_id' => 1, 'bulan' => 1, 'mall' => 'ewalk', 'keterangan' => 'Tahun Baru', 'jumlah' => 30],
            ['pos_id' => 1, 'bulan' => 1, 'mall' => 'pentacity', 'keterangan' => null, 'jumlah' => 20],
        ];
        $d = [$pos, $ren, [['pos_id' => 1, 'bulan' => 1, 'mall' => 'both', 'nilai' => 10]], []];
        $rekap = BudgetRekap::susun(...$d, ...['semua', 12, []]);
        $perMal = $pisahMal ? ['ewalk' => BudgetRekap::susun(...$d, ...['ewalk', 12, []]), 'pentacity' => BudgetRekap::susun(...$d, ...['pentacity', 12, []])] : null;
        $info = ['dept' => 'E&P', 'tahun' => 2027, 'oleh' => 'Uji', 'waktu' => '13/10/2026 10:00'];
        return BudgetEkspor::lembar($info, $rekap, $perMal, $ren, []);
    }

    public function testEmpatLembarDanTotalRekapBerupaRumus(): void
    {
        $l = $this->lembar(false);
        $this->assertSame(['Rekap', 'Per Bulan', 'Rincian Budget', 'Realisasi'], array_column($l, 'name'));
        $total = $l[0]['rows'][5];                         // kepala di baris 4, satu pos di baris 5, total baris 6
        $this->assertSame('Total', $total[1]['v']);
        $this->assertSame('SUM(C5:C5)', $total[2]['f']);
        $this->assertEquals(50, $total[2]['v']);
    }

    public function testKeteranganRincianJadiKomentarSelBudget(): void
    {
        $gab = $this->lembar(false)[1];
        $this->assertSame(['C6' => 'Tahun Baru: 30 (eWalk)'], $gab['komentar']);

        $mal = $this->lembar(true)[1];                     // eWalk Jan = kolom C, Penta Jan = E; kepala 3 baris
        $this->assertSame(['C7' => 'Tahun Baru: 30'], $mal['komentar']);
    }

    public function testSerialTanggalExcel(): void
    {
        $this->assertSame(46037, BudgetEkspor::serialTanggal('2026-01-15'));
        $this->assertSame('kosong', BudgetEkspor::serialTanggal('kosong'));
    }
}
